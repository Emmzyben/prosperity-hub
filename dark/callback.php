<?php
session_start(); // Start the session to access stored data

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    $url = "https://api.paystack.co/transaction/verify/" . $reference;

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer sk_live_63154c8a0c39934a8a024302b80b149d38236f38", // Replace with your secret key
        "Cache-Control: no-cache",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($result && $result['status'] && $result['data']['status'] === 'success') {
        // Retrieve user ID and amount from session
        $userId = $_SESSION['payment']['user_id'];
        $amount = $_SESSION['payment']['amount'];

        // Clear the session data
        unset($_SESSION['payment']);

        // Redirect to pay.php with user_id and amount as query parameters
        header("Location: pay.php?user_id=$userId&amount=$amount");
        exit();
    } else {
        // Redirect to fund.php if payment verification fails
        header("Location: fund.php?error=Transaction+failed");
        exit();
    }
} else {
    // Redirect to fund.php if no transaction reference is provided
    header("Location: fund.php?error=No+transaction+reference+provided");
    exit();
}
