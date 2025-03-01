<?php
session_start();
include './database/dbconfig.php'; // Include your database configuration file
header('Content-Type: application/json');

// Initialize payment request (only for POST method)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['amount']) || !isset($input['email']) || !isset($input['user_id'])) {
        echo json_encode(['status' => false, 'message' => 'Invalid parameters']);
        exit();
    }

    $amount = (int) $input['amount'] * 100; // Convert to subunits
    $email = $input['email'];
    $txRef = $input['tx_ref'];
    $userId = $input['user_id'];

    // Fetch the Paystack secret key from the admin table in the database
    $query = "SELECT secret FROM admin LIMIT 1"; // Adjust query if needed (for example, if you need to check a specific admin)
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $secretKey = $row['secret']; // Fetch the secret key
    } else {
        echo json_encode(['status' => false, 'message' => 'Secret key not found']);
        exit();
    }

    // Pass the userId as a query parameter in the callback URL
    $callback_url = "http://cashstack.ng/callback.php?user_id=" . $userId;

    // Step 2: Store userId in session for later use during the callback
    $_SESSION['payment'] = [
        'user_id' => $userId,
    ];

    $url = "https://api.paystack.co/transaction/initialize";

    $fields = [
        'email' => $email,
        'amount' => $amount,
        'reference' => $txRef,
        'callback_url' => $callback_url, // Include userId in the callback URL
    ];

    $fields_string = http_build_query($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secretKey", // Use the secret key from the database
        "Cache-Control: no-cache",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo $response; // Return the response from Paystack
    } else {
        echo json_encode(['status' => false, 'message' => 'Unable to initialize transaction']);
    }
    exit();
}
