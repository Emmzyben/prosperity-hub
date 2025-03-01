<?php
session_start(); 

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['amount']) || !isset($input['email']) || !isset($input['user_id'])) {
  echo json_encode(['status' => false, 'message' => 'Invalid parameters']);
  exit();
}

$amount = (int) $input['amount'] * 100; // Convert to subunits
$email = $input['email'];
$txRef = $input['tx_ref'];
$userId = $input['user_id']; // Get the user ID
$callback_url = "http://cashstack.ng/dark/callback.php";

// Store amount and user ID in the session
$_SESSION['payment'] = [
  'amount' => $input['amount'], // Store original amount for easy retrieval
  'user_id' => $userId,
];

$url = "https://api.paystack.co/transaction/initialize";

$fields = [
  'email' => $email,
  'amount' => $amount,
  'reference' => $txRef,
  'callback_url' => $callback_url,
];

$fields_string = http_build_query($fields);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer sk_live_63154c8a0c39934a8a024302b80b149d38236f38", // Replace with your secret key
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
?>
