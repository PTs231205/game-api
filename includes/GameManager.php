<?php

class GameManager {
    private ?PDO $pdo = null;

    public function __construct($config) {
        $db = $config['database'] ?? [];
        try {
            $dsn = "mysql:host=" . ($db['host'] ?? '127.0.0.1') .
                ";port=" . ($db['port'] ?? 3306) .
                ";dbname=" . ($db['database'] ?? '') .
                ";charset=" . ($db['charset'] ?? 'utf8mb4');
            $this->pdo = new PDO($dsn, (string)($db['username'] ?? ''), (string)($db['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
        } catch (Throwable $e) {
            $this->pdo = null;
        }
    }

    private function getClientByClientId(?string $clientId): ?array
    {
        if (!$this->pdo || !$clientId) return null;
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE client_id = ? LIMIT 1");
        $stmt->execute([$clientId]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        return $c ?: null;
    }

    public function getWalletBalance($clientId) {
        $c = $this->getClientByClientId((string)$clientId);
        return $c ? (float)($c['wallet_balance'] ?? 0) : 0.0;
    }

    public function getGgrBalance($clientId) {
        $c = $this->getClientByClientId((string)$clientId);
        return $c ? (float)($c['ggr_balance'] ?? 0) : 0.0;
    }

    public function getCurrency($clientId) {
        return 'INR';
    }

    public function getApiTokens($clientId) {
        $c = $this->getClientByClientId((string)$clientId);
        if (!$c) return [];
        return [
            [
                'id' => 'api_key',
                'name' => 'API Key',
                'token' => (string)($c['api_key'] ?? ''),
                'status' => (string)($c['status'] ?? 'Active'),
            ],
            [
                'id' => 'client_secret',
                'name' => 'Client Secret (for sig)',
                'token' => (string)($c['client_secret'] ?? ''),
                'status' => (string)($c['status'] ?? 'Active'),
            ],
        ];
    }

    public function getWhitelistedIPs($clientId) {
        $c = $this->getClientByClientId((string)$clientId);
        if (!$c) return [];
        $ips = json_decode((string)($c['ip_whitelist'] ?? '[]'), true);
        return is_array($ips) ? $ips : [];
    }

    public function isIpWhitelistEnabled($clientId): bool
    {
        $c = $this->getClientByClientId((string)$clientId);
        return $c ? ((int)($c['ip_whitelist_enabled'] ?? 0) === 1) : false;
    }

    public function getBalanceChanges($clientId, int $limit = 20): array
    {
        $c = $this->getClientByClientId((string)$clientId);
        if (!$this->pdo || !$c) return [];
        $uuid = (string)($c['uuid'] ?? '');
        if ($uuid === '') return [];
        $stmt = $this->pdo->prepare("SELECT * FROM client_balance_changes WHERE client_uuid = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $uuid);
        $stmt->bindValue(2, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getGameLogs($clientId, $limit = 10) {
        // Return empty or fetch from logs table if needed for legacy reasons.
        // For now, keeping it minimal as we are switching value in Dashboard to Callbacks.
        return [];
    }

    public function getRecentCallbacks($clientId, $limit = 10) {
        $c = $this->getClientByClientId((string)$clientId);
        if (!$this->pdo || !$c) return [];
        $uuid = (string)($c['uuid'] ?? '');
        if ($uuid === '') return [];

        $stmt = $this->pdo->prepare("
            SELECT * FROM game_callback_logs 
            WHERE client_uuid = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $uuid);
        $stmt->bindValue(2, max(1, (int)$limit), PDO::PARAM_INT);
        $stmt->execute();
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        
        foreach ($rows as $r) {
            $out[] = [
                'time' => (string)($r['created_at'] ?? ''),
                'game_uid' => (string)($r['game_uid'] ?? ''),
                'round_id' => (string)($r['game_round'] ?? ''),
                'user_id' => (string)($r['user_id'] ?? ''),
                'bet' => (float)($r['bet_amount'] ?? 0),
                'win' => (float)($r['win_amount'] ?? 0),
                'balance_after' => (float)($r['balance_after'] ?? 0),
                'status' => (string)($r['status'] ?? 'unknown'),
            ];
        }
        return $out;
    }

    public function getDashboardStats($clientId) {
        $c = $this->getClientByClientId((string)$clientId);
        if (!$this->pdo || !$c) {
            return [
                'requests_24h' => 0,
                'success_24h' => 0,
                'fail_24h' => 0,
                'avg_latency_24h' => 0,
                'active_sessions' => 0,
            ];
        }
        $uuid = (string)($c['uuid'] ?? '');
        if ($uuid === '') {
            return [
                'requests_24h' => 0,
                'success_24h' => 0,
                'fail_24h' => 0,
                'avg_latency_24h' => 0,
                'active_sessions' => 0,
            ];
        }

        // Use game_callback_logs instead of api_logs for stats
        $stmt = $this->pdo->prepare("
            SELECT 
              COUNT(*) AS reqs,
              SUM(CASE WHEN win_amount > 0 THEN 1 ELSE 0 END) AS success, -- Treat wins as success (mock logic)
              SUM(CASE WHEN win_amount = 0 THEN 1 ELSE 0 END) AS fail,
              AVG(200) AS avg_latency -- Mock latency as callback don't store it
            FROM game_callback_logs
            WHERE client_uuid = ? AND created_at >= (NOW() - INTERVAL 24 HOUR)
        ");
        $stmt->execute([$uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Active sessions can still be from game_sessions or approximated
        $stmt2 = $this->pdo->prepare("SELECT COUNT(*) AS c FROM game_sessions WHERE client_uuid = ? AND updated_at >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt2->execute([$uuid]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'requests_24h' => (int)($row['reqs'] ?? 0),
            'success_24h' => (int)($row['success'] ?? 0),
            'fail_24h' => (int)($row['fail'] ?? 0),
            'avg_latency_24h' => (int)round((float)($row['avg_latency'] ?? 0)),
            'active_sessions' => (int)($row2['c'] ?? 0),
        ];
    }

    public function getProviderStats($clientId) {
        $client = $this->getClientByClientId((string)$clientId);
        if (!$client) return [];

        $blocked = json_decode((string)($client['blocked_providers'] ?? '[]'), true);
        if (!is_array($blocked)) $blocked = [];

        $stmt = $this->pdo->query("SELECT * FROM game_providers ORDER BY name ASC");
        $allProviders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        $stats = [
            'total_providers' => 0,
            'enabled_providers' => 0,
            'total_games' => 0,
            'enabled_games' => 0,
            'providers_list' => []
        ];
        
        foreach ($allProviders as $prov) {
            $brandId = (string)$prov['brand_id'];
            $gameCount = (int)($prov['games_count'] ?? 0);
            
            // Global Status
            $isGlobalActive = strtolower($prov['status']) === 'active';
            
            // Client Status
            $isClientBlocked = in_array($brandId, $blocked);
            $isEnabled = $isGlobalActive && !$isClientBlocked;
            
            $stats['total_providers']++;
            $stats['total_games'] += $gameCount;
            
            if ($isEnabled) {
                $stats['enabled_providers']++;
                $stats['enabled_games'] += $gameCount;
            }
            
            $statusLabel = 'Active';
            if (!$isGlobalActive) $statusLabel = 'System Disabled';
            elseif ($isClientBlocked) $statusLabel = 'Blocked';

            $stats['providers_list'][] = [
                'name' => $prov['name'],
                'logo' => $prov['logo'],
                'games_count' => $gameCount,
                'status' => $statusLabel, 
                'is_enabled' => $isEnabled,
                'rtp' => $prov['rtp']
            ];
        }
        
        return $stats;
    }

    public function getActivePaymentMethods() {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->query("SELECT * FROM payment_methods WHERE status='Active' ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getGamesByProvider($providerName) {
        if (!$this->pdo || !$providerName) return [];
        // First find brand_id by name (or we could pass brand_id if available in frontend)
        $stmt = $this->pdo->prepare("SELECT brand_id FROM game_providers WHERE name = ? LIMIT 1");
        $stmt->execute([$providerName]);
        $brandId = $stmt->fetchColumn();

        if (!$brandId) return [];

        // Assuming id as game_uid for now if game_uid column missing, OR maybe game_name acts as ID
        // The error said 'g.game_uid' incorrect.
        
        // It seems 'games' table has: id, brand_id, game_name, game_img, category, etc.
        // It lacks 'game_uid', 'name', 'img', 'status'.
        // We will map: 
        // game_uid -> id (or combine brand_id + game_name)
        // name -> game_name
        // img -> game_img
        // status -> (assume active if exists)
        
        $stmt = $this->pdo->prepare("SELECT id as game_uid, game_name as name, game_img as img FROM games WHERE brand_id = ?");
        $stmt->execute([$brandId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getGamesList($provider = null) {
        if (!$this->pdo) return [];
        
        $sql = "
            SELECT 
                g.id as game_uid, g.game_name as name, g.game_img as img, g.brand_id, p.name as provider_name
            FROM games g
            LEFT JOIN game_providers p ON g.brand_id = p.brand_id
            WHERE p.status = 'Active'
        ";
        
        $params = [];
        if ($provider) {
             // Supports searching by Provider NAME or BRAND_ID
            $sql .= " AND (p.name = ? OR g.brand_id = ?)";
            $params[] = $provider;
            $params[] = $provider;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getProvidersList() {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->query("SELECT name, brand_id, logo, games_count FROM game_providers WHERE status = 'Active'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
