<?php
include './database/dbconfig.php';
header('Content-Type: application/json');

// Check if user_id is passed in the URL
if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id']; // Get userId from the callback URL
} else {
    echo json_encode(['status' => false, 'message' => 'User ID not found']);
    exit();
}

// Check if the transaction reference is provided
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    // Verify the transaction with Paystack
    $url = "https://api.paystack.co/transaction/verify/" . $reference;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer sk_live_63154c8a0c39934a8a024302b80b149d38236f38", // Replace with your secret key
        "Cache-Control: no-cache",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($result && $result['status'] && $result['data']['status'] === 'success') {
        $amount = (int) $result['data']['amount']; // Amount from Paystack (subunits)

        // Convert the amount from subunits (cents) to normal currency (naira, dollars, etc.)
        $amountInCurrency = $amount / 100; // Convert subunits to currency

        $date = date("Y-m-d");
        $time = date("H:i:s");

        // Generate a unique transaction ID
        $transactionId = $reference;

        // Begin a database transaction
        $conn->begin_transaction();

        try {
            // Update the user's balance in the `users` table (add the amount in currency)
            $updateBalanceQuery = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($updateBalanceQuery);
            $stmt->bind_param("di", $amountInCurrency, $userId); // Use $amountInCurrency instead of $amount
            $stmt->execute();

            // Check if the balance update was successful
            if ($stmt->affected_rows === 0) {
                throw new Exception("Failed to update user balance.");
            }

            // Insert the transaction into the `transactions` table with the normal currency amount
            $insertTransactionQuery = "
                INSERT INTO transactions (id, amount, transactionType, date, time, transactionId, status)
                VALUES (?, ?, 'Fund', ?, ?, ?, 'success')
            ";
            $stmt = $conn->prepare($insertTransactionQuery);
            $stmt->bind_param("idsss", $userId, $amountInCurrency, $date, $time, $transactionId); // Use $amountInCurrency
            $stmt->execute();

            // Commit the transaction
            $conn->commit();

            // Redirect to the dashboard
            header('Location: dashboard.php');
            exit();

        } catch (Exception $e) {
            // Roll back the transaction in case of error
            $conn->rollback();

            // Set error message in session
            $_SESSION['message'] = "Error processing the transaction: " . $e->getMessage();
            $_SESSION['messageType'] = "error";

            // Redirect to the fund.php page
            header('Location: fund.php');
            exit();
        }
    } else {
        // Redirect to fund.php if payment verification fails
        header("Location: fund.php?error=Transaction+failed");
        exit();
    }
} else {
    // Redirect to fund.php if no transaction reference is provided or method is incorrect
    header("Location: fund.php?error=No+transaction+reference+provided");
    exit();
}
