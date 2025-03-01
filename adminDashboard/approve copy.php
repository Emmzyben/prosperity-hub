<?php
function handleTransaction($transactionId, $actionType)
{
    include './database/dbconfig.php';

    if ($actionType === 'approve') {
        $sql = "SELECT bank_name, account_name, account_number, amount, id FROM transactions WHERE transactionId = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        if ($transaction) {
            $userId = $transaction['id'];
            $amount = $transaction['amount'];
            $bankName = $transaction['bank_name'];
        
            // Fetch user data
            $sql = "SELECT available_withdrawal, balance FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userResult = $stmt->get_result();
            $user = $userResult->fetch_assoc();
        
            if ($user) {
                $availableWithdrawal = (float)$user['available_withdrawal'];
                $balance = (float)$user['balance'];
        
                if ($amount > $availableWithdrawal) {
                    echo "<script>alert('Insufficient funds for withdrawal.');</script>";
                    return;
                }

              
                // Flutterwave API call for transfer
                $flutterwaveUrl = "https://api.flutterwave.com/v3/transfers";

                $flutterwaveData = [
                    "account_bank" => $transaction['bank_name'],
                    "account_number" => $transaction['account_number'],
                    "amount" => $amount,
                    "currency" => "NGN",
                    "narration" => "Transfer to " . $transaction['account_name'],
                ];

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $flutterwaveUrl);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($flutterwaveData));
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . 'FLWSECK-13b47183187262772a88525f4cd9adc4-1932ff71daavt-X',
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($curl);
                if ($response === false) {
                    curl_close($curl);
                    echo "<script>alert('Curl error: " . curl_error($curl) . "');</script>";
                    return;
                }
                curl_close($curl);

                $responseData = json_decode($response, true);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $data = $responseData['data'];
                    $transferId = $data['id'];
                    $transferStatus = $data['status'];

                    $sql = "UPDATE transactions SET status = ?, transfer_id = ? WHERE transactionId = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $transferStatus, $transferId, $transactionId);
                    $stmt->execute();

                    if ($transferStatus === 'SUCCESSFUL') {
                        $newAvailableWithdrawal = $availableWithdrawal - $amount;
                        $newBalance = $balance - $amount;

                        $sql = "UPDATE users SET available_withdrawal = ?, balance = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ddi", $newAvailableWithdrawal, $newBalance, $userId);
                        $stmt->execute();

                        echo "<script>alert('Transaction approved and funds transferred successfully!');</script>";
                    } else {
                        echo "<script>alert('Transaction approved but transfer status: $transferStatus');</script>";
                    }
                } else {
                    $errorMessage = $responseData['message'] ?? 'Transfer failed with Flutterwave.';
                    echo "<script>alert('Transaction approval failed: $errorMessage');</script>";
                }
            } else {
                echo "<script>alert('User not found.');</script>";
            }
        } else {
            echo "<script>alert('Transaction not found.');</script>";
        }
    } elseif ($actionType === 'decline') {
        $sql = "UPDATE transactions SET status = 'declined' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();

        echo "<script>alert('Transaction declined successfully.');</script>";
    } else {
        echo "<script>alert('Invalid action type.');</script>";
    }

    $conn->close();
}

// Example usage:
// handleTransaction($transactionId, $actionType);
?>
