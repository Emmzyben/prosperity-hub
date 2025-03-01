<?php
include './database/dbconfig.php';
$apiKey = 'FLWSECK-13b47183187262772a88525f4cd9adc4-1932ff71daavt-X'; 

// Function to check the transfer status from Flutterwave API
function checkTransferStatus($transfer_id, $apiKey) {
    $url = "https://api.flutterwave.com/v3/transfers/{$transfer_id}";
    $headers = [
        "Authorization: Bearer {$apiKey}",
    ];

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute the cURL request and get the response
    $response = curl_exec($ch);
    curl_close($ch);

    // Check for any cURL errors
    if ($response === false) {
        return null;
    }

    // Decode the JSON response
    $responseData = json_decode($response, true);

    // Check if the API response contains the 'data' field and status
    if (isset($responseData['data'])) {
        return $responseData['data']['status'];
    }

    return null;
}

// Query to get all transactions with transactionType 'withdrawal'
$sql = "SELECT * FROM transactions WHERE transactionType = 'withdrawal' AND transfer_id IS NOT NULL AND (status = 'NEW' OR status = 'PENDING' )";

$result = $conn->query($sql);

// Check if there are any transactions
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactionId = $row['transactionId'];
        $transferId = $row['transfer_id'];
        $userId = $row['id']; 
        $amount = $row['amount'];  

        // Check the transfer status from Flutterwave API
        $transferStatus = checkTransferStatus($transferId, $apiKey);

       

        if ($transferStatus === 'SUCCESSFUL') {
            // Fetch the user's available withdrawal and balance
            $userSql = "SELECT available_withdrawal, balance FROM users WHERE id = ?";
            $stmt = $conn->prepare($userSql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userResult = $stmt->get_result();
            $userData = $userResult->fetch_assoc();

            if ($userData) {
                $availableWithdrawal = $userData['available_withdrawal'];
                $balance = $userData['balance'];

                // Calculate the new available withdrawal and balance
                $newAvailableWithdrawal = $availableWithdrawal - $amount;
                $newBalance = $balance - $amount;

                // Update the user's available withdrawal and balance
                $updateSql = "UPDATE users SET available_withdrawal = ?, balance = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ddi", $newAvailableWithdrawal, $newBalance, $userId);
                $updateStmt->execute();

                echo "Transaction ID {$transactionId} - User ID {$userId}: Withdrawal successful. Updated balance and available withdrawal.<br>";
            } else {
                echo "User ID {$userId} not found.<br>";
            }
        } else {
            $sql = "UPDATE transactions SET status = ?, transfer_id = ? WHERE transactionId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $transferStatus, $transferId, $transactionId);
            $stmt->execute();
            echo "Transaction ID {$transactionId}: Status is not successful. Current status: {$transferStatus}.<br>";
        
        }
    }
} else {
    echo "No pending withdrawals found.<br>";
}

$conn->close();
?>
