<?php
// Core logic for the Master Admin Panel

class MasterManager {
    private $redis;
    private $db;
    private $userStore;
    private $clientStore;
    private $securitySalt;

    public function __construct($config) {
        $this->securitySalt = (string)($config['security']['salt'] ?? '');

        // Init DB
        $db = $config['database'];
        try {
            // Check for correct host for local environment
            $dsn = "mysql:host={$db['host']};dbname={$db['database']};charset=utf8mb4";
            $this->db = new PDO($dsn, $db['username'], $db['password']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }

        require_once __DIR__ . '/Crypto.php';
        require_once __DIR__ . '/UserStore.php';
        $this->userStore = new UserStore($this->db);

        require_once __DIR__ . '/ClientStore.php';
        $this->clientStore = new ClientStore($this->db);
    }

    // Analytics Dashboard
    public function getGlobalStats() {
        $stats = [
            'total_bet_24h' => 0.00,
            'total_loss_24h' => 0.00,
            'avg_rtp' => 0.0,
            'system_balance' => 0.00,
            'active_sessions' => 0,
            'active_clients' => 0,
            'requests_per_sec' => 0,
            'server_health' => '100%',
        ];

        try {
            // 1. Total Bets & Wins (24h)
            // Assuming 'created_at' exists and is default CURRENT_TIMESTAMP. 
            // If strictly relying on provided timestamp in JSON, we can't easily query.
            // We use created_at or fallback to all time if looking for general stats, 
            // but the UI says "24h".
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(bet_amount) as total_bet, 
                    SUM(win_amount) as total_win 
                FROM game_callback_logs 
                WHERE created_at >= (NOW() - INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['total_bet_24h'] = (float)($row['total_bet'] ?? 0);
            $totalWin = (float)($row['total_win'] ?? 0);
            
            // "Total Loss" for the dashboard usually refers to "User Loss" (Revenue) 
            // OR "System Loss" (User Wins). 
            // Given the static value was small compared to bet, let's assume "System Loss" (User Wins) 
            // to keep it consistent with the label "Volatility".
            // However, usually Casino Dashboard: 'Bet' (In), 'Win' (Out).
            // Let's return Total Win as 'total_loss_24h' (Money leaving system).
            $stats['total_loss_24h'] = $totalWin;

            // RTP
            if ($stats['total_bet_24h'] > 0) {
                $stats['avg_rtp'] = round(($totalWin / $stats['total_bet_24h']) * 100, 2);
            }

            // 2. System Balance (Sum of Clients)
            $stmt = $this->db->query("SELECT SUM(wallet_balance) as wb, SUM(ggr_balance) as gb FROM clients");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['system_balance'] = (float)($row['wb'] ?? 0) + (float)($row['gb'] ?? 0);

            // 3. Active Sessions (Last 10 mins activity)
            // Checking game_sessions updated_at
            $stmt = $this->db->query("SELECT COUNT(*) as c FROM game_sessions WHERE updated_at >= (NOW() - INTERVAL 10 MINUTE)");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['active_sessions'] = (int)($row['c'] ?? 0);

            // 4. Active Clients
            $stats['active_clients'] = count($this->clientStore->all());

        } catch (Exception $e) {
            // Fallback or log error
            error_log("MasterManager Stats Error: " . $e->getMessage());
        }

        return $stats;
    }

    public function getSystemHealth() {
        $dbStatus = 'Unknown';
        $dbLatency = '0ms';
        
        $start = microtime(true);
        try {
            $this->db->query("SELECT 1");
            $dbStatus = 'Healthy';
            $dbLatency = round((microtime(true) - $start) * 1000, 2) . 'ms';
        } catch (Exception $e) {
            $dbStatus = 'Error';
        }

        return [
            'database' => ['status' => $dbStatus, 'latency' => $dbLatency],
            // Mocking Redis/API Gateway for now as we don't have direct access/metrics setup
            'redis' => ['status' => 'Healthy', 'latency' => '0.5ms', 'memory_used' => 'Active'], 
            'api_gateway' => ['status' => 'Stable', 'rpm' => 'N/A'],
            'circuit_breaker' => ['status' => 'Closed', 'tripped_count' => 0],
        ];
    }

    // Clients Management
    public function getClients() {
        $clients = $this->clientStore->all();
        foreach ($clients as &$c) {
            $c['balance'] = $c['wallet_balance'] ?? 0;
            // Also ensure numeric
            $c['balance'] = (float)$c['balance'];
        }
        unset($c);
        
        usort($clients, function ($a, $b) {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });
        return $clients;
    }

    // Game Providers
    public function getProviders() {
        try {
            $stmt = $this->db->query("SELECT * FROM game_providers ORDER BY name ASC");
            $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $providers ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRecentAccessLogs() {
        return [
            ['time' => '10:45:12', 'ip' => '192.168.1.15', 'action' => 'Generate Key', 'user' => 'SuperAdmin'],
            ['time' => '10:42:05', 'ip' => '10.0.0.5', 'action' => 'Edit Client #3', 'user' => 'Support1'],
            ['time' => '10:30:00', 'ip' => '172.16.0.2', 'action' => 'Clear Cache', 'user' => 'System'],
        ];
    }

    // --- Users (Players) ---
    public function getUsers(): array
    {
        $users = $this->userStore->all();
        usort($users, function ($a, $b) {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });
        return $users;
    }

    public function createUser(string $userId, $balance, ?string $currencyCode = null, ?string $language = null): array
    {
        $userId = trim($userId);
        if ($userId === '') return ['ok' => false, 'error' => 'user_id_required'];

        if (!is_numeric($balance)) return ['ok' => false, 'error' => 'balance_invalid'];
        $balance = (float)$balance;
        if ($balance < 0) return ['ok' => false, 'error' => 'balance_negative'];

        $currencyCode = $currencyCode ? strtoupper(trim($currencyCode)) : null;
        $language = $language ? strtolower(trim($language)) : null;

        $user = [
            'user_id' => $userId,
            'balance' => $balance,
            'currency_code' => $currencyCode,
            'language' => $language,
            'created_at' => gmdate('c'),
        ];

        return $this->userStore->create($user);
    }

    public function deleteUser(string $userId): array
    {
        $userId = trim($userId);
        if ($userId === '') return ['ok' => false, 'error' => 'user_id_required'];
        return $this->userStore->delete($userId);
    }

    // --- Clients (Operators) ---
    public function createClient(string $name, string $clientId, string $password, string $providerToken, string $providerSecret, string $status = 'Active', string $ipWhitelistStr = ''): array
    {
        $name = trim($name);
        $clientId = trim($clientId);
        $password = (string)$password;
        $providerToken = trim($providerToken);
        $providerSecret = trim($providerSecret);
        $status = trim($status) ?: 'Active';

        if ($name === '' || $clientId === '' || $password === '' || $providerToken === '' || $providerSecret === '') {
            return ['ok' => false, 'error' => 'missing_fields'];
        }

        require_once __DIR__ . '/TokenGenerator.php';

        $id = 'c_' . bin2hex(random_bytes(4));
        // 16-char alphanumeric token/secret
        do {
            $apiKey = TokenGenerator::alnum(16);
            $exists = $this->clientStore->findByApiKey($apiKey);
        } while ($exists);

        // Ensure secret is not trivially same as apiKey
        do {
            $clientSecret = TokenGenerator::alnum(16);
        } while ($clientSecret === $apiKey);

        // Process IP Whitelist
        $ipWhitelist = [];
        if (trim($ipWhitelistStr) !== '') {
            $ips = explode(',', $ipWhitelistStr);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ipWhitelist[] = $ip;
                }
            }
        }
        $ipWhitelistEnabled = count($ipWhitelist) > 0 ? 1 : 0;

        $client = [
            'id' => $id,
            'name' => $name,
            'client_id' => $clientId,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => $status,
            'api_key' => $apiKey,
            'client_secret' => $clientSecret,
            'ip_whitelist_enabled' => $ipWhitelistEnabled,
            'ip_whitelist' => $ipWhitelist,
            'provider' => [
                'token' => $providerToken,
                'secret' => $providerSecret,
                'server_url' => 'https://igamingapis.live/api/v1',
            ],
            'created_at' => gmdate('c'),
        ];

        // Store Access Key (Password) encrypted for admin panel display.
        // Login verification still uses password_hash/password_verify.
        // WARNING: Storing plaintext passwords is insecure; enabled here per requirement.
        $client['provider']['access_key_plain'] = $password;
        if ($this->securitySalt !== '') {
            $enc = Crypto::encryptString($password, $this->securitySalt);
            if ($enc !== '') {
                $client['provider']['access_key_enc'] = $enc;
            }
        }

        $res = $this->clientStore->create($client);
        if (($res['ok'] ?? false) !== true) return $res;

        return ['ok' => true, 'client' => $client];
    }

    public function revealClientAccessKey(array $client): ?string
    {
        $plain = $client['provider']['access_key_plain'] ?? null;
        if (is_string($plain) && $plain !== '') return $plain;

        if ($this->securitySalt === '') return null;
        $enc = $client['provider']['access_key_enc'] ?? null;
        if (!is_string($enc) || $enc === '') return null;
        return Crypto::decryptString($enc, $this->securitySalt);
    }

    public function resetClientAccessKey(string $id): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];

        $client = $this->clientStore->findById($id);
        if (!$client) return ['ok' => false, 'error' => 'not_found'];

        $newAccessKey = 'ak_' . bin2hex(random_bytes(6));
        $newHash = password_hash($newAccessKey, PASSWORD_DEFAULT);

        $provider = $client['provider'] ?? [];
        if (!is_array($provider)) $provider = [];

        // WARNING: Storing plaintext passwords is insecure; enabled here per requirement.
        $provider['access_key_plain'] = $newAccessKey;
        if ($this->securitySalt !== '') {
            $enc = Crypto::encryptString($newAccessKey, $this->securitySalt);
            if ($enc !== '') {
                $provider['access_key_enc'] = $enc;
            }
        }

        $res = $this->clientStore->update($id, [
            'password_hash' => $newHash,
            'provider' => $provider,
        ]);

        if (($res['ok'] ?? false) !== true) return $res;

        return [
            'ok' => true,
            'client_id' => $client['client_id'] ?? null,
            'api_key' => $client['api_key'] ?? null,
            'access_key' => $newAccessKey,
        ];
    }

