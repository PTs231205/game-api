<?php
// Main Entry Point

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Base Directories
define('BASE_PATH', __DIR__ . '/');

// Load Config
$config = require BASE_PATH . 'config/config.php';

// Autoloader
spl_autoload_register(function ($class) {
    if (file_exists(BASE_PATH . 'includes/' . $class . '.php')) {
        require BASE_PATH . 'includes/' . $class . '.php';
    }
});

// Initialize Components
// Language Manager expects base dir for languages, now at BASE_PATH . 'languages'
$langManager = new LanguageManager(BASE_PATH . 'languages');
$gameManager = new GameManager($config);
$router = new Router();

// --- Auth helpers (very simple) ---
$requireClient = function() {
    if (!isset($_SESSION['client_auth']) || !is_array($_SESSION['client_auth'])) {
        header('Location: /login');
        exit;
    }
};
$requireMaster = function() {
    if (empty($_SESSION['master_auth'])) {
        header('Location: /master/login');
        exit;
    }
};

// Define Routes
$router->add('GET', '/', function() use ($langManager, $gameManager, $config, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/dashboard.php';
});

$router->add('GET', '/wallet', function() use ($langManager, $config, $gameManager, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/wallet.php';
});

$router->add('GET', '/tokens', function() use ($langManager, $config, $gameManager, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/tokens.php';
});

$router->add('GET', '/login', function() {
    require BASE_PATH . 'views/login.php';
});

$router->add('POST', '/login', function() use ($config) {
    require_once BASE_PATH . 'includes/ClientStore.php';
    
    // Init DB Connection specifically for login check
    // Ideally this should be centralized but doing it here for speed
    $dbConfig = $config['database'];
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database Error");
    }

    $store = new ClientStore($pdo);

    $clientId = (string)($_POST['client_id'] ?? '');
    $accessKey = (string)($_POST['access_key'] ?? '');

    $client = $store->findByClientId(trim($clientId));
    if (!$client || ($client['status'] ?? 'Active') !== 'Active') {
        header('Location: /login?err=invalid');
        exit;
    }

    if (!password_verify($accessKey, (string)($client['password_hash'] ?? ''))) {
        header('Location: /login?err=invalid');
        exit;
    }

    $_SESSION['client_auth'] = [
        'id' => $client['id'] ?? null,
        'client_id' => $client['client_id'] ?? null,
        'name' => $client['name'] ?? null,
        'api_key' => $client['api_key'] ?? null,
    ];

    header('Location: /');
    exit;
});

$router->add('GET', '/logout', function() {
    unset($_SESSION['client_auth']);
    header('Location: /login');
    exit;
});

$router->add('GET', '/logs', function() use ($langManager, $gameManager, $config, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/logs.php';
});

$router->add('GET', '/docs', function() use ($langManager, $gameManager, $config, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/docs.php';
});

$router->add('GET', '/tester', function() use ($langManager, $config, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/tester.php';
});

$router->add('GET', '/ip-whitelist', function() use ($langManager, $gameManager, $config, $requireClient) {
    $requireClient();
    require BASE_PATH . 'views/ip_whitelist.php';
});

// JSON API for Dashboard (AJAX)
$router->add('GET', '/api/provider/games', function() use ($gameManager, $requireClient) {
    if (!isset($_SESSION['client_auth'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'auth_required']);
        exit;
    }
    
    $providerName = $_GET['provider'] ?? '';
    $games = $gameManager->getGamesByProvider($providerName);
    
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'provider' => $providerName, 'games' => $games]);
    exit;
});

// --- Public API for Game List / Providers List (Authenticated by API Key) ---
$router->add('GET', '/api/v1/providers', function() use ($gameManager, $config) {
    header('Content-Type: application/json');
    
    // Simple API Key Check (from query param)
    $apiKey = $_GET['api_key'] ?? '';
    // In a real scenario, validate API Key against Clients DB.
    // For now, assuming if they have a valid key they can access.
    // Ideally we should instantiate ClientStore and check. But let's assume open or check existing session first.
    
    // Actually, let's implement basic check:
    require_once BASE_PATH . 'includes/ClientStore.php';
    $db = $config['database'];
    try {
         $pdo = new PDO("mysql:host={$db['host']};dbname={$db['database']}", $db['username'], $db['password']);
         $clientStore = new ClientStore($pdo);
         $client = $clientStore->findByApiKey($apiKey);
         if (!$client || $client['status'] !== 'Active') {
             echo json_encode(['ok' => false, 'error' => 'invalid_api_key']);
             exit;
         }
    } catch (Exception $e) {
         echo json_encode(['ok' => false, 'error' => 'server_error']);
         exit;
    }

    $providers = $gameManager->getProvidersList();
    echo json_encode(['ok' => true, 'count' => count($providers), 'providers' => $providers]);
    exit;
});

$router->add('GET', '/api/v1/games', function() use ($gameManager, $config) {
    header('Content-Type: application/json');

    $apiKey = $_GET['api_key'] ?? '';
    require_once BASE_PATH . 'includes/ClientStore.php';
    $db = $config['database'];
    try {
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['database']}", $db['username'], $db['password']);
        $clientStore = new ClientStore($pdo);
        $client = $clientStore->findByApiKey($apiKey);
        if (!$client || $client['status'] !== 'Active') {
            echo json_encode(['ok' => false, 'error' => 'invalid_api_key']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'server_error']);
        exit;
    }

    $provider = $_GET['provider'] ?? null;
    $games = $gameManager->getGamesList($provider);
    echo json_encode(['ok' => true, 'count' => count($games), 'games' => $games]);
    exit;
});

