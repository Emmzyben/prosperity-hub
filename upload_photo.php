<?php
session_start();
include './database/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['userId'])) {
        $userId = $_SESSION['userId'];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileSize = $_FILES['photo']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (in_array($fileExtension, $allowedExtensions) && $fileSize <= 800 * 1024) {
                $uploadFileDir = './profileImages/';
                $dest_path = $uploadFileDir . $userId . '.' . $fileExtension;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Update profile picture
                    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->bind_param("si", $dest_path, $userId);
                    $stmt->execute();
                    
                    $_SESSION['message'] = 'Photo uploaded successfully.';
                    $_SESSION['messageType'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error moving the uploaded file.';
                    $_SESSION['messageType'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Image should be less than 800kb';
                $_SESSION['messageType'] = 'error';
            }
        } else {
            $_SESSION['message'] = 'No file was uploaded.';
            $_SESSION['messageType'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'User not logged in.';
        $_SESSION['messageType'] = 'error';
    }
    header('Location: pages-account-settings-account.php');
    exit;
}
$conn->close();
