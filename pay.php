<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
} else {
    header('Location: login.php');
    exit(); 
}

include './database/dbconfig.php'; // Include your database connection

// Get the parameters from the query string
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : null;

// Validate parameters
if (!$user_id || !$amount || $amount <= 0) {
    $_SESSION['message'] = "Invalid request parameters.";
    $_SESSION['messageType'] = "error";
    header('Location: fund.php');
    exit();
}

// Generate the current date and time
$date = date("Y-m-d");
$time = date("H:i:s");

// Generate a unique transaction ID
$transactionId = uniqid("txn-");

// Begin a database transaction
$conn->begin_transaction();

try {
    // Update the user's balance in the `users` table
    $updateBalanceQuery = "UPDATE users SET balance = balance + ? WHERE id = ?";
    $stmt = $conn->prepare($updateBalanceQuery);
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();

    // Check if the balance update was successful
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to update user balance.");
    }

    // Insert the transaction into the `transactions` table
    $insertTransactionQuery = "
        INSERT INTO transactions (id, amount, transactionType, date, time, transactionId, status)
        VALUES (?, ?, 'Fund', ?, ?, ?, 'success')
    ";
    $stmt = $conn->prepare($insertTransactionQuery);
    $stmt->bind_param("idsss", $user_id, $amount, $date, $time, $transactionId);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

 

    // Redirect to the fund.php page
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
?>
