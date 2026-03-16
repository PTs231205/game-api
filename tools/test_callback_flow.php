<?php
/**
 * InfinityAPI - End-to-end test script
 *
 * Tests:
 * 1) /v1/launch with ts+sig (HMAC)
 * 2) Provider callback simulation -> /v1/callback?client=<uuid>
 * 3) Optional: forwarding to /v1/forward-test receiver
 *
 * Usage examples:
 *   php tools/test_callback_flow.php
 *   php tools/test_callback_flow.php --client_id=client_2509cc --user_id=23213 --game_uid=3978 --balance=10
 *   php tools/test_callback_flow.php --set_forward=1
 */

function arg(string $name, $default = null) {
    global $argv;
    foreach ($argv as $a) {
        if (strpos($a, "--{$name}=") === 0) return substr($a, strlen("--{$name}="));
    }
    return $default;
}

function http_get(string $url): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $t0 = microtime(true);
    $body = curl_exec($ch);
    $ms = (int)round((microtime(true) - $t0) * 1000);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'ms' => $ms, 'err' => $err, 'body' => $body];
}

function http_post_json(string $url, array $payload): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $t0 = microtime(true);
    $body = curl_exec($ch);
    $ms = (int)round((microtime(true) - $t0) * 1000);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'ms' => $ms, 'err' => $err, 'body' => $body];
}

$base = (string)arg('base', 'https://visionmall.fun');
$clientId = (string)arg('client_id', '');
$apiKey = (string)arg('api_key', '');
$userId = (string)arg('user_id', '23213');
$gameUid = (string)arg('game_uid', '3978');
$balance = (string)arg('balance', '10');
$setForward = (string)arg('set_forward', '0') === '1';

$config = require __DIR__ . '/../config/config.php';

// DB connect
$db = $config['database'] ?? [];
$dsn = "mysql:host=" . ($db['host'] ?? '127.0.0.1') .
    ";port=" . ($db['port'] ?? 3306) .
    ";dbname=" . ($db['database'] ?? '') .
    ";charset=" . ($db['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, (string)($db['username'] ?? ''), (string)($db['password'] ?? ''), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 3,
]);

// Pick a client
if ($apiKey !== '') {
    $stmt = $pdo->prepare("SELECT uuid, client_id, api_key, client_secret, provider_config FROM clients WHERE api_key = ? LIMIT 1");
    $stmt->execute([$apiKey]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif ($clientId !== '') {
    $stmt = $pdo->prepare("SELECT uuid, client_id, api_key, client_secret, provider_config FROM clients WHERE client_id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    $stmt = $pdo->query("SELECT uuid, client_id, api_key, client_secret, provider_config FROM clients WHERE status='Active' ORDER BY created_at DESC LIMIT 1");
    $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$client) {
    fwrite(STDERR, "Client not found. Pass --client_id=... OR --api_key=...\n");
    exit(1);
}

$clientUuid = (string)($client['uuid'] ?? '');
$clientId = (string)($client['client_id'] ?? '');
$apiKey = (string)($client['api_key'] ?? '');
$clientSecret = (string)($client['client_secret'] ?? '');
$providerCfg = json_decode((string)($client['provider_config'] ?? '{}'), true);
if (!is_array($providerCfg)) $providerCfg = [];

echo "=== InfinityAPI Test Flow ===\n";
echo "Base: {$base}\n";
echo "Client UUID: {$clientUuid}\n";
echo "Client ID: {$clientId}\n";
echo "API Key: {$apiKey}\n";
echo "User ID: {$userId}\n";
echo "Game UID: {$gameUid}\n";
echo "Balance: {$balance}\n";
echo "Set Forward Callback URL: " . ($setForward ? "YES" : "NO") . "\n";
echo "----------------------------\n";

// Set forwarding to local receiver (optional)
$salt = (string)($config['security']['salt'] ?? '');
$forwardKey = $salt !== '' ? substr(hash('sha256', $salt), 0, 16) : '';
$forwardUrl = rtrim($base, '/') . '/v1/forward-test?k=' . urlencode($forwardKey);

if ($setForward) {
    $providerCfg['forward_callback_url'] = $forwardUrl;
    $stmt = $pdo->prepare("UPDATE clients SET provider_config = ? WHERE uuid = ?");
    $stmt->execute([json_encode($providerCfg), $clientUuid]);
    echo "Forward Callback URL saved: {$forwardUrl}\n";
}

// 1) Launch with signature
$ts = time();
$dataToSign = $apiKey . '|' . $userId . '|' . $gameUid . '|' . $balance . '|' . $ts;
$sig = hash_hmac('sha256', $dataToSign, $clientSecret);
$launchUrl = rtrim($base, '/') . '/v1/launch?api_key=' . urlencode($apiKey) .
    '&user_id=' . urlencode($userId) .
    '&game_uid=' . urlencode($gameUid) .
    '&balance=' . urlencode($balance) .
    '&ts=' . urlencode((string)$ts) .
    '&sig=' . urlencode($sig) .
    '&redirect=0';

echo "\n[1] Calling /v1/launch\n{$launchUrl}\n";
$launchRes = http_get($launchUrl);
echo "HTTP {$launchRes['code']} ({$launchRes['ms']}ms)\n";
if ($launchRes['err']) echo "cURL error: {$launchRes['err']}\n";
echo "Body: {$launchRes['body']}\n";

// 2) Simulate provider callback
$payload = [
    // Use same game_uid as launch so session lookup works
    'game_uid' => (string)$gameUid,
    'game_round' => (string)random_int(1000000000, 9999999999),
    'member_account' => (string)$userId,
    'bet_amount' => 3,
    'win_amount' => 0,
    'timestamp' => date('Y-m-d H:i:s'),
];

$cbUrl = rtrim($base, '/') . '/v1/callback?client=' . urlencode($clientUuid);
echo "\n[2] Simulating iGaming callback -> /v1/callback\n{$cbUrl}\n";
echo "Payload: " . json_encode($payload) . "\n";
$cbRes = http_post_json($cbUrl, $payload);
echo "HTTP {$cbRes['code']} ({$cbRes['ms']}ms)\n";
if ($cbRes['err']) echo "cURL error: {$cbRes['err']}\n";
echo "Body: {$cbRes['body']}\n";

// 3) Check forward-test receiver logs (if forward set)
if ($setForward) {
    $checkUrl = rtrim($base, '/') . '/v1/forward-test?k=' . urlencode($forwardKey);
    echo "\n[3] Checking forwarded payload receiver log\n{$checkUrl}\n";
    $fwdRes = http_get($checkUrl);
    echo "HTTP {$fwdRes['code']} ({$fwdRes['ms']}ms)\n";
    echo "Body: {$fwdRes['body']}\n";
}

echo "\n=== Done ===\n";