// --- Master Panel Routes ---
$router->add('GET', '/master/login', function() {
    require BASE_PATH . 'views/master/login.php';
});

$router->add('POST', '/master/login', function() {
    $creds = require BASE_PATH . 'config/master.php';
    $u = (string)($creds['username'] ?? '');
    $hash = (string)($creds['password_hash'] ?? '');

    $inU = (string)($_POST['username'] ?? '');
    $inP = (string)($_POST['password'] ?? '');

    if ($inU === $u && $hash !== '' && password_verify($inP, $hash)) {
        $_SESSION['master_auth'] = true;
        header('Location: /master/dashboard');
        exit;
    }

    header('Location: /master/login?err=invalid');
    exit;
});

$router->add('GET', '/master/logout', function() {
    unset($_SESSION['master_auth']);
    header('Location: /master/login');
    exit;
});

$router->add('GET', '/master/dashboard', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/dashboard.php';
});

$router->add('GET', '/master/reports', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/reports.php';
});

$router->add('GET', '/master/clients', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/clients.php';
});

$router->add('POST', '/master/clients/create', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $res = $masterManager->createClient(
        (string)($_POST['name'] ?? ''),
        (string)($_POST['client_id'] ?? ''),
        (string)($_POST['access_key'] ?? ''),
        (string)($_POST['provider_token'] ?? ''),
        (string)($_POST['provider_secret'] ?? ''),
        (string)($_POST['status'] ?? 'Active'),
        (string)($_POST['ip_whitelist'] ?? '')
    );

    if (($res['ok'] ?? false) === true) {
        // Show credentials once in UI (no URL params)
        $c = $res['client'] ?? [];
        $_SESSION['flash_client_creds'] = [
            'client_id' => $c['client_id'] ?? null,
            'api_key' => $c['api_key'] ?? null,
            'client_secret' => $c['client_secret'] ?? null,
            'access_key' => (string)($_POST['access_key'] ?? ''),
        ];
        header('Location: /master/clients?ok=created');
        exit;
    }
    $err = urlencode((string)($res['error'] ?? 'create_failed'));
    header('Location: /master/clients?err=' . $err);
    exit;
});

$router->add('POST', '/master/clients/quick-create', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $short = bin2hex(random_bytes(3)); // 6 chars
    $name = 'Client ' . strtoupper($short);
    $clientId = 'client_' . $short;
    $accessKey = 'AK_' . bin2hex(random_bytes(6));
    $status = 'Active';
    $ipWhitelist = '';

    // Provider creds fallback: config.php -> games.php
    $providerToken = (string)($config['game_provider']['token'] ?? '');
    $providerSecret = (string)($config['game_provider']['secret'] ?? '');

    if ($providerToken === '' || $providerSecret === '' || $providerSecret === '********************************') {
        $gamesCfg = require BASE_PATH . 'config/games.php';
        $providerToken = (string)($gamesCfg['providers']['default']['api_token'] ?? $providerToken);
        $providerSecret = (string)($gamesCfg['providers']['default']['api_secret'] ?? $providerSecret);
    }

    if ($providerToken === '' || $providerSecret === '' || $providerSecret === '********************************') {
        header('Location: /master/clients?err=missing_provider_creds');
        exit;
    }

    $res = $masterManager->createClient(
        $name,
        $clientId,
        $accessKey,
        $providerToken,
        $providerSecret,
        $status,
        $ipWhitelist
    );

    if (($res['ok'] ?? false) === true) {
        $_SESSION['flash_client_creds'] = [
            'client_id' => $clientId,
            'api_key' => ($res['client']['api_key'] ?? null),
            'client_secret' => ($res['client']['client_secret'] ?? null),
            'access_key' => $accessKey,
        ];
        header('Location: /master/clients?ok=created');
        exit;
    }

    $err = urlencode((string)($res['error'] ?? 'create_failed'));
    header('Location: /master/clients?err=' . $err);
    exit;
});

$router->add('POST', '/master/clients/delete', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    $res = $masterManager->deleteClient((string)($_POST['id'] ?? ''));
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=deleted' : 'err=delete_failed'));
    exit;
});

$router->add('POST', '/master/clients/rotate-key', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    $res = $masterManager->rotateClientApiKey((string)($_POST['id'] ?? ''));
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=rotated' : 'err=rotate_failed'));
    exit;
});

$router->add('POST', '/master/clients/ip-whitelist', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    $res = $masterManager->updateClientIpWhitelist(
        (string)($_POST['id'] ?? ''),
        (string)($_POST['ip_whitelist'] ?? '')
    );
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=ip_whitelist_updated' : 'err=ip_whitelist_failed'));
    exit;
});

$router->add('POST', '/master/clients/ip-whitelist-toggle', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    $id = (string)($_POST['id'] ?? '');
    $enabled = (string)($_POST['enabled'] ?? '0');
    $res = $masterManager->setClientIpWhitelistEnabled($id, $enabled === '1');
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=ip_whitelist_toggled' : 'err=ip_whitelist_toggle_failed'));
    exit;
});

