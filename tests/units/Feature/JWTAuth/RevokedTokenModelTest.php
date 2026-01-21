<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Feature\JWTAuth;

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\KanproBridge\Feature\JWTAuth\RevokedTokenModel;

/**
 * RevokedTokenModel Unit Tests
 */
class RevokedTokenModelTest extends TestCase
{
    /**
     * @var array Mock storage for database
     */
    private $storage = [];

    /**
     * @var MockDb Mock database
     */
    private $db;

    /**
     * @var RevokedTokenModel
     */
    private $model;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = [];
        $this->db = new MockDb($this->storage);
        $this->model = new RevokedTokenModel($this->db);
    }

    // ========================================
    // Constructor Tests
    // ========================================

    /**
     * Test constructor accepts database connection
     */
    public function testConstructorAcceptsDatabase(): void
    {
        $model = new RevokedTokenModel($this->db);
        $this->assertInstanceOf(RevokedTokenModel::class, $model);
    }

    // ========================================
    // add() Tests
    // ========================================

    /**
     * Test add inserts revoked token record
     */
    public function testAddInsertsRevokedToken(): void
    {
        $result = $this->model->add('test-jti-123', 1, 'access', time() + 3600);

        $this->assertTrue($result);
        $this->assertCount(1, $this->storage[RevokedTokenModel::TABLE]);
    }

    /**
     * Test add stores correct data
     */
    public function testAddStoresCorrectData(): void
    {
        $jti = 'unique-jti-456';
        $userId = 42;
        $tokenType = 'refresh';
        $expiresAt = time() + 7200;

        $this->model->add($jti, $userId, $tokenType, $expiresAt);

        $record = $this->storage[RevokedTokenModel::TABLE][0];
        $this->assertEquals($jti, $record['jti']);
        $this->assertEquals($userId, $record['user_id']);
        $this->assertEquals($tokenType, $record['token_type']);
        $this->assertEquals($expiresAt, $record['expires_at']);
        $this->assertArrayHasKey('revoked_at', $record);
    }

    /**
     * Test add sets revoked_at to current time
     */
    public function testAddSetsRevokedAtToCurrentTime(): void
    {
        $timeBefore = time();
        $this->model->add('jti-time-test', 1, 'access', time() + 3600);
        $timeAfter = time();

        $record = $this->storage[RevokedTokenModel::TABLE][0];
        $this->assertGreaterThanOrEqual($timeBefore, $record['revoked_at']);
        $this->assertLessThanOrEqual($timeAfter, $record['revoked_at']);
    }

    /**
     * Test add can insert multiple tokens
     */
    public function testAddCanInsertMultipleTokens(): void
    {
        $this->model->add('jti-1', 1, 'access', time() + 3600);
        $this->model->add('jti-2', 1, 'refresh', time() + 7200);
        $this->model->add('jti-3', 2, 'access', time() + 3600);

        $this->assertCount(3, $this->storage[RevokedTokenModel::TABLE]);
    }

    // ========================================
    // isRevoked() Tests
    // ========================================

    /**
     * Test isRevoked returns true for revoked token
     */
    public function testIsRevokedReturnsTrueForRevokedToken(): void
    {
        $jti = 'revoked-token-jti';
        $this->model->add($jti, 1, 'access', time() + 3600);

        $result = $this->model->isRevoked($jti);

        $this->assertTrue($result);
    }

    /**
     * Test isRevoked returns false for non-revoked token
     */
    public function testIsRevokedReturnsFalseForNonRevokedToken(): void
    {
        $result = $this->model->isRevoked('non-existent-jti');

        $this->assertFalse($result);
    }

    /**
     * Test isRevoked with multiple tokens
     */
    public function testIsRevokedWithMultipleTokens(): void
    {
        $this->model->add('jti-revoked-1', 1, 'access', time() + 3600);
        $this->model->add('jti-revoked-2', 2, 'access', time() + 3600);

        $this->assertTrue($this->model->isRevoked('jti-revoked-1'));
        $this->assertTrue($this->model->isRevoked('jti-revoked-2'));
        $this->assertFalse($this->model->isRevoked('jti-not-revoked'));
    }

    // ========================================
    // revokeAllByUser() Tests
    // ========================================

    /**
     * Test revokeAllByUser returns true
     */
    public function testRevokeAllByUserReturnsTrue(): void
    {
        $result = $this->model->revokeAllByUser(1);

        $this->assertTrue($result);
    }

    /**
     * Test revokeAllByUser with specific JTIs
     */
    public function testRevokeAllByUserWithSpecificJtis(): void
    {
        $jtis = [
            'jti-1' => ['type' => 'access', 'exp' => time() + 3600],
            'jti-2' => ['type' => 'refresh', 'exp' => time() + 7200],
        ];

        $result = $this->model->revokeAllByUser(1, $jtis);

        $this->assertTrue($result);
        $this->assertCount(2, $this->storage[RevokedTokenModel::TABLE]);
        $this->assertTrue($this->model->isRevoked('jti-1'));
        $this->assertTrue($this->model->isRevoked('jti-2'));
    }

    /**
     * Test revokeAllByUser with empty JTIs array
     */
    public function testRevokeAllByUserWithEmptyJtis(): void
    {
        $result = $this->model->revokeAllByUser(1, []);

        $this->assertTrue($result);
        $this->assertEmpty($this->storage[RevokedTokenModel::TABLE] ?? []);
    }

    // ========================================
    // cleanup() Tests
    // ========================================

    /**
     * Test cleanup removes expired tokens
     */
    public function testCleanupRemovesExpiredTokens(): void
    {
        // Add expired token
        $this->model->add('expired-jti', 1, 'access', time() - 3600);
        // Add valid token
        $this->model->add('valid-jti', 1, 'access', time() + 3600);

        $removed = $this->model->cleanup();

        $this->assertEquals(1, $removed);
        $this->assertFalse($this->model->isRevoked('expired-jti'));
        $this->assertTrue($this->model->isRevoked('valid-jti'));
    }

    /**
     * Test cleanup returns zero when no expired tokens
     */
    public function testCleanupReturnsZeroWhenNoExpiredTokens(): void
    {
        $this->model->add('valid-jti-1', 1, 'access', time() + 3600);
        $this->model->add('valid-jti-2', 1, 'access', time() + 7200);

        $removed = $this->model->cleanup();

        $this->assertEquals(0, $removed);
        $this->assertCount(2, $this->storage[RevokedTokenModel::TABLE]);
    }

    /**
     * Test cleanup removes multiple expired tokens
     */
    public function testCleanupRemovesMultipleExpiredTokens(): void
    {
        $this->model->add('expired-1', 1, 'access', time() - 7200);
        $this->model->add('expired-2', 1, 'access', time() - 3600);
        $this->model->add('expired-3', 2, 'refresh', time() - 1800);
        $this->model->add('valid-1', 1, 'access', time() + 3600);

        $removed = $this->model->cleanup();

        $this->assertEquals(3, $removed);
        $this->assertCount(1, $this->storage[RevokedTokenModel::TABLE]);
    }

    // ========================================
    // getAllByUser() Tests
    // ========================================

    /**
     * Test getAllByUser returns user's revoked tokens
     */
    public function testGetAllByUserReturnsUserTokens(): void
    {
        $this->model->add('user1-jti-1', 1, 'access', time() + 3600);
        $this->model->add('user1-jti-2', 1, 'refresh', time() + 7200);
        $this->model->add('user2-jti-1', 2, 'access', time() + 3600);

        $user1Tokens = $this->model->getAllByUser(1);

        $this->assertCount(2, $user1Tokens);
    }

    /**
     * Test getAllByUser returns empty array for user with no tokens
     */
    public function testGetAllByUserReturnsEmptyForUserWithNoTokens(): void
    {
        $this->model->add('other-user-jti', 2, 'access', time() + 3600);

        $result = $this->model->getAllByUser(1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getAllByUser returns correct token data
     */
    public function testGetAllByUserReturnsCorrectData(): void
    {
        $this->model->add('test-jti', 1, 'access', time() + 3600);

        $tokens = $this->model->getAllByUser(1);

        $this->assertCount(1, $tokens);
        $this->assertEquals('test-jti', $tokens[0]['jti']);
        $this->assertEquals(1, $tokens[0]['user_id']);
        $this->assertEquals('access', $tokens[0]['token_type']);
    }

    // ========================================
    // revokeAll() Tests
    // ========================================

    /**
     * Test revokeAll inserts global revocation marker
     */
    public function testRevokeAllInsertsGlobalMarker(): void
    {
        $result = $this->model->revokeAll();

        $this->assertTrue($result);
        $this->assertTrue($this->model->isRevoked('__global_revoke__'));
    }

    /**
     * Test revokeAll marker has correct data
     */
    public function testRevokeAllMarkerHasCorrectData(): void
    {
        $this->model->revokeAll();

        $record = $this->storage[RevokedTokenModel::TABLE][0];
        $this->assertEquals('__global_revoke__', $record['jti']);
        $this->assertEquals(0, $record['user_id']);
        $this->assertEquals('all', $record['token_type']);
    }

    /**
     * Test revokeAll marker expires in one year
     */
    public function testRevokeAllMarkerExpiresInOneYear(): void
    {
        $timeBefore = time();
        $this->model->revokeAll();
        $timeAfter = time();

        $record = $this->storage[RevokedTokenModel::TABLE][0];
        $oneYear = 31536000;

        $this->assertGreaterThanOrEqual($timeBefore + $oneYear, $record['expires_at']);
        $this->assertLessThanOrEqual($timeAfter + $oneYear, $record['expires_at']);
    }

    // ========================================
    // isAllRevoked() Tests
    // ========================================

    /**
     * Test isAllRevoked returns true for token issued before global revocation
     */
    public function testIsAllRevokedReturnsTrueForOldToken(): void
    {
        $this->model->revokeAll();
        $tokenIssuedAt = time() - 3600; // Token issued 1 hour ago

        $result = $this->model->isAllRevoked($tokenIssuedAt);

        $this->assertTrue($result);
    }

    /**
     * Test isAllRevoked returns false when no global revocation
     */
    public function testIsAllRevokedReturnsFalseWhenNoGlobalRevocation(): void
    {
        $result = $this->model->isAllRevoked(time());

        $this->assertFalse($result);
    }

    /**
     * Test isAllRevoked returns false for token issued after global revocation
     */
    public function testIsAllRevokedReturnsFalseForNewToken(): void
    {
        $this->model->revokeAll();

        // Simulate token issued after revocation
        $tokenIssuedAt = time() + 1;

        $result = $this->model->isAllRevoked($tokenIssuedAt);

        $this->assertFalse($result);
    }

    /**
     * Test isAllRevoked boundary case - token issued at exact revocation time
     */
    public function testIsAllRevokedBoundaryCase(): void
    {
        $this->model->revokeAll();
        $record = $this->storage[RevokedTokenModel::TABLE][0];
        $revokedAt = $record['revoked_at'];

        // Token issued at exact same time as revocation
        $result = $this->model->isAllRevoked($revokedAt);

        $this->assertTrue($result);
    }

    // ========================================
    // TABLE Constant Test
    // ========================================

    /**
     * Test TABLE constant value
     */
    public function testTableConstant(): void
    {
        $this->assertEquals('jwt_revoked_tokens', RevokedTokenModel::TABLE);
    }
}

/**
 * Mock Database for RevokedTokenModel tests
 *
 * Simulates Kanboard's database query builder interface
 */
class MockDb
{
    private $storage;
    private $currentTable;
    private $conditions = [];

    public function __construct(array &$storage)
    {
        $this->storage = &$storage;
    }

    public function table(string $tableName)
    {
        $this->currentTable = $tableName;
        $this->conditions = [];
        return $this;
    }

    public function eq(string $column, $value)
    {
        $this->conditions[$column] = ['op' => 'eq', 'value' => $value];
        return $this;
    }

    public function lt(string $column, $value)
    {
        $this->conditions[$column] = ['op' => 'lt', 'value' => $value];
        return $this;
    }

    public function insert(array $data): bool
    {
        if (!isset($this->storage[$this->currentTable])) {
            $this->storage[$this->currentTable] = [];
        }
        $this->storage[$this->currentTable][] = $data;
        return true;
    }

    public function exists(): bool
    {
        return $this->findOne() !== null;
    }

    public function findOne()
    {
        $results = $this->findAll();
        return $results[0] ?? null;
    }

    public function findAll(): array
    {
        if (!isset($this->storage[$this->currentTable])) {
            return [];
        }

        $results = [];
        foreach ($this->storage[$this->currentTable] as $row) {
            if ($this->matchesConditions($row)) {
                $results[] = $row;
            }
        }
        return $results;
    }

    public function remove(): int
    {
        if (!isset($this->storage[$this->currentTable])) {
            return 0;
        }

        $removed = 0;
        $this->storage[$this->currentTable] = array_values(array_filter(
            $this->storage[$this->currentTable],
            function ($row) use (&$removed) {
                if ($this->matchesConditions($row)) {
                    $removed++;
                    return false;
                }
                return true;
            }
        ));

        return $removed;
    }

    private function matchesConditions(array $row): bool
    {
        foreach ($this->conditions as $column => $condition) {
            if (!isset($row[$column])) {
                return false;
            }

            $value = $row[$column];
            switch ($condition['op']) {
                case 'eq':
                    if ($value !== $condition['value']) {
                        return false;
                    }
                    break;
                case 'lt':
                    if ($value >= $condition['value']) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }
}
