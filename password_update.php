<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

include './database/dbconfig.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['userId'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate the new password
    if (empty($password) || empty($confirmPassword)) {
        $_SESSION['message'] = "Both fields are required.";
        $_SESSION['messageType'] = "danger";
    } elseif ($password !== $confirmPassword) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['messageType'] = "danger";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the update statement
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $userId);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Password updated successfully.";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Error updating password: " . $stmt->error;
            $_SESSION['messageType'] = "danger";
        }

        $stmt->close();
    }

    $conn->close();
    header('Location: pages-account-settings-account.php');
    exit();
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];

    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
} else {
    $message = '';
    $messageType = '';
}
?>
