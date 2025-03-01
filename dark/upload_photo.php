<?php
session_start();
include '../database/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['userId'])) {
        $userId = $_SESSION['userId'];

        // Check if a file was uploaded
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileSize = $_FILES['photo']['size'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Allowed file extensions
            $allowedfileExtensions = ['jpg', 'jpeg', 'png'];
            if (in_array($fileExtension, $allowedfileExtensions) && $fileSize <= 800 * 1024) {
                // Directory where the file will be uploaded
                $uploadFileDir = './profileImages/';
                $dest_path = $uploadFileDir . $userId . '.' . $fileExtension;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Update user photo path in the database
                    $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $dest_path, $userId);
                    $stmt->execute();

                    // Prepare success response
                    $_SESSION['message'] = 'Photo uploaded successfully.';
                    $_SESSION['messageType'] = 'success';
                } else {
                    // Prepare error response
                    $_SESSION['message'] = 'There was an error moving the uploaded file.';
                    $_SESSION['messageType'] = 'error';
                }
            } else {
                // Prepare error response
                $_SESSION['message'] = 'Upload failed. Allowed file types: jpg, jpeg, png. Max size: 800KB.';
                $_SESSION['messageType'] = 'error';
            }
        } else {
            // Prepare error response
            $_SESSION['message'] = 'No file was uploaded or there was an error uploading the file.';
            $_SESSION['messageType'] = 'error';
        }
    } else {
        // Prepare error response
        $_SESSION['message'] = 'User not logged in.';
        $_SESSION['messageType'] = 'error';
    }
} else {
    // Prepare error response
    $_SESSION['message'] = 'Invalid request method.';
    $_SESSION['messageType'] = 'error';
}

header('Location: pages-account-settings-account.php'); 
exit;

$conn->close();
