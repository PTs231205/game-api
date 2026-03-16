<?php

class UserStore
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
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(string $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function create(array $user): array
    {
        try {
            // Check existence
            if ($this->find($user['user_id'] ?? '')) {
                return ['ok' => false, 'error' => 'user_exists'];
            }

            $stmt = $this->pdo->prepare("INSERT INTO users (user_id, balance, currency_code, language, created_at) VALUES (?, ?, ?, ?, ?)");
            
            $created = isset($user['created_at']) ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : date('Y-m-d H:i:s');
            
            $stmt->execute([
                $user['user_id'],
                $user['balance'],
                $user['currency_code'] ?? 'USD',
                $user['language'] ?? 'en',
                $created
            ]);

            return ['ok' => true];
        } catch (PDOException $e) {
            return ['ok' => false, 'error' => 'db_error: ' . $e->getMessage()];
        }
    }

    public function delete(string $userId): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'not_found'];
    }

    public function updateBalance(string $userId, float $amount, string $type = 'credit'): array
    {
        // Simple transaction
        try {
            $this->pdo->beginTransaction();
            
            $user = $this->find($userId);
            if (!$user) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'user_not_found'];
            }

            $newBalance = floatval($user['balance']);
            if ($type === 'debit') {
                if ($newBalance < $amount) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'insufficient_funds'];
                }
                $newBalance -= $amount;
            } else {
                $newBalance += $amount;
            }

            $stmt = $this->pdo->prepare("UPDATE users SET balance = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$newBalance, $userId]);
            
            $this->pdo->commit();
            return ['ok' => true, 'new_balance' => $newBalance];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error: ' . $e->getMessage()];
        }
    }
}
