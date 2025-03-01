<?php
session_start();

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

include './database/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user ID from the form
    $userId = $_POST['userId'];

    // Prepare and execute the delete query
    $deleteSql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Optionally: Destroy the session if the logged-in user is deleting their own account
        if ($_SESSION['userId'] == $userId) {
            session_destroy();
        }
        echo "Account deleted successfully.";
        header('Location: login.php'); // Redirect to the homepage or a confirmation page
        exit();
    } else {
        echo "Error deleting account: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
