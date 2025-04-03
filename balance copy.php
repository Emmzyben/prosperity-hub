<?php
include './database/dbconfig.php';

// Define the daily percentage (1%)
$dailyPercentage = 1 / 100;

// Start a transaction
$conn->begin_transaction();

try {
    // Fetch all user investments by matching the `id` field
    $sql = "SELECT id, SUM(package_amount) AS total_investment 
            FROM investment 
            GROUP BY id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $userId = $row['id'];
            $totalInvestment = $row['total_investment'];

            // Calculate 30% of the total investment (target profit)
            $targetProfit = $totalInvestment * 0.30;

            // Fetch the current profit and withdrawal count
            $sqlProfit = "SELECT profit, withdrawal_count FROM users WHERE id = ?";
            $stmtProfit = $conn->prepare($sqlProfit);
            $stmtProfit->bind_param("i", $userId);
            $stmtProfit->execute();
            $stmtProfit->bind_result($currentProfit, $withdrawal_count);
            $stmtProfit->fetch();
            $stmtProfit->close();

            // If the current profit is already at or above 30%, clear investments and reset fields
            if ($currentProfit >= $targetProfit) {
                if ($withdrawal_count === 0) {
                    // Clear investments for this user
                    $deleteInvestmentsSql = "DELETE FROM investment WHERE id = ?";
                    $stmtDeleteInvestments = $conn->prepare($deleteInvestmentsSql);
                    $stmtDeleteInvestments->bind_param("i", $userId);

                    if (!$stmtDeleteInvestments->execute()) {
                        throw new Exception("Error clearing investments for user ID $userId: " . $stmtDeleteInvestments->error);
                    }
                    $stmtDeleteInvestments->close();

                    // Reset both profit and referalBonus fields for this user
                    $resetFieldsSql = "UPDATE users SET profit = 0, referalBonus = 0 WHERE id = ?";
                    $stmtResetFields = $conn->prepare($resetFieldsSql);
                    $stmtResetFields->bind_param("i", $userId);

                    if (!$stmtResetFields->execute()) {
                        throw new Exception("Error resetting fields for user ID $userId: " . $stmtResetFields->error);
                    }
                    $stmtResetFields->close();

                    echo "User ID $userId has reached the target profit of 30%. Investments cleared, and fields reset.\n";
                }
                continue; 
            }

            // Calculate the daily profit based on the user's total investment
            $dailyProfit = $totalInvestment * $dailyPercentage;

            // If adding the daily profit would exceed the target, adjust it
            if ($currentProfit + $dailyProfit > $targetProfit) {
                $dailyProfit = $targetProfit - $currentProfit;
            }

            // Update the user's profit in the users table
            $updateSql = "UPDATE users SET profit = profit + ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);

            if ($stmt) {
                $stmt->bind_param("di", $dailyProfit, $userId);

                if (!$stmt->execute()) {
                    throw new Exception("Error updating user ID $userId: " . $stmt->error);
                }

                echo "Updated user ID $userId: +$dailyProfit added to profit.\n";
                $stmt->close();
            } else {
                throw new Exception("Error preparing statement for user ID $userId: " . $conn->error);
            }
        }
    } else {
        echo "No active investments found.\n";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo "Transaction failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
