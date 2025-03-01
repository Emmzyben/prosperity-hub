<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$accountNumber = $input['account_number'] ?? '';
$bankCode = $input['bank_code'] ?? '';

if (!$accountNumber || !$bankCode) {
    echo json_encode(['error' => 'Bank code or account number is missing.']);
    exit;
}

// Paystack API credentials
$secretKey = "sk_test_3890ee4913bf33ee148324fad87e09f2790da6a9"; // Replace with your Paystack secret key

// Prepare the API URL
$url = "https://api.paystack.co/bank/resolve?account_number=$accountNumber&bank_code=$bankCode";

// Call Paystack API
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ),
));

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(['error' => curl_error($curl)]);
    curl_close($curl);
    exit;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Handle API response
if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data['status'] === true) {
        echo json_encode(['data' => $data['data']]);
    } else {
        echo json_encode(['error' => $data['message'] ?? 'Unable to resolve account.']);
    }
} else {
    echo json_encode(['error' => 'Failed to resolve account.']);
}
?>