    public function deleteClient(string $id): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];
        return $this->clientStore->delete($id);
    }

    public function rotateClientApiKey(string $id): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];
        require_once __DIR__ . '/TokenGenerator.php';
        do {
            $apiKey = TokenGenerator::alnum(16);
            $exists = $this->clientStore->findByApiKey($apiKey);
        } while ($exists);
        return $this->clientStore->update($id, ['api_key' => $apiKey]);
    }

    public function updateClientIpWhitelist(string $id, string $ipWhitelistStr): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];

        $ipWhitelist = [];
        $ipWhitelistStr = trim($ipWhitelistStr);
        if ($ipWhitelistStr !== '') {
            $ips = explode(',', $ipWhitelistStr);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ipWhitelist[] = $ip;
                }
            }
        }

        return $this->clientStore->update($id, ['ip_whitelist' => $ipWhitelist]);
    }

    public function setClientIpWhitelistEnabled(string $id, bool $enabled): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];
        return $this->clientStore->update($id, ['ip_whitelist_enabled' => $enabled ? 1 : 0]);
    }

    public function updateClientBalances(string $id, $walletBalance, $ggrBalance): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];

        if (!is_numeric($walletBalance) || !is_numeric($ggrBalance)) {
            return ['ok' => false, 'error' => 'invalid_balance'];
        }

        $existing = $this->clientStore->findById($id);
        if (!$existing) return ['ok' => false, 'error' => 'not_found'];

        $walletBefore = (float)($existing['wallet_balance'] ?? 0);
        $ggrBefore = (float)($existing['ggr_balance'] ?? 0);

        $wallet = round((float)$walletBalance, 2);
        $ggr = round((float)$ggrBalance, 2);

        $res = $this->clientStore->update($id, [
            'wallet_balance' => $wallet,
            'ggr_balance' => $ggr,
        ]);

        if (($res['ok'] ?? false) === true) {
            try {
                $stmt = $this->db->prepare("INSERT INTO client_balance_changes (client_uuid, wallet_before, wallet_after, ggr_before, ggr_after, note) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    (string)($existing['uuid'] ?? $existing['id'] ?? $id),
                    $walletBefore,
                    $wallet,
                    $ggrBefore,
                    $ggr,
                    'master_update',
                ]);
            } catch (Throwable $e) {
                // ignore
            }
        }

        return $res;
    }

    public function setClientForwardCallbackUrl(string $id, string $url): array
    {
        $id = trim($id);
        $url = trim($url);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];

        $client = $this->clientStore->findById($id);
        if (!$client) return ['ok' => false, 'error' => 'not_found'];

        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
            return ['ok' => false, 'error' => 'invalid_url'];
        }

        $provider = $client['provider'] ?? [];
        if (!is_array($provider)) $provider = [];
        $provider['forward_callback_url'] = $url;

        return $this->clientStore->update($id, [
            'provider' => $provider,
        ]);
    }

    public function updateClientBlockedProviders(string $id, array $blockedProviders): array
    {
        $id = trim($id);
        if ($id === '') return ['ok' => false, 'error' => 'id_required'];
        return $this->clientStore->update($id, ['blocked_providers' => $blockedProviders]);
    }

    // --- Payment Methods ---
    public function getPaymentMethods() {
        $stmt = $this->db->query("SELECT * FROM payment_methods ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createPaymentMethod($name, $type, $address, $qrImage, $instructions, $status) {
        try {
            $stmt = $this->db->prepare("INSERT INTO payment_methods (name, type, address, qr_image, instructions, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($name),
                trim($type),
                trim($address),
                trim($qrImage),
                trim($instructions),
                $status
            ]);
            return ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function updatePaymentMethod($id, $name, $type, $address, $qrImage, $instructions, $status) {
        try {
            $sql = "UPDATE payment_methods SET name=?, type=?, address=?, instructions=?, status=?";
            $params = [trim($name), trim($type), trim($address), trim($instructions), $status];
            
            if ($qrImage !== null) {
                $sql .= ", qr_image=?";
                $params[] = trim($qrImage);
            }
            
            $sql .= " WHERE id=?";
            $params[] = $id;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function deletePaymentMethod($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM payment_methods WHERE id=?");
            $stmt->execute([$id]);
            return ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAnalyticsReport($period = 'today') {
        $stats = [
            'total_bet' => 0.00,
            'total_win' => 0.00,
            'total_loss' => 0.00, // User loss (Revenue)
            'ggr_deduction' => 0.00,
            'client_breakdown' => []
        ];

        try {
            // Determine Date Range
            $dateCondition = "DATE(created_at) = CURDATE()"; // Default today
            $ggrDateCondition = "DATE(created_at) = CURDATE()";

            if ($period === 'yesterday') {
                $dateCondition = "DATE(created_at) = SUBDATE(CURDATE(), 1)";
                $ggrDateCondition = "DATE(created_at) = SUBDATE(CURDATE(), 1)";
            } elseif ($period === 'month') {
                $dateCondition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                $ggrDateCondition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            } elseif ($period === 'year') {
                $dateCondition = "YEAR(created_at) = YEAR(CURDATE())";
                $ggrDateCondition = "YEAR(created_at) = YEAR(CURDATE())";
            }

            // 1. Aggregate Bets/Wins/Loss from game_callback_logs
            $sql = "
                SELECT 
                    client_uuid,
                    SUM(bet_amount) as bets,
                    SUM(win_amount) as wins
                FROM game_callback_logs
                WHERE $dateCondition
                GROUP BY client_uuid
            ";
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Aggregate GGR Deductions from client_balance_changes
            // Look for notes starting with 'GGR Deduction'
            $sqlGGR = "
                SELECT 
                    client_uuid,
                    SUM(wallet_before - wallet_after) as ggr_amt
                FROM client_balance_changes
                WHERE $ggrDateCondition 
                  AND note LIKE 'GGR Deduction%'
                GROUP BY client_uuid
            ";
            $stmtGGR = $this->db->query($sqlGGR);
            $ggrRows = $stmtGGR->fetchAll(PDO::FETCH_ASSOC);
            $ggrMap = [];
            foreach ($ggrRows as $r) {
                $ggrMap[$r['client_uuid']] = (float)$r['ggr_amt'];
            }

            // 3. Combine Data
            $clients = $this->clientStore->all();
            $clientNameMap = [];
            foreach ($clients as $c) {
                $uuid = $c['uuid'] ?? $c['id']; // handle both if necessary, but consistency is key
                if (!$uuid) continue;
                $clientNameMap[$uuid] = $c['name'];
            }

            foreach ($rows as $r) {
                $uuid = $r['client_uuid'];
                $bets = (float)$r['bets'];
                $wins = (float)$r['wins'];
                $loss = $bets - $wins; // Positive if house won (Revenue)
                $ggr = $ggrMap[$uuid] ?? 0.00;

                $stats['total_bet'] += $bets;
                $stats['total_win'] += $wins;
                $stats['total_loss'] += $loss;
                $stats['ggr_deduction'] += $ggr;

                $stats['client_breakdown'][] = [
                    'client_name' => $clientNameMap[$uuid] ?? 'Unknown Client',
                    'bets' => $bets,
                    'wins' => $wins,
                    'loss' => $loss,
                    'ggr' => $ggr
                ];
            }

            // If there were GGR deductions but no game logs for that period (edge case, or different table sync), add them
            foreach ($ggrMap as $uuid => $amt) {
                // Check if already processed
                $found = false;
                foreach ($stats['client_breakdown'] as $cb) {
                    if (($clientNameMap[$uuid] ?? '') === $cb['client_name']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                     $stats['ggr_deduction'] += $amt; // Be careful double counting if logic above was perfect
                     // Logically if loop above ran, stats['ggr_deduction'] included it IF row existed.
                     // Here we only add if row DID NOT exist in game_callback_logs (unlikely but possible)
                     $stats['client_breakdown'][] = [
                        'client_name' => $clientNameMap[$uuid] ?? 'Unknown Client',
                        'bets' => 0,
                        'wins' => 0,
                        'loss' => 0,
                        'ggr' => $amt
                    ];
                }
            }

        } catch (Exception $e) {
            // error_log
        }

        return $stats;
    }
}
