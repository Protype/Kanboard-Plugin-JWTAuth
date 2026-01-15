<?php

namespace Kanboard\Plugin\JWTAuth\Model;

/**
 * JWT Revoked Token Model
 *
 * Manages the storage and retrieval of revoked JWT tokens
 */
class JWTRevokedTokenModel
{
    /**
     * @var mixed Database connection or storage
     */
    private $db;

    /**
     * @var string Table name
     */
    const TABLE = 'jwt_revoked_tokens';

    /**
     * Constructor
     *
     * @param mixed $db Database connection (PDO or mock)
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Add a revoked token
     *
     * @param string $jti JWT ID
     * @param int $userId User ID
     * @param string $tokenType Token type ('access' or 'refresh')
     * @param int $expiresAt Token expiration timestamp
     * @return bool Success
     */
    public function add(string $jti, int $userId, string $tokenType, int $expiresAt): bool
    {
        return $this->db->table(self::TABLE)->insert([
            'jti' => $jti,
            'user_id' => $userId,
            'token_type' => $tokenType,
            'revoked_at' => time(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if a token is revoked
     *
     * @param string $jti JWT ID
     * @return bool True if revoked
     */
    public function isRevoked(string $jti): bool
    {
        return $this->db->table(self::TABLE)
            ->eq('jti', $jti)
            ->exists();
    }

    /**
     * Revoke all tokens for a user
     *
     * @param int $userId User ID
     * @param array $jtis List of JTIs to revoke
     * @return bool Success
     */
    public function revokeAllByUser(int $userId, array $jtis = []): bool
    {
        // If specific JTIs provided, revoke those
        // Otherwise, mark all user's tokens as potentially revoked
        // by storing a special marker
        foreach ($jtis as $jti => $data) {
            $this->add($jti, $userId, $data['type'], $data['exp']);
        }
        return true;
    }

    /**
     * Clean up expired revoked tokens
     *
     * Removes entries for tokens that have naturally expired
     *
     * @return int Number of rows deleted
     */
    public function cleanup(): int
    {
        return $this->db->table(self::TABLE)
            ->lt('expires_at', time())
            ->remove();
    }

    /**
     * Get all revoked tokens for a user
     *
     * @param int $userId User ID
     * @return array List of revoked tokens
     */
    public function getAllByUser(int $userId): array
    {
        return $this->db->table(self::TABLE)
            ->eq('user_id', $userId)
            ->findAll();
    }

    /**
     * Revoke all tokens in the system
     *
     * Stores a global revocation marker
     *
     * @return bool Success
     */
    public function revokeAll(): bool
    {
        return $this->db->table(self::TABLE)->insert([
            'jti' => '__global_revoke__',
            'user_id' => 0,
            'token_type' => 'all',
            'revoked_at' => time(),
            'expires_at' => time() + 31536000, // 1 year
        ]);
    }

    /**
     * Check if all tokens were revoked at a given time
     *
     * @param int $tokenIssuedAt Token issue timestamp
     * @return bool True if token was issued before global revocation
     */
    public function isAllRevoked(int $tokenIssuedAt): bool
    {
        $record = $this->db->table(self::TABLE)
            ->eq('jti', '__global_revoke__')
            ->findOne();

        return $record && $record['revoked_at'] >= $tokenIssuedAt;
    }
}
