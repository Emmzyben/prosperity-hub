<?php
include './database/dbconfig.php';

// Define the daily percentage (1.49%)
$dailyPercentage = 1 / 100; // 1% (adjusted to achieve 30% monthly return)

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

            // Calculate 30% of the total investment (this is the target profit)
            $targetProfit = $totalInvestment * 0.30;

            // Fetch the current profit from the users table
            $sqlProfit = "SELECT profit FROM users WHERE id = ?";
            $stmtProfit = $conn->prepare($sqlProfit);
            $stmtProfit->bind_param("i", $userId);
            $stmtProfit->execute();
            $stmtProfit->bind_result($currentProfit);
            $stmtProfit->fetch();
            $stmtProfit->close();

            // If the current profit is already at or above 30%, skip this user
            if ($currentProfit >= $targetProfit) {
                echo "User ID $userId has reached the target profit of 30%. No further updates.\n";
                continue; // Skip this user
            }

            // Calculate the daily profit based on the user's total investment
            $dailyProfit = $totalInvestment * $dailyPercentage;

            // If adding the daily profit would exceed the target, adjust the profit to match the target
            if ($currentProfit + $dailyProfit > $targetProfit) {
                $dailyProfit = $targetProfit - $currentProfit;
            }

            // Update the user's balance and profit in the users table
            $updateSql = "UPDATE users 
                          SET profit = profit + ? 
                          WHERE id = ?";
            $stmt = $conn->prepare($updateSql);

            if ($stmt) {
                $stmt->bind_param("di", $dailyProfit, $userId);

                if (!$stmt->execute()) {
                    // If there is an error, throw an exception to rollback
                    throw new Exception("Error updating user ID $userId: " . $stmt->error);
                }

                echo "Updated user ID $userId: +$dailyProfit added to and profit.\n";
                $stmt->close();
            } else {
                // If there's a problem preparing the statement, throw an exception
                throw new Exception("Error preparing statement for user ID $userId: " . $conn->error);
            }
        }
    } else {
        echo "No active investments found.\n";
    }

    // Commit the transaction if everything was successful
    $conn->commit();
} catch (Exception $e) {
    // In case of an error, rollback the transaction
    $conn->rollback();

    // Display the error message
    echo "Transaction failed: " . $e->getMessage() . "\n";
}

// Close the connection
$conn->close();
?>
