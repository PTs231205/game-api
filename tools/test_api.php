<?php
/**
 * InfinityAPI - API Tester Script
 * This script tests the /v1/launch endpoint.
 */

// --- CONFIGURATION ---
$base_url = "http://127.0.0.1/v1/launch";
$host     = "visionmall.fun";
$api_key  = "inf_389cbadd6484eaf0b5db4dbdcba2ddee"; // Fetched from your database
$user_id  = "testuser1";                         // Fetched from your database
$game_uid = "viva_slots";                        // Sample Game UID (Provider dependent)

// Optional Parameters
$currency = "INR";
$language = "en";

// --- EXECUTION ---
echo "--- iGaming API Test Starting ---\n";
echo "Endpoint: $base_url (Host: $host)\n";
echo "Client API Key: $api_key\n";
echo "User ID: $user_id\n";
echo "Game UID: $game_uid\n";
echo "----------------------------------\n\n";

// Prepare Query Parameters
$params = [
    'user_id'  => $user_id,
    'game_uid' => $game_uid,
    'currency' => $currency,
    'language' => $language,
    'return_url' => 'http://visionmall.fun/v1/return'
];

$url = $base_url . '?' . http_build_query($params);

echo "Final Request URL: $url\n\n";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Host: $host",
    "X-API-KEY: $api_key"
]);

// Execute Request
$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// --- RESULTS ---
echo "HTTP Status Code: $http_code\n";
echo "Response Time: " . round(($end_time - $start_time) * 1000, 2) . "ms\n\n";

if ($error) {
    echo "CURL ERROR: $error\n";
} else {
    echo "RAW RESPONSE:\n";
    echo $response . "\n\n";
    
    $json = json_decode($response, true);
    if ($json) {
        if (isset($json['ok']) && $json['ok'] === true) {
            echo "✅ SUCCESS: API is working correctly!\n";
            echo "🔗 Game URL: " . $json['game_url'] . "\n";
        } else {
            echo "❌ API ERROR: " . ($json['error'] ?? 'Unknown error') . "\n";
            if (isset($json['provider_response'])) {
                echo "Provider Message: " . print_r($json['provider_response'], true) . "\n";
            }
        }
    } else {
        echo "❌ INVALID JSON RESPONSE. Check if the endpoint URL is correct.\n";
    }
}

echo "\n--- Test Completed ---\n";
