<?php
include './database/dbconfig.php';

// Start a transaction
$conn->begin_transaction();

try {
    // Fetch all user investments by matching the `user_id` field (not `id`)
    $sql = "SELECT user_id, SUM(package_amount) AS total_investment 
            FROM investment 
            GROUP BY user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $userId = $row['user_id'];
            $totalInvestment = $row['total_investment'];

            // Determine investment plan, daily percentage, and duration
            if ($totalInvestment >= 100 && $totalInvestment <= 4999) {
                $dailyPercentage = 2 / 100; // 2% for Basic Plan
                $profitLimit = $totalInvestment * 0.12; // 12% profit limit (2% * 6 days)
            } elseif ($totalInvestment >= 5000 && $totalInvestment <= 14999) {
                $dailyPercentage = 2.5 / 100; // 2.5% for Intermediate Plan
                $profitLimit = $totalInvestment * 0.15; // 15% profit limit (2.5% * 6 days)
            } elseif ($totalInvestment >= 15000 && $totalInvestment <= 59999) {
                $dailyPercentage = 3 / 100; // 3% for Professional Plan
                $profitLimit = $totalInvestment * 0.30; // 30% profit limit (3% * 10 days)
            } elseif ($totalInvestment >= 60000) {
                $dailyPercentage = 4 / 100; // 4% for Expert Plan
                $profitLimit = $totalInvestment * 3.12; // 312% profit limit (4% * 78 days)
            } else {
                continue; // Skip if investment is not in any plan
            }

            // Fetch the current profit and balance
            $sqlProfit = "SELECT profit, balance FROM users WHERE id = ?";
            $stmtProfit = $conn->prepare($sqlProfit);
            $stmtProfit->bind_param("i", $userId);
            $stmtProfit->execute();
            $stmtProfit->bind_result($currentProfit, $balance);
            $stmtProfit->fetch();
            $stmtProfit->close();

            // If the current profit has reached the plan's limit, clear investments and reset fields
            if ($currentProfit >= $profitLimit) {
                // Clear investments for this user
                $deleteInvestmentsSql = "DELETE FROM investment WHERE user_id = ?";
                $stmtDeleteInvestments = $conn->prepare($deleteInvestmentsSql);
                $stmtDeleteInvestments->bind_param("i", $userId);

                if (!$stmtDeleteInvestments->execute()) {
                    throw new Exception("Error clearing investments for user ID $userId: " . $stmtDeleteInvestments->error);
                }
                $stmtDeleteInvestments->close();

                // Reset profit and referral bonus fields
                $resetFieldsSql = "UPDATE users SET profit = 0, referalBonus = 0 WHERE id = ?";
                $stmtResetFields = $conn->prepare($resetFieldsSql);
                $stmtResetFields->bind_param("i", $userId);

                if (!$stmtResetFields->execute()) {
                    throw new Exception("Error resetting fields for user ID $userId: " . $stmtResetFields->error);
                }
                $stmtResetFields->close();

                echo "User ID $userId has reached the profit limit of {$profitLimit}%. Investments cleared, and fields reset.\n";
                continue;
            }

            // Calculate the daily profit based on the user's total investment
            $dailyProfit = $totalInvestment * $dailyPercentage;

            // If adding the daily profit would exceed the limit, adjust it
            if ($currentProfit + $dailyProfit > $profitLimit) {
                $dailyProfit = $profitLimit - $currentProfit;
            }

            // Update the user's profit and balance
            $updateSql = "UPDATE users SET profit = profit + ?, balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);

            if ($stmt) {
                $stmt->bind_param("ddi", $dailyProfit, $dailyProfit, $userId);

                if (!$stmt->execute()) {
                    throw new Exception("Error updating user ID $userId: " . $stmt->error);
                }

                echo "Updated user ID $userId: +$dailyProfit added to profit and balance.\n";
                $stmt->close();
            } else {
                throw new Exception("Error preparing statement for user ID $userId: " . $conn->error);
            }
        }
    } else {
        echo "No active investments found.\n";
    }

    // Commit the transaction
    $conn->commit();
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    echo "Transaction failed: " . $e->getMessage() . "\n";
}

// Close the connection
$conn->close();
?>
