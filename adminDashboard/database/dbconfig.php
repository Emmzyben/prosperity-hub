<?php

$servername = "localhost"; 
$username = "cashstac_cashstack";      
$password = "cashstack1234";          
$dbname = "cashstac_cashstack"; 

try {
    $conn = @new mysqli($servername, $username, $password, $dbname);


    if ($conn->connect_errno) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

} catch (Exception $e) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();     
    }
    
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['messageType'] = "error";

    header('Location: error.php');
    exit(); 
}
?>
