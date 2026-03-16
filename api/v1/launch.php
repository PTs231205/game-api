<?php
header('Content-Type: application/json');

// Error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$t0 = microtime(true);

// Load configuration
$config = require __DIR__ . '/../../config/config.php';

// Database connection
try {
    $dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['database']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

// Load stores and launcher
require_once __DIR__ . '/../../includes/ClientStore.php';
require_once __DIR__ . '/../../includes/UserStore.php';
require_once __DIR__ . '/../../includes/GameLauncher.php';

$clientStore = new ClientStore($pdo);
$userStore = new UserStore($pdo);

// 1. Get Parameters (Mandatory: api_key, user_id, game_uid | Optional: balance, currency_code, language)
$apiKey = $_REQUEST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
$userId = $_REQUEST['user_id'] ?? null;
$gameUid = $_REQUEST['game_uid'] ?? null;
$balanceParam = null;
foreach (['balance', 'wallet_balance', 'balance_amount'] as $k) {
    if (isset($_REQUEST[$k]) && (string)$_REQUEST[$k] !== '') {
        $balanceParam = (string)$_REQUEST[$k];
        break;
    }
}
$currencyParam = isset($_REQUEST['currency_code']) ? (string)$_REQUEST['currency_code'] : null;
$languageParam = isset($_REQUEST['language']) ? (string)$_REQUEST['language'] : null;
$returnParam = isset($_REQUEST['return']) ? (string)$_REQUEST['return'] : (isset($_REQUEST['return_url']) ? (string)$_REQUEST['return_url'] : null);
$callbackParam = isset($_REQUEST['callback']) ? (string)$_REQUEST['callback'] : (isset($_REQUEST['callback_url']) ? (string)$_REQUEST['callback_url'] : null);
$redirect = isset($_REQUEST['redirect']) && (string)$_REQUEST['redirect'] !== '0';

// Fallback: agar request index.php se include hua ho to query string REQUEST_URI se parse karo
if ((!$apiKey || !$userId || !$gameUid) && !empty($_SERVER['REQUEST_URI'])) {
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    if ($query) {
        parse_str($query, $parsed);
        if (empty($apiKey)) $apiKey = $parsed['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        if (empty($userId)) $userId = $parsed['user_id'] ?? null;
        if (empty($gameUid)) $gameUid = $parsed['game_uid'] ?? null;
        if ($balanceParam === null && isset($parsed['balance'])) $balanceParam = (string)$parsed['balance'];
        if ($currencyParam === null && isset($parsed['currency_code'])) $currencyParam = (string)$parsed['currency_code'];
        if ($languageParam === null && isset($parsed['language'])) $languageParam = (string)$parsed['language'];
    }
}
if ((!$apiKey || !$userId || !$gameUid) && !empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $parsed);
    if (empty($apiKey)) $apiKey = $parsed['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    if (empty($userId)) $userId = $parsed['user_id'] ?? null;
    if (empty($gameUid)) $gameUid = $parsed['game_uid'] ?? null;
}

// 2. Validate Mandatory Input
if (!$apiKey || !$userId || !$gameUid) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing mandatory parameters: api_key, user_id, game_uid']);
    exit;
}

// 3. Authenticate Client
$client = $clientStore->findByApiKey($apiKey);
if (!$client || $client['status'] !== 'Active') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid or Inactive API Key']);
    exit;
}

// 3.0 Simple signature (HMAC) security
// Client sends:
//   ts = unix timestamp (seconds) or ms
//   sig = hmac_sha256(client_secret, api_key|user_id|game_uid|balance|ts)
$ts = $_REQUEST['ts'] ?? null;
$sig = $_REQUEST['sig'] ?? null;
$clientSecret = (string)($client['client_secret'] ?? '');
$ipWhitelistEnabled = (int)($client['ip_whitelist_enabled'] ?? 0);

// 3.1 IP Whitelist (if configured)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $clientIp = (string)$_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
    $clientIp = trim($ips[0]);
}