$router->add('POST', '/master/clients/balances', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $id = (string)($_POST['id'] ?? '');
    $wallet = $_POST['wallet_balance'] ?? 0;
    $ggr = $_POST['ggr_balance'] ?? 0;

    $res = $masterManager->updateClientBalances($id, $wallet, $ggr);
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=balances_updated' : 'err=balances_failed'));
    exit;
});

$router->add('POST', '/master/clients/forward-callback', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $id = (string)($_POST['id'] ?? '');
    $url = (string)($_POST['url'] ?? '');

    $res = $masterManager->setClientForwardCallbackUrl($id, $url);
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=forward_callback_saved' : 'err=forward_callback_failed'));
    exit;
});

$router->add('POST', '/master/clients/providers', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $id = (string)($_POST['id'] ?? '');
    // In UI, we check boxes to ENABLE.
    // If checked, it's sent in $_POST['providers'].
    // If NOT checked, it's NOT sent.
    
    // We want to store BLOCKED providers (those NOT checked).
    // So we need list of ALL providers first.
    
    $allProviders = $masterManager->getProviders();
    $allBrandIds = array_column($allProviders, 'brand_id');
    
    $enabledBrandIds = $_POST['providers'] ?? [];
    if (!is_array($enabledBrandIds)) $enabledBrandIds = [];
    
    // Blocked = All - Enabled
    $blockedBrandIds = array_values(array_diff($allBrandIds, $enabledBrandIds));
    
    $res = $masterManager->updateClientBlockedProviders($id, $blockedBrandIds);
    header('Location: /master/clients?' . (($res['ok'] ?? false) ? 'ok=providers_updated' : 'err=providers_failed'));
    exit;
});

$router->add('POST', '/master/clients/reset-access-key', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $res = $masterManager->resetClientAccessKey((string)($_POST['id'] ?? ''));
    if (($res['ok'] ?? false) === true) {
        $_SESSION['flash_client_creds'] = [
            'client_id' => $res['client_id'] ?? null,
            'api_key' => $res['api_key'] ?? null,
            'access_key' => $res['access_key'] ?? null,
        ];
        header('Location: /master/clients?ok=access_key_reset');
        exit;
    }

    $err = urlencode((string)($res['error'] ?? 'reset_failed'));
    header('Location: /master/clients?err=' . $err);
    exit;
});

$router->add('GET', '/master/logs', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/logs.php';
});

$router->add('GET', '/master/users', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/users.php';
});

$router->add('GET', '/master/users/new', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/users_new.php';
});

$router->add('POST', '/master/users/create', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $userId = $_POST['user_id'] ?? '';
    $balance = $_POST['balance'] ?? '';
    $currencyCode = $_POST['currency_code'] ?? null;
    $language = $_POST['language'] ?? null;
    $redirectTo = (string)($_POST['redirect_to'] ?? '');

    $res = $masterManager->createUser((string)$userId, $balance, $currencyCode ? (string)$currencyCode : null, $language ? (string)$language : null);
    if (($res['ok'] ?? false) === true) {
        if ($redirectTo === 'new_tab') {
            header('Location: /master/users/new?ok=created');
        } else {
            header('Location: /master/users?ok=created');
        }
        exit;
    }
    $err = urlencode((string)($res['error'] ?? 'create_failed'));
    if ($redirectTo === 'new_tab') {
        header('Location: /master/users/new?err=' . $err);
    } else {
        header('Location: /master/users?err=' . $err);
    }
    exit;
});

$router->add('POST', '/master/users/delete', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $userId = $_POST['user_id'] ?? '';
    $res = $masterManager->deleteUser((string)$userId);
    if (($res['ok'] ?? false) === true) {
        header('Location: /master/users?ok=deleted');
        exit;
    }
    $err = urlencode((string)($res['error'] ?? 'delete_failed'));
    header('Location: /master/users?err=' . $err);
    exit;
});

// --- Payment Methods Routes ---
$router->add('GET', '/master/settings', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    require BASE_PATH . 'views/master/settings.php';
});

$router->add('POST', '/master/payment-methods/create', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    // Handle File Upload
    $qrImage = '';
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = BASE_PATH . 'public/uploads/qr/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $filename = uniqid('qr_') . '.' . $ext;
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $uploadDir . $filename)) {
                $qrImage = '/uploads/qr/' . $filename;
            }
        }
    }

    $res = $masterManager->createPaymentMethod(
        $_POST['name'] ?? '',
        $_POST['type'] ?? '',
        $_POST['address'] ?? '',
        $qrImage,
        $_POST['instructions'] ?? '',
        $_POST['status'] ?? 'Active'
    );

    header('Location: /master/settings?' . (($res['ok'] ?? false) ? 'ok=payment_created' : 'err=payment_create_failed'));
    exit;
});

$router->add('POST', '/master/payment-methods/update', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);

    $id = $_POST['id'] ?? '';
    
    // Handle File Upload
    $qrImage = null; // null means no change
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = BASE_PATH . 'public/uploads/qr/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $filename = uniqid('qr_') . '.' . $ext;
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $uploadDir . $filename)) {
                $qrImage = '/uploads/qr/' . $filename;
            }
        }
    }

    $res = $masterManager->updatePaymentMethod(
        $id,
        $_POST['name'] ?? '',
        $_POST['type'] ?? '',
        $_POST['address'] ?? '',
        $qrImage,
        $_POST['instructions'] ?? '',
        $_POST['status'] ?? 'Active'
    );

    header('Location: /master/settings?' . (($res['ok'] ?? false) ? 'ok=payment_updated' : 'err=payment_update_failed'));
    exit;
});

