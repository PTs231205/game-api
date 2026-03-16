<?php
$apiKey = 'AeczS70avtPkkOsy';
$clientSecret = '3ih3VKxHJ3XOd7Da';

$userId = '23213';
$gameUid = '3978';
$balance = '10';
$ts = time();

$data = $apiKey . '|' . $userId . '|' . $gameUid . '|' . $balance . '|' . $ts;
$sig = hash_hmac('sha256', $data, $clientSecret);

$url = "api.appixa.in/v1/launch?api_key={$apiKey}&user_id={$userId}&game_uid={$gameUid}&balance={$balance}&ts={$ts}&sig={$sig}";

// API call
$response = file_get_contents($url);

if ($response !== false) {
    $result = json_decode($response, true);

    if ($result['ok'] === true && isset($result['game_url'])) {
        header("Location: " . $result['game_url']);
        exit;
    } else {
        echo "Game URL not found!";
    }
} else {
    echo "API request failed!";
}
?>
