<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../database/dbconfig.php';

header('Content-Type: application/json');

// Fetch the API key
$sql = "SELECT secret FROM admin";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $apiDetails = $result->fetch_assoc();
    $apiKey = $apiDetails['secret'];
} else {
    echo json_encode(['error' => 'No API key found.']);
    exit;
}
$stmt->close();

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.flutterwave.com/v3/banks/NG",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    )
));

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(['error' => 'cURL Error: ' . curl_error($curl)]);
    curl_close($curl);
    exit;
}

curl_close($curl);

// Debugging response
if (json_decode($response) === null) {
    echo json_encode(['error' => 'Invalid JSON response from API.', 'response' => $response]);
    exit;
}

// Output the JSON response
echo $response;

?>
