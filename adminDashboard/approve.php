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

                // Proceed with the transaction
                $transferStatus = "success";

                // Update transaction status
                $sql = "UPDATE transactions SET status = ? WHERE transactionId = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $transferStatus, $transactionId);
                $stmt->execute();

                // Update user balances
                $newAvailableWithdrawal = $availableWithdrawal - $amount;
                $newBalance = $balance - $amount;

                $sql = "UPDATE users SET available_withdrawal = ?, balance = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ddi", $newAvailableWithdrawal, $newBalance, $userId);
                $stmt->execute();

                echo "<script>alert('Transaction approved!');</script>";
            } else {
                echo "<script>alert('User not found.');</script>";
            }
        } else {
            echo "<script>alert('Transaction not found.');</script>";
        }
    } elseif ($actionType === 'decline') {
        // Decline transaction
        $sql = "UPDATE transactions SET status = 'declined' WHERE transactionId = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();

        echo "<script>alert('Transaction declined successfully.');</script>";
    } else {
        echo "<script>alert('Invalid action type.');</script>";
    }

    // Close database connection
    $conn->close();
}
?>
