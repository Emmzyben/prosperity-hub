<?php
include './database/dbconfig.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Select all users with active investments
$sql = "SELECT u.id, u.available_withdrawal, u.withdrawal_count, u.profit, u.referalBonus, i.package_amount 
        FROM users u
        LEFT JOIN investment i ON u.id = i.id
        WHERE i.package_amount > 0";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Loop through each user
    while ($user = $result->fetch_assoc()) {
        $userId = $user['id'];
        $packageAmount = $user['package_amount'] ?? 0;
        $withdrawalCount = $user['withdrawal_count'] ?? 0;
        $profit = $user['profit'] ?? 0;
        $referalBonus = $user['referalBonus'] ?? 0;

        // Weekly 25% addition and 7.5% profit increment
        if ($withdrawalCount < 4) {
            $weeklyWithdrawalAmount = $packageAmount * 0.25;
            $profitIncrement = $packageAmount * 0.075;

            // Update available withdrawal, profit, and increment withdrawal count
            $updateSql = "UPDATE users 
                          SET available_withdrawal = available_withdrawal + ?, 
                              profit = profit + ?, 
                              withdrawal_count = withdrawal_count + 1 
                          WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("ddi", $weeklyWithdrawalAmount, $profitIncrement, $userId);

            if ($stmt->execute()) {
                echo "User ID $userId: Weekly withdrawal and profit increment added, withdrawal count incremented.\n";
            } else {
                echo "User ID $userId: Error updating available withdrawal, profit, and withdrawal count - " . $stmt->error . "\n";
            }
            $stmt->close();
        }

        // After 4 withdrawals, add profit and referral bonus, delete investment, reset profit and referral bonus, and reset withdrawal count
        if ($withdrawalCount == 4) {
            $totalAddition = $profit + $referalBonus;

            // Start transaction to ensure atomicity
            $conn->begin_transaction();

            try {
                // Update available withdrawal, reset profit, reset referral bonus, and reset withdrawal count
                $updateSql = "UPDATE users 
                              SET available_withdrawal = available_withdrawal + ?, 
                                  profit = 0, 
                                  referalBonus = 0, 
                                  withdrawal_count = 0 
                              WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("di", $totalAddition, $userId);
                $stmt->execute();
                $stmt->close();

                // Delete investment for the user
                $deleteSql = "DELETE FROM investment WHERE id = ?";
                $stmt = $conn->prepare($deleteSql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();

                // Commit the transaction
                $conn->commit();
                echo "User ID $userId: Profit and referral bonus added, investment deleted, profit and referral bonus reset, withdrawal count reset.\n";
            } catch (Exception $e) {
                // Rollback the transaction in case of an error
                $conn->rollback();
                echo "User ID $userId: Error during 4th withdrawal processing - " . $e->getMessage() . "\n";
            }
        }
    }
} else {
    echo "No users found with investments in the database.\n";
}

// Close the database connection
$conn->close();
?>