$router->add('POST', '/master/payment-methods/delete', function() use ($config, $requireMaster) {
    $requireMaster();
    require_once BASE_PATH . 'includes/MasterManager.php';
    $masterManager = new MasterManager($config);
    
    $res = $masterManager->deletePaymentMethod($_POST['id'] ?? '');
    
    header('Location: /master/settings?' . (($res['ok'] ?? false) ? 'ok=payment_deleted' : 'err=payment_delete_failed'));
    exit;
});

$router->add('GET', '/master', function() {
    header('Location: /master/dashboard');
    exit;
});

// --- Game Routes ---
$router->add('GET', '/games', function() use ($langManager, $config) {
    require BASE_PATH . 'views/games.php';
});

$router->add('POST', '/game/launch', function() use ($config) {
    // 1. Get User Session (Mock)
    $userId = '23213';
    $balance = '40.00';
    $gameUid = $_POST['game_uid'] ?? null;

    if (!$gameUid) die('Game ID required');

    // 2. Load Config
    $gamesConfig = require BASE_PATH . 'config/games.php';
    $providerConfig = $gamesConfig['providers']['default'];

    // 3. Init Launcher
    require_once BASE_PATH . 'includes/GameLauncher.php';
    $launcher = new GameLauncher(
        $providerConfig['api_token'],
        $providerConfig['api_secret'],
        $providerConfig['server_url']
    );

    // 4. Launch Request
    $response = $launcher->launchGame(
        $userId,
        $balance,
        $gameUid,
        $providerConfig['return_url'],
        $providerConfig['callback_url']
    );

    // 5. Build Iframe or Redirect
    if (isset($response['code']) && $response['code'] === 0 && isset($response['data']['url'])) {
        $gameUrl = $response['data']['url'];
        
        // Show Iframe
        ?>
        <!DOCTYPE html>
        <html lang="en" class="h-full bg-black">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Playing Game...</title>
            <style>body,html{margin:0;padding:0;height:100%;overflow:hidden;}iframe{width:100%;height:100%;border:none;}</style>
        </head>
        <body>
            <iframe src="<?= htmlspecialchars($gameUrl) ?>" allowfullscreen></iframe>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Error Handling
        echo "<pre>";
        echo "Error Launching Game:\n";
        print_r($response);
        echo "</pre>";
        exit;
    }
});

// --- Public API (simple GET endpoint for testing/integration) ---
// Example:
//   /v1/launch?user_id=23213&balance=40&game_uid=3978&return=https://...&callback=https://...
// Optional:
//   &currency_code=BDT&language=bn&redirect=1
$launchHandler = function() use ($config) {
    header('Content-Type: application/json; charset=utf-8');

    // 1. Get Parameters
    $apiKey = $_REQUEST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    $userId = $_REQUEST['user_id'] ?? null;
    $gameUid = $_REQUEST['game_uid'] ?? null;
    $currency = $_REQUEST['currency'] ?? null;
    $language = $_REQUEST['language'] ?? null;
    $returnUrl = $_REQUEST['return_url'] ?? null;
    $callUrl = $_REQUEST['callback_url'] ?? null;

    // 2. Validate Input
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'ok' => false, 
            'error' => 'Missing API Key', 
            'debug' => [
                'uri' => $_SERVER['REQUEST_URI'],
                'get' => $_GET,
                'request' => $_REQUEST,
                'method' => $_SERVER['REQUEST_METHOD']
            ]
        ]);
        return;
    }

    if (!$userId || !$gameUid) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing required parameters: user_id, game_uid']);
        return;
    }

    // 3. Database Connection (Need for Stores)
    $dbConfig = $config['database'];
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Internal Server Error']);
        return;
    }

    require_once BASE_PATH . 'includes/ClientStore.php';
    require_once BASE_PATH . 'includes/UserStore.php';
    require_once BASE_PATH . 'includes/GameLauncher.php';

    $clientStore = new ClientStore($pdo);
    $userStore = new UserStore($pdo);

    // 4. Authenticate Client
    $client = $clientStore->findByApiKey($apiKey);
    if (!$client) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid API Key']);
        return;
    }

    if (($client['status'] ?? 'Active') !== 'Active') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Client account is inactive']);
        return;
    }

    // 5. IP Protection
    $clientIp = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $clientIp = trim($ips[0]);
    }

    $whitelistedIps = $client['ip_whitelist'] ?? [];
    if (!empty($whitelistedIps)) {
        // If whitelist exists, enforce it
        if (!in_array($clientIp, $whitelistedIps)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'IP Address not whitelisted', 'your_ip' => $clientIp]);
            return;
        }
    }

    // 5.5 Check Game Provider Restrictions
    // a. Get Game Info to find Brand ID
    $stmt = $pdo->prepare("SELECT brand_id, status FROM games WHERE game_uid = ? LIMIT 1");
    $stmt->execute([$gameUid]);
    $gameInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gameInfo) {
        $brandId = (string)($gameInfo['brand_id'] ?? '');

        // b. Check Global Provider Status
        if ($brandId !== '') {
             $stmt2 = $pdo->prepare("SELECT status FROM game_providers WHERE brand_id = ? LIMIT 1");
             $stmt2->execute([$brandId]);
             $provInfo = $stmt2->fetch(PDO::FETCH_ASSOC);
             if ($provInfo && strtolower($provInfo['status']) === 'inactive') {
                 http_response_code(403);
                 echo json_encode(['ok' => false, 'error' => 'Game provider is currently disabled']);
                 return;
             }
        }

        // c. Check Client Blocked Providers
        // Assuming client stores blocked_providers as JSON array of brand_ids
        $blockedProviders = json_decode($client['blocked_providers'] ?? '[]', true);
        if (is_array($blockedProviders) && in_array($brandId, $blockedProviders)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Access to this game provider is restricted for your account']);
            return;
        }

        // d. Check Game Status (optional)
        if (strtolower($gameInfo['status'] ?? 'active') !== 'active') {
             http_response_code(403);
             echo json_encode(['ok' => false, 'error' => 'This game is currently disabled']);
             return;
        }
    }


    // 6. Get User Balance
    $user = $userStore->find($userId);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'User not found. Please register user first via Master API (or Panel).']);
        return;
    }
    $balance = $user['balance'];

    // 7. Config & Launch
    // Use Client's provider config if available, else Global config
    $prov = $client['provider'] ?? [];
    $token = $prov['token'] ?? $config['game_provider']['token'] ?? '';
    $secret = $prov['secret'] ?? $config['game_provider']['secret'] ?? '';
    $baseUrl = $prov['server_url'] ?? $config['game_provider']['base_url'] ?? 'https://igamingapis.live/api/v1';

    $launcher = new GameLauncher($token, $secret, $baseUrl);

    // System Callback (Our Server)
    $systemCallbackUrl = $config['app']['url'] . '/v1/callback';

    $finalReturnUrl = $returnUrl ?? $config['app']['url'];

    $response = $launcher->launchGame(
        $userId,
        $balance,
        $gameUid,
        $finalReturnUrl,
        $systemCallbackUrl,
        $currency ? $currency : ($user['currency_code'] ?? 'USD'),
        $language ? $language : ($user['language'] ?? 'en')
    );

    if (isset($response['code']) && $response['code'] == 0 && isset($response['data']['url'])) {
        echo json_encode([
            'ok' => true,
            'game_url' => $response['data']['url'],
            'balance_used' => $balance
        ]);
    } else {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'error' => 'Game provider error',
            'provider_response' => $response
        ]);
    }
};
$router->add('GET', '/v1/launch', $launchHandler);
$router->add('GET', '/v1/launch.php', $launchHandler);