$whitelistedIps = $client['ip_whitelist'] ?? [];
if (is_string($whitelistedIps)) {
    $decoded = json_decode($whitelistedIps, true);
    $whitelistedIps = is_array($decoded) ? $decoded : [];
}

if ($ipWhitelistEnabled === 1 && is_array($whitelistedIps) && count($whitelistedIps) > 0) {
    if (!in_array($clientIp, $whitelistedIps, true)) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'IP Address not whitelisted',
            'your_ip' => $clientIp,
        ]);
        exit;
    }
}

// 4. User: DB se lo. Agar user nahi mila, balance param required.
$user = $userStore->find($userId);
$balance = null;
$currencyCode = $currencyParam ?? 'INR';
$language = $languageParam ?? 'en';

// If balance is provided in URL, always use it (so user creation is not mandatory).
if ($balanceParam !== null) {
    if (!is_numeric($balanceParam)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid balance (must be numeric)']);
        exit;
    }
    $balance = (string)$balanceParam;
} elseif ($user) {
    $balance = (string)($user['balance'] ?? '0');
    if ($currencyParam === null) $currencyCode = (string)($user['currency_code'] ?? 'INR');
    if ($languageParam === null) $language = (string)($user['language'] ?? 'en');
} else {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'User not found. Pass balance in URL (e.g. &balance=40) OR create user in master panel.',
        'required' => ['balance'],
        'example' => '/v1/launch?api_key=...&user_id=23213&game_uid=3978&balance=40'
    ]);
    exit;
}

// If no signature provided, allow only when IP whitelist is configured.
// If signature is provided, verify it (works even without IP whitelist).
if ($sig === null || $sig === '' || $ts === null || $ts === '') {
    if (!($ipWhitelistEnabled === 1 && is_array($whitelistedIps) && count($whitelistedIps) > 0)) {
        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'error' => 'Missing signature. Provide ts and sig OR enable IP whitelist in master panel.',
            'required' => ['ts', 'sig'],
        ]);
        exit;
    }
} else {
    // Normalize timestamp
    $tsNum = is_numeric($ts) ? (float)$ts : null;
    if ($tsNum === null) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid ts']);
        exit;
    }
    // Accept ms or seconds
    if ($tsNum > 1000000000000) {
        $tsNum = $tsNum / 1000.0;
    }
    $now = time();
    if (abs($now - (int)$tsNum) > 120) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Signature expired']);
        exit;
    }
    if ($clientSecret === '') {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Client secret not configured']);
        exit;
    }

    $dataToSign = (string)$apiKey . '|' . (string)$userId . '|' . (string)$gameUid . '|' . (string)$balance . '|' . (string)$ts;
    $expected = hash_hmac('sha256', $dataToSign, $clientSecret);
    $sigNorm = strtolower(trim((string)$sig));
    if (!hash_equals($expected, $sigNorm)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
        exit;
    }
}

// 5. Provider credentials (per client)
$prov = $client['provider'] ?? [];
$token = is_array($prov) ? (string)($prov['token'] ?? '') : '';
$secret = is_array($prov) ? (string)($prov['secret'] ?? '') : '';
$baseUrl = is_array($prov) ? (string)($prov['server_url'] ?? 'https://igamingapis.live/api/v1') : 'https://igamingapis.live/api/v1';

if ($token === '' || $secret === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Client provider credentials not configured']);
    exit;
}

$launcher = new GameLauncher($token, $secret, $baseUrl);

// 6. Callback/Return URLs (defaults)
$appUrl = (string)($config['app']['url'] ?? 'https://visionmall.fun');
$clientUuid = (string)($client['uuid'] ?? $client['id'] ?? '');
// Provider should ALWAYS callback to our system endpoint, so we can forward to client's callback.
// Identify client using query param.
$systemCallbackUrl = $appUrl . '/v1/callback' . ($clientUuid !== '' ? ('?client=' . urlencode($clientUuid)) : '');
$finalReturnUrl = $returnParam ?: ($appUrl . '/v1/return');

