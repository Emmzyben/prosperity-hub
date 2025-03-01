<?php
session_start(); 


if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];  // Get the error message
    $messageType = $_SESSION['messageType']; // Get the message type (in this case, "error")
    
    // Clear the message from session after displaying it
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
} else {
    // Default message if no error is set
    $message = "An unexpected error occurred. Please try again later.";
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .error-container {
            text-align: center;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            color: #D8000C;
            background-color: #FFD2D2;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .back-button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="error-container">
        <h1>Oops! Something went wrong.</h1>
        <p>Check your internet connection or contact our customer care</p>
        <!-- Display the error message -->
        

        <a href="login.php" class="back-button" >Reload page</a>

    </div>

</body>
</html>
