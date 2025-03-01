<?php
// Database configuration
include '../database/dbconfig.php';

// Select all users from the users table
$sql = "SELECT id, balance FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Loop through each user 
    while ($user = $result->fetch_assoc()) {
        $userId = $user['id'];
        $balance = $user['balance'];

        // Calculate 25% of the user's balance 
        $weeklyWithdrawalAmount = $balance * 0.25;

        // Update the available_withdrawals field in the users table
        $updateSql = "UPDATE users SET available_withdrawals = available_withdrawals + ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("di", $weeklyWithdrawalAmount, $userId);
        
        if ($stmt->execute()) {
            echo "User ID $userId: Available withdrawals updated successfully.\n";
        } else {
            echo "User ID $userId: Error updating available withdrawals - " . $stmt->error . "\n";
        }
        
        $stmt->close();
    }
} else {
    echo "No users found in the database.";
}

// Close the database connection
$conn->close();
?>