// 7. Launch Game — seedha igamingapis ko bhejo
$response = $launcher->launchGame(
    $userId,
    $balance,
    $gameUid,
    $finalReturnUrl,
    $systemCallbackUrl,
    $currencyCode,
    $language
);

// 8. Return Result
if (isset($response['code']) && $response['code'] == 0 && isset($response['data']['url'])) {
    // Save launch/session in DB (so callback can update using stored balance)
    $launchRequestId = 'req_' . bin2hex(random_bytes(8));

    // Sync user balance (create/update) so callbacks can always update user wallet
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (user_id, balance, currency_code, language, created_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
              balance = VALUES(balance),
              currency_code = VALUES(currency_code),
              language = VALUES(language),
              updated_at = NOW()
        ");
        $stmt->execute([
            (string)$userId,
            is_numeric($balance) ? (float)$balance : 0.0,
            (string)$currencyCode,
            (string)$language,
        ]);
    } catch (Throwable $e) {
        // ignore
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO game_sessions
              (client_uuid, user_id, game_uid, launch_request_id, balance, currency_code, language, status)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, 'launched')
            ON DUPLICATE KEY UPDATE
              launch_request_id = VALUES(launch_request_id),
              user_id = VALUES(user_id),
              game_uid = VALUES(game_uid),
              balance = VALUES(balance),
              currency_code = VALUES(currency_code),
              language = VALUES(language),
              status = 'launched',
              updated_at = NOW()
        ");
        $stmt->execute([
            (string)($client['uuid'] ?? $client['id'] ?? ''),
            (string)$userId,
            (string)$gameUid,
            $launchRequestId,
            is_numeric($balance) ? (float)$balance : 0.0,
            (string)$currencyCode,
            (string)$language,
        ]);
    } catch (Throwable $e) {
        // ignore session save failure
    }

    // log
    try {
        $stmt = $pdo->prepare("INSERT INTO api_logs (client_uuid, endpoint, ip, user_id, game_uid, balance, ok, provider_code, provider_msg, provider_url, latency_ms) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
        $lat = (int)round((microtime(true) - $t0) * 1000);
        $stmt->execute([
            (string)($client['uuid'] ?? $client['id'] ?? ''),
            'launch',
            (string)($clientIp ?? ''),
            (string)$userId,
            (string)$gameUid,
            is_numeric($balance) ? (float)$balance : null,
            isset($response['code']) ? (int)$response['code'] : null,
            (string)($response['msg'] ?? null),
            (string)($response['data']['url'] ?? null),
            $lat,
        ]);
    } catch (Throwable $e) {
        // ignore logging failure
    }

    if ($redirect) {
        header('Location: ' . $response['data']['url'], true, 302);
        echo json_encode(['ok' => true, 'redirect' => true, 'game_url' => $response['data']['url'], 'request_id' => $launchRequestId]);
        exit;
    }
    echo json_encode(['ok' => true, 'game_url' => $response['data']['url'], 'request_id' => $launchRequestId]);
} else {
    // log
    try {
        $stmt = $pdo->prepare("INSERT INTO api_logs (client_uuid, endpoint, ip, user_id, game_uid, balance, ok, provider_code, provider_msg, provider_url, latency_ms) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)");
        $lat = (int)round((microtime(true) - $t0) * 1000);
        $stmt->execute([
            (string)($client['uuid'] ?? $client['id'] ?? ''),
            'launch',
            (string)($clientIp ?? ''),
            (string)$userId,
            (string)$gameUid,
            is_numeric($balance) ? (float)$balance : null,
            isset($response['code']) ? (int)$response['code'] : null,
            (string)($response['msg'] ?? null),
            (string)($response['data']['url'] ?? null),
            $lat,
        ]);
    } catch (Throwable $e) {
        // ignore logging failure
    }

    // Don't use HTTP 502 here, because nginx will replace response body with its own 502.html.
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'Game provider error', 'provider_response' => $response]);
}