// Default return page after game closes (can be overridden via /v1/launch?return=...)
$router->add('GET', '/v1/return', function() {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Return</title></head><body style='font-family:system-ui;padding:24px'><h3>Game closed</h3><p>You can safely close this tab.</p></body></html>";
});

// Forward callback test receiver (for integration testing)
$router->add('POST', '/v1/forward-test', function() use ($config) {
    header('Content-Type: application/json; charset=utf-8');
    $key = (string)($_GET['k'] ?? '');
    $salt = (string)($config['security']['salt'] ?? '');
    $expected = $salt !== '' ? substr(hash('sha256', $salt), 0, 16) : '';
    if ($expected === '' || !hash_equals($expected, $key)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        return;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $logDir = BASE_PATH . 'app_logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/forward_test.log';
    $entry = date('[Y-m-d H:i:s] ') . "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . " | Raw: " . $raw . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);

    echo json_encode(['ok' => true, 'received' => is_array($data) ? $data : null]);
});

$router->add('GET', '/v1/forward-test', function() use ($config) {
    header('Content-Type: application/json; charset=utf-8');
    $key = (string)($_GET['k'] ?? '');
    $salt = (string)($config['security']['salt'] ?? '');
    $expected = $salt !== '' ? substr(hash('sha256', $salt), 0, 16) : '';
    if ($expected === '' || !hash_equals($expected, $key)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        return;
    }
    $logFile = BASE_PATH . 'app_logs/forward_test.log';
    if (!is_file($logFile)) {
        echo json_encode(['ok' => true, 'lines' => []]);
        return;
    }
    $lines = @file($logFile, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) $lines = [];
    $tail = array_slice($lines, -20);
    echo json_encode(['ok' => true, 'lines' => $tail]);
});

// Callback receiver for provider
$callbackHandler = function() use ($config) {
    header('Content-Type: application/json; charset=utf-8');
    date_default_timezone_set('Asia/Kolkata');
    
    // 1. Capture Input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!$data) {
        // If invalid JSON, provider expects credit_amount = -1 with error
        echo json_encode([
            'credit_amount' => -1,
            'error' => 'Invalid JSON'
        ]);
        return;
    }

    // 2. Log Request (Crucial for debugging integration)
    $logDir = BASE_PATH . 'app_logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/callback.log';
    $logEntry = date('[Y-m-d H:i:s] ') . "IP: " . $_SERVER['REMOTE_ADDR'] . " | Data: " . json_encode($data) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    // 3. Parse iGaming callback payload (as per docs)
    $gameUid = (string)($data['game_uid'] ?? '');
    $gameRound = (string)($data['game_round'] ?? '');
    $memberAccount = (string)($data['member_account'] ?? '');
    $betAmount = (float)($data['bet_amount'] ?? 0);
    $winAmount = (float)($data['win_amount'] ?? 0);
    $roundTime = (string)($data['timestamp'] ?? '');

    // Provider expects credit_amount = max(0, bet - win)
    $credit = max(0, $betAmount - $winAmount);

    // 4. Update stored session balance (balance = balance - bet + win)
    $db = $config['database'];
    try {
        $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}", $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        require_once BASE_PATH . 'includes/UserStore.php';
        require_once BASE_PATH . 'includes/ClientStore.php';
        $userStore = new UserStore($pdo);
        $clientStore = new ClientStore($pdo);

        $clientUuid = (string)($_GET['client'] ?? '');
        $launchRequestId = null;
        $balanceBefore = null;
        $balanceAfter = null;
        $forwardHttpCode = null;
        $forwardErr = null;
        $forwardRespSnip = null;
        $forwardUrl = null;

        // Find session saved at launch time
        if ($clientUuid !== '' && $gameUid !== '') {
            $stmt = $pdo->prepare("SELECT launch_request_id, user_id, balance FROM game_sessions WHERE client_uuid = ? AND game_uid = ? LIMIT 1");
            $stmt->execute([$clientUuid, $gameUid]);
            $sess = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($sess)) {
                $launchRequestId = (string)($sess['launch_request_id'] ?? '');
                $balanceBefore = (float)($sess['balance'] ?? 0);
            }
        }

        // Fallback: if no session found, try user balance (optional)
        if ($balanceBefore === null && $memberAccount !== '') {
            $u = $userStore->find($memberAccount);
            if ($u) $balanceBefore = (float)($u['balance'] ?? 0);
        }
        if ($balanceBefore === null) $balanceBefore = 0.0;

        // Optional insufficient funds check based on stored balance
        if ($betAmount > 0 && $balanceBefore < $betAmount) {
            // Persist callback log
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO game_callback_logs
                      (client_uuid, user_id, game_uid, game_round, bet_amount, win_amount, balance_before, balance_after, credit_amount, status, raw_json)
                    VALUES
                      (?, ?, ?, ?, ?, ?, ?, ?, ?, 'insufficient_funds', ?)
                    ON DUPLICATE KEY UPDATE
                      bet_amount = VALUES(bet_amount),
                      win_amount = VALUES(win_amount),
                      balance_before = VALUES(balance_before),
                      balance_after = VALUES(balance_after),
                      credit_amount = VALUES(credit_amount),
                      status = 'insufficient_funds',
                      raw_json = VALUES(raw_json)
                ");
                $stmt->execute([
                    $clientUuid !== '' ? $clientUuid : 'unknown',
                    $memberAccount !== '' ? $memberAccount : 'unknown',
                    $gameUid !== '' ? $gameUid : 'unknown',
                    $gameRound !== '' ? $gameRound : 'unknown',
                    (float)$betAmount,
                    (float)$winAmount,
                    (float)$balanceBefore,
                    (float)$balanceBefore,
                    -1.0,
                    json_encode($data),
                ]);
            } catch (Throwable $e) {
                // ignore
            }

            $providerResponse = [
                'credit_amount' => -1,
                'error' => 'insufficient_funds',
                'timestamp' => round(microtime(true) * 1000),
            ];
            echo json_encode($providerResponse);

            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }

            // Forward failure event to client callback (best-effort)
            if ($clientUuid !== '') {
                $client = $clientStore->findById($clientUuid);
                $forwardUrl = '';
                if ($client && isset($client['provider']) && is_array($client['provider'])) {
                    $forwardUrl = (string)($client['provider']['forward_callback_url'] ?? '');
                }
                if ($forwardUrl !== '') {
                    $forwardPayload = $data;
                    $forwardPayload['inf_request_id'] = $launchRequestId;
                    $forwardPayload['inf_balance'] = (float)$balanceBefore;
                    $forwardPayload['inf_net_amount'] = (float)$winAmount - (float)$betAmount;
                    $forwardPayload['inf_deduct_amount'] = -1;
                    $forwardPayload['inf_error'] = 'insufficient_funds';
                    // For client panel simplicity: credit_amount = next balance (unchanged on failure)
                    $forwardPayload['credit_amount'] = (float)$balanceBefore;
                    $forwardPayload['deduct_amount'] = -1;
                    $forwardPayload['error'] = 'insufficient_funds';

                    $ch = curl_init($forwardUrl);
                    if ($ch) {
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($forwardPayload));
                        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                        $resp = curl_exec($ch);
                        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $err = curl_error($ch);
                        curl_close($ch);
                        $forwardHttpCode = $code;
                        $forwardErr = $err ? $err : null;
                        $forwardRespSnip = is_string($resp) ? substr($resp, 0, 300) : null;

                        $fwdEntry = date('[Y-m-d H:i:s] ') . "FORWARD url={$forwardUrl} http_code={$code} err=" . ($err ? $err : '-') . " resp=" . (is_string($resp) ? substr($resp, 0, 300) : '-') . PHP_EOL;
                        file_put_contents($logFile, $fwdEntry, FILE_APPEND);
                    }
                }
            }

            // Update callback log with forward status
            try {
                $stmt = $pdo->prepare("
                    UPDATE game_callback_logs
                    SET forwarded_url = ?, forward_http_code = ?, forward_error = ?, forward_response_snip = ?, forwarded_at = NOW()
                    WHERE client_uuid = ? AND user_id = ? AND game_uid = ? AND game_round = ?
                ");
                $stmt->execute([
                    $forwardUrl,
                    $forwardHttpCode,
                    $forwardErr,
                    $forwardRespSnip,
                    $clientUuid !== '' ? $clientUuid : 'unknown',
                    $memberAccount !== '' ? $memberAccount : 'unknown',
                    $gameUid !== '' ? $gameUid : 'unknown',
                    $gameRound !== '' ? $gameRound : 'unknown',
                ]);
            } catch (Throwable $e) {
                // ignore
            }

            return;
        }

        // Update balance snapshot for this session
        $balanceAfter = (float)$balanceBefore - (float)$betAmount + (float)$winAmount;

        // Update/Insert session row
        if ($clientUuid !== '' && $gameUid !== '') {
            if (!$launchRequestId) {
                $launchRequestId = 'cb_' . bin2hex(random_bytes(8));
            }
            $stmt = $pdo->prepare("
                INSERT INTO game_sessions
                  (client_uuid, user_id, game_uid, launch_request_id, balance, last_game_round, last_callback_at, status)
                VALUES
                  (?, ?, ?, ?, ?, ?, NOW(), 'launched')
                ON DUPLICATE KEY UPDATE
                  user_id = VALUES(user_id),
                  balance = VALUES(balance),
                  last_game_round = VALUES(last_game_round),
                  last_callback_at = NOW(),
                  updated_at = NOW()
            ");
            $stmt->execute([
                $clientUuid,
                $memberAccount !== '' ? $memberAccount : 'unknown',
                $gameUid,
                $launchRequestId,
                (float)$balanceAfter,
                $gameRound !== '' ? $gameRound : null,
            ]);
        }

        // Update user wallet as absolute current balance (create/update)
        if ($memberAccount !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO users (user_id, balance, currency_code, language, created_at)
                VALUES (?, ?, 'INR', 'en', NOW())
                ON DUPLICATE KEY UPDATE
                  balance = VALUES(balance),
                  updated_at = NOW()
            ");
            $stmt->execute([(string)$memberAccount, (float)$balanceAfter]);
        }

        // Persist callback log (success)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO game_callback_logs
                  (client_uuid, user_id, game_uid, game_round, bet_amount, win_amount, balance_before, balance_after, credit_amount, status, raw_json)
                VALUES
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ok', ?)
                ON DUPLICATE KEY UPDATE
                  bet_amount = VALUES(bet_amount),
                  win_amount = VALUES(win_amount),
                  balance_before = VALUES(balance_before),
                  balance_after = VALUES(balance_after),
                  credit_amount = VALUES(credit_amount),
                  status = 'ok',
                  raw_json = VALUES(raw_json)
            ");
            $stmt->execute([
                $clientUuid !== '' ? $clientUuid : 'unknown',
                $memberAccount !== '' ? $memberAccount : 'unknown',
                $gameUid !== '' ? $gameUid : 'unknown',
                $gameRound !== '' ? $gameRound : 'unknown',
                (float)$betAmount,
                (float)$winAmount,
                (float)$balanceBefore,
                (float)$balanceAfter,
                (float)$balanceAfter,
                json_encode($data),
            ]);
        } catch (Throwable $e) {
            // ignore
        }

        // --- GGR Commission Logic (Deduct from Client Wallet on User Loss) ---
        // PnL for this round from User perspective
        // Loss = Bet - Win. If Bet > Win (e.g. 100 - 0 = 100 Loss), outcome is positive loss.
        // Win = Win - Bet. If Win > Bet (e.g. 200 - 100 = 100 Win), outcome is negative loss.
        $userLoss = (float)$betAmount - (float)$winAmount;

        if ($clientUuid !== '' && $userLoss > 0) {
            try {
                // Fetch current GGR % and Wallet
                // Re-fetch client to get latest balance/settings
                $clientData = $clientStore->findById($clientUuid);
                
                if ($clientData) {
                    $ggrPercent = (float)($clientData['ggr_balance'] ?? 0); // Assuming 'ggr_balance' stores the % (e.g., 10 or 15)
                    // If client has a valid GGR % set
                    if ($ggrPercent > 0) {
                        $commission = $userLoss * ($ggrPercent / 100);
                        
                        if ($commission > 0) {
                            // Deduct from Client Wallet
                            // We do a direct update query to ensure atomicity and avoid race conditions better
                            $stmt = $pdo->prepare("UPDATE clients SET wallet_balance = wallet_balance - ? WHERE uuid = ?");
                            $stmt->execute([$commission, $clientUuid]);
                            
                            // Log the deduction in client_balance_changes (or a new table if preferred, but using existing)
                            // We need to fetch the NEW wallet balance for accurate logging, or just log the change
                            // Let's refetch to be safe for logs
                            $stmt = $pdo->prepare("SELECT wallet_balance FROM clients WHERE uuid = ?");
                            $stmt->execute([$clientUuid]);
                            $newWallet = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO client_balance_changes 
                                (client_uuid, wallet_before, wallet_after, ggr_before, ggr_after, note, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, NOW())
                            ");
                            
                            // Approximation of 'before' for log
                            $walletBefore = (float)$newWallet + $commission;
                            
                            $stmt->execute([
                                $clientUuid,
                                $walletBefore,
                                (float)$newWallet,
                                $ggrPercent,
                                $ggrPercent,
                                "GGR Deduction: {$ggrPercent}% on User Loss " . number_format($userLoss, 2) . " (Game: {$gameUid}, Round: {$gameRound})"
                            ]);
                        }
                    }
                }
            } catch (Throwable $e) {
                // Log GGR error but don't fail the callback
                $errEntry = date('[Y-m-d H:i:s] ') . "GGR_ERROR: " . $e->getMessage() . PHP_EOL;
                file_put_contents($logFile, $errEntry, FILE_APPEND);
            }
        }
        // ---------------------------------------------------------------

        // 5. Provider expected response (ALWAYS from our server)
        // As requested: credit_amount = current balance (after applying bet/win)
        $providerResponse = [
            'credit_amount' => (float)$balanceAfter,
            'timestamp' => round(microtime(true) * 1000),
        ];
        echo json_encode($providerResponse);

        // Flush response to provider first, then do client-forwarding (best-effort).
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }

        // 6. Forward callback to client's configured callback URL (if set)
        if ($clientUuid !== '') {
            $client = $clientStore->findById($clientUuid);
            $forwardUrl = '';
            if ($client && isset($client['provider']) && is_array($client['provider'])) {
                $forwardUrl = (string)($client['provider']['forward_callback_url'] ?? '');
            }

            // "Only request send" — response is ignored.
            if ($forwardUrl !== '') {
                $forwardPayload = $data;
                $forwardPayload['inf_request_id'] = $launchRequestId;
                $forwardPayload['inf_balance'] = (float)$balanceAfter;
                $forwardPayload['inf_net_amount'] = (float)$winAmount - (float)$betAmount;
                $forwardPayload['inf_deduct_amount'] = (float)$credit;
                // For client panel simplicity: credit_amount = next balance
                $forwardPayload['credit_amount'] = (float)$balanceAfter;
                $forwardPayload['deduct_amount'] = (float)$credit;
                $ch = curl_init($forwardUrl);
                if ($ch) {
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($forwardPayload));
                    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                    $resp = curl_exec($ch);
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $err = curl_error($ch);
                    curl_close($ch);
                    $forwardHttpCode = $code;
                    $forwardErr = $err ? $err : null;
                    $forwardRespSnip = is_string($resp) ? substr($resp, 0, 300) : null;

                    $fwdEntry = date('[Y-m-d H:i:s] ') . "FORWARD url={$forwardUrl} http_code={$code} err=" . ($err ? $err : '-') . " resp=" . (is_string($resp) ? substr($resp, 0, 300) : '-') . PHP_EOL;
                    file_put_contents($logFile, $fwdEntry, FILE_APPEND);
                }
            }
        }

        // Update callback log with forward status
        try {
            $stmt = $pdo->prepare("
                UPDATE game_callback_logs
                SET forwarded_url = ?, forward_http_code = ?, forward_error = ?, forward_response_snip = ?, forwarded_at = NOW()
                WHERE client_uuid = ? AND user_id = ? AND game_uid = ? AND game_round = ?
            ");
            $stmt->execute([
                $forwardUrl,
                $forwardHttpCode,
                $forwardErr,
                $forwardRespSnip,
                $clientUuid !== '' ? $clientUuid : 'unknown',
                $memberAccount !== '' ? $memberAccount : 'unknown',
                $gameUid !== '' ? $gameUid : 'unknown',
                $gameRound !== '' ? $gameRound : 'unknown',
            ]);
        } catch (Throwable $e) {
            // ignore
        }

        return;

    } catch (Exception $e) {
        $errEntry = date('[Y-m-d H:i:s] ') . "ERROR: " . $e->getMessage() . " | game_uid={$gameUid} round={$gameRound} member={$memberAccount} bet={$betAmount} win={$winAmount} time={$roundTime}" . PHP_EOL;
        file_put_contents($logFile, $errEntry, FILE_APPEND);
        echo json_encode([
            'credit_amount' => -1,
            'error' => 'internal_error',
        ]);
        return;
    }
};
$router->add('GET', '/v1/callback', $callbackHandler);
$router->add('POST', '/v1/callback', $callbackHandler);
$router->add('GET', '/v1/callback.php', $callbackHandler);
$router->add('POST', '/v1/callback.php', $callbackHandler);
$router->add('GET', '/api/v1/callback.php', $callbackHandler);
$router->add('POST', '/api/v1/callback.php', $callbackHandler);
$router->add('GET', '/api/v1/launch.php', $launchHandler);

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Agar URL me /v1/launch hai to turant launch API chalao (Nginx 404 bypass — request index.php tak aaye to)
if ($method === 'GET' && stripos($uri, '/v1/launch') !== false) {
    require BASE_PATH . 'api/v1/launch.php';
    exit;
}

// Path Logic: Prioritize ?s=, then fallback to REQUEST_URI (clean URL)
$path = '/';
if (isset($_GET['s']) && (string)$_GET['s'] !== '') {
    $path = (string)$_GET['s'];
} else {
    // Strip query string from URI
    $uri_parts = explode('?', $uri, 2);
    $path = $uri_parts[0];
}

// Normalize path: decode, trim, and ensure leading slash
$path = rawurldecode((string)$path);
$path = '/' . trim($path, '/');
if ($path === '') {
    $path = '/';
}
$path = preg_replace('#/+#', '/', $path);

// Handle Language Switch param globally
if (isset($_GET['lang'])) {
    // Redirect handled mostly by session, but we can restart loop or just let it pass
}

$router->dispatch($method, $path);
