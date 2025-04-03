<?php
function handleWithdrawal($transactionId, $actionType)
{
    include './database/dbconfig.php';

    // Start transaction
    $conn->begin_transaction();

    try {
        if ($actionType === 'approve') {
            // Fetch withdrawal amount
            $sql = "SELECT amount FROM withdrawal WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $transactionId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to fetch withdrawal amount");
            }

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if (!$row || !isset($row['amount'])) {
                throw new Exception("Withdrawal record not found");
            }

            $withdrawalAmount = floatval($row['amount']);

            // Get current user balance
            $userIdSql = "SELECT user_id FROM withdrawal WHERE id = ?";
            $userIdStmt = $conn->prepare($userIdSql);
            $userIdStmt->bind_param("i", $transactionId);
            
            if (!$userIdStmt->execute()) {
                throw new Exception("Failed to fetch user ID");
            }

            $userIdResult = $userIdStmt->get_result();
            $userIdRow = $userIdResult->fetch_assoc();
            
            if (!$userIdRow || !isset($userIdRow['user_id'])) {
                throw new Exception("User ID not found");
            }

            $userId = $userIdRow['user_id'];

            // Check and update balance
            $balanceSql = "SELECT balance FROM users WHERE id = ?";
            $balanceStmt = $conn->prepare($balanceSql);
            $balanceStmt->bind_param("i", $userId);

            if (!$balanceStmt->execute()) {
                throw new Exception("Failed to fetch user balance");
            }

            $balanceResult = $balanceStmt->get_result();
            $balanceRow = $balanceResult->fetch_assoc();

            if (!$balanceRow || !isset($balanceRow['balance'])) {
                throw new Exception("User balance not found");
            }

            $currentBalance = floatval($balanceRow['balance']);
            
            if ($currentBalance < $withdrawalAmount) {
                throw new Exception("Insufficient balance");
            }

            // Update balance
            $newBalance = $currentBalance - $withdrawalAmount;
            $updateBalanceSql = "UPDATE users SET balance = ? WHERE id = ?";
            $updateBalanceStmt = $conn->prepare($updateBalanceSql);
            $updateBalanceStmt->bind_param("di", $newBalance, $userId);

            if (!$updateBalanceStmt->execute()) {
                throw new Exception("Failed to update balance");
            }

            // Update withdrawal status
            $withdrawalStatus = "approved";
            $transactionStatus = "success";

            $sql = "UPDATE withdrawal SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $withdrawalStatus, $transactionId);
            $stmt->execute();

            // Update transaction status
            $sql = "UPDATE transactions SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $transactionStatus, $transactionId);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            echo "<script>alert('Transaction approved and balance updated!');</script>";
        } elseif ($actionType === 'decline') {
            // Decline transaction
            $sql = "UPDATE transactions SET status = 'pending' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            echo "<script>alert('Transaction declined successfully.');</script>";
        } else {
            echo "<script>alert('Invalid action type.');</script>";
        }
    } catch (Exception $e) {
        // Rollback transaction if any error occurs
        $conn->rollback();
        echo "<script>alert('Error processing transaction: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }

    // Close database connection
    $conn->close();
}
?>
