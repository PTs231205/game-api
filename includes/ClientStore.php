<?php

class ClientStore
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        // Select 'uuid as id' to match previous structure
        $stmt = $this->pdo->query("SELECT *, uuid as id FROM clients ORDER BY created_at DESC");
        // Decode JSON fields
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$r) {
            $r['ip_whitelist'] = json_decode($r['ip_whitelist'] ?? '[]', true);
            $r['provider'] = json_decode($r['provider_config'] ?? '{}', true);
            // Optionally, we could map provider_config to 'provider'
        }
        return $results ?: [];
    }

    public function findByClientId(string $clientId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT *, uuid as id FROM clients WHERE client_id = ? LIMIT 1");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $client['ip_whitelist'] = json_decode($client['ip_whitelist'] ?? '[]', true);
            $client['provider'] = json_decode($client['provider_config'] ?? '{}', true);
            return $client;
        }
        return null;
    }

    public function findByApiKey(string $apiKey): ?array
    {
        $stmt = $this->pdo->prepare("SELECT *, uuid as id FROM clients WHERE api_key = ? LIMIT 1");
        $stmt->execute([$apiKey]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $client['ip_whitelist'] = json_decode($client['ip_whitelist'] ?? '[]', true);
            $client['provider'] = json_decode($client['provider_config'] ?? '{}', true);
            return $client;
        }
        return null;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT *, uuid as id FROM clients WHERE uuid = ? LIMIT 1");
        $stmt->execute([$id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $client['ip_whitelist'] = json_decode($client['ip_whitelist'] ?? '[]', true);
            $client['provider'] = json_decode($client['provider_config'] ?? '{}', true);
            return $client;
        }
        return null;
    }

    public function create(array $client): array
    {
        try {
            // Check existence
            if ($this->findByClientId($client['client_id'] ?? '')) {
                return ['ok' => false, 'error' => 'client_id_exists'];
            }
            if ($this->findByApiKey($client['api_key'] ?? '')) {
                return ['ok' => false, 'error' => 'api_key_exists'];
            }

            $stmt = $this->pdo->prepare("INSERT INTO clients (uuid, name, client_id, password_hash, status, api_key, client_secret, ip_whitelist_enabled, ip_whitelist, provider_config, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $created = isset($client['created_at']) ? date('Y-m-d H:i:s', strtotime($client['created_at'])) : date('Y-m-d H:i:s');
            $ips = isset($client['ip_whitelist']) ? json_encode($client['ip_whitelist']) : '[]';
            $provider = isset($client['provider']) ? json_encode($client['provider']) : '{}';
            
            $id = $client['id'] ?? ('c_' . bin2hex(random_bytes(4)));
            $clientSecret = (string)($client['client_secret'] ?? '');
            if ($clientSecret === '') {
                $clientSecret = bin2hex(random_bytes(16));
            }
            $ipWhitelistEnabled = !empty($client['ip_whitelist_enabled']) ? 1 : 0;

            $stmt->execute([
                $id,
                $client['name'],
                $client['client_id'],
                $client['password_hash'],
                $client['status'] ?? 'Active',
                $client['api_key'],
                $clientSecret,
                $ipWhitelistEnabled,
                $ips,
                $provider,
                $created
            ]);

            return ['ok' => true];
        } catch (PDOException $e) {
            return ['ok' => false, 'error' => 'db_error: ' . $e->getMessage()];
        }
    }

    public function delete(string $id): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM clients WHERE uuid = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'not_found'];
    }

    public function update(string $id, array $patch): array
    {
        try {
            $existing = $this->findById($id);
            if (!$existing) {
                return ['ok' => false, 'error' => 'not_found'];
            }
            
            $set = [];
            $vars = [];
            
            if (isset($patch['name'])) { $set[] = 'name = ?'; $vars[] = $patch['name']; }
            if (isset($patch['status'])) { $set[] = 'status = ?'; $vars[] = $patch['status']; }
            if (isset($patch['api_key'])) { $set[] = 'api_key = ?'; $vars[] = $patch['api_key']; }
            if (isset($patch['password_hash'])) { $set[] = 'password_hash = ?'; $vars[] = $patch['password_hash']; }
            if (isset($patch['provider'])) { $set[] = 'provider_config = ?'; $vars[] = json_encode($patch['provider']); }
            if (isset($patch['ip_whitelist'])) { $set[] = 'ip_whitelist = ?'; $vars[] = json_encode($patch['ip_whitelist']); }
            if (isset($patch['ip_whitelist_enabled'])) { $set[] = 'ip_whitelist_enabled = ?'; $vars[] = (int)$patch['ip_whitelist_enabled']; }
            if (isset($patch['wallet_balance'])) { $set[] = 'wallet_balance = ?'; $vars[] = $patch['wallet_balance']; }
            if (isset($patch['ggr_balance'])) { $set[] = 'ggr_balance = ?'; $vars[] = $patch['ggr_balance']; }
            if (isset($patch['blocked_providers'])) { $set[] = 'blocked_providers = ?'; $vars[] = json_encode($patch['blocked_providers']); }
            
            if (empty($set)) return ['ok' => true];
            
            $sql = "UPDATE clients SET " . implode(', ', $set) . " WHERE uuid = ?";
            $vars[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($vars);
            
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['ok' => false, 'error' => 'db_error: ' . $e->getMessage()];
        }
    }
}
