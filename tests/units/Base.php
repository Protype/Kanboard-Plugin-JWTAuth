<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units;

use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * Base test class for KanproBridge plugin tests
 *
 * Provides a mock Kanboard container with essential services
 */
abstract class Base extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array Mock configuration storage
     */
    protected $configStorage = [];

    /**
     * @var array Mock user session data
     */
    protected $userSessionData = [
        'id' => 1,
        'username' => 'admin',
        'name' => 'Admin',
        'email' => 'admin@localhost',
        'role' => 'app-admin',
    ];

    /**
     * @var array Mock revoked tokens storage
     */
    protected $revokedTokensStorage = [];

    /**
     * @var array Mock user metadata storage
     */
    protected $userMetadataStorage = [];

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->configStorage = [];
        $this->revokedTokensStorage = [];
        $this->userMetadataStorage = [];

        $this->setupConfigModel();
        $this->setupUserSession();
        $this->setupHelper();
        $this->setupRevokedTokenModel();
        $this->setupDatabase();
    }

    /**
     * Set up mock configModel
     */
    protected function setupConfigModel(): void
    {
        $configStorage = &$this->configStorage;

        $configModel = $this->createMock(MockConfigModel::class);

        $configModel->method('get')
            ->willReturnCallback(function ($key, $default = '') use (&$configStorage) {
                return $configStorage[$key] ?? $default;
            });

        $configModel->method('save')
            ->willReturnCallback(function ($values) use (&$configStorage) {
                foreach ($values as $key => $value) {
                    $configStorage[$key] = $value;
                }
                return true;
            });

        $configModel->method('getAll')
            ->willReturnCallback(function () use (&$configStorage) {
                return $configStorage;
            });

        $this->container['configModel'] = $configModel;
    }

    /**
     * Set up mock userSession
     */
    protected function setupUserSession(): void
    {
        $userSessionData = &$this->userSessionData;

        $userSession = $this->createMock(MockUserSession::class);

        $userSession->method('getAll')
            ->willReturnCallback(function () use (&$userSessionData) {
                return $userSessionData;
            });

        $userSession->method('getId')
            ->willReturnCallback(function () use (&$userSessionData) {
                return $userSessionData['id'];
            });

        $userSession->method('isAdmin')
            ->willReturnCallback(function () use (&$userSessionData) {
                return ($userSessionData['role'] ?? '') === 'app-admin';
            });

        $this->container['userSession'] = $userSession;
    }

    /**
     * Set up mock helper
     */
    protected function setupHelper(): void
    {
        $url = new MockUrlHelper();
        $helper = new \stdClass();
        $helper->url = $url;

        $this->container['helper'] = $helper;
    }

    /**
     * Set up mock revoked token model
     */
    protected function setupRevokedTokenModel(): void
    {
        $storage = &$this->revokedTokensStorage;

        $model = new MockRevokedTokenModel($storage);
        $this->container['jwtRevokedTokenModel'] = $model;
    }

    /**
     * Set up mock database for user metadata
     */
    protected function setupDatabase(): void
    {
        $metadataStorage = &$this->userMetadataStorage;

        $db = new MockDatabase($metadataStorage);
        $this->container['db'] = $db;
    }

    /**
     * Set a config value for testing
     */
    protected function setConfig(string $key, $value): void
    {
        $this->configStorage[$key] = $value;
    }

    /**
     * Get a config value
     */
    protected function getConfig(string $key, $default = '')
    {
        return $this->configStorage[$key] ?? $default;
    }

    /**
     * Set user session data for testing
     */
    protected function setUserSession(array $data): void
    {
        $this->userSessionData = array_merge($this->userSessionData, $data);
    }
}

/**
 * Mock interface for ConfigModel
 */
interface MockConfigModel
{
    public function get(string $key, $default = '');
    public function save(array $values): bool;
    public function getAll(): array;
}

/**
 * Mock interface for UserSession
 */
interface MockUserSession
{
    public function getAll(): array;
    public function getId();
    public function isAdmin(): bool;
}

/**
 * Mock URL Helper class
 */
class MockUrlHelper
{
    public function base(): string
    {
        return 'http://localhost/';
    }
}

/**
 * Mock Revoked Token Model for testing
 */
class MockRevokedTokenModel
{
    private $storage;

    public function __construct(array &$storage)
    {
        $this->storage = &$storage;
    }

    public function add(string $jti, int $userId, string $tokenType, int $expiresAt): bool
    {
        $this->storage[$jti] = [
            'jti' => $jti,
            'user_id' => $userId,
            'token_type' => $tokenType,
            'revoked_at' => time(),
            'expires_at' => $expiresAt,
        ];
        return true;
    }

    public function isRevoked(string $jti): bool
    {
        return isset($this->storage[$jti]);
    }

    public function revokeAllByUser(int $userId): bool
    {
        // Mark that all tokens for this user are revoked
        $this->storage["__user_revoked_{$userId}"] = [
            'user_id' => $userId,
            'revoked_at' => time(),
        ];
        return true;
    }

    public function isUserRevoked(int $userId, int $tokenIssuedAt): bool
    {
        $key = "__user_revoked_{$userId}";
        if (isset($this->storage[$key])) {
            // Tokens issued at or before the revocation time are considered revoked
            return $this->storage[$key]['revoked_at'] >= $tokenIssuedAt;
        }
        return false;
    }

    public function cleanup(): int
    {
        $count = 0;
        $now = time();
        foreach ($this->storage as $jti => $data) {
            if (isset($data['expires_at']) && $data['expires_at'] < $now) {
                unset($this->storage[$jti]);
                $count++;
            }
        }
        return $count;
    }

    public function revokeAll(): bool
    {
        $this->storage["__all_revoked"] = [
            'revoked_at' => time(),
        ];
        return true;
    }

    public function isAllRevoked(int $tokenIssuedAt): bool
    {
        if (isset($this->storage["__all_revoked"])) {
            return $this->storage["__all_revoked"]['revoked_at'] >= $tokenIssuedAt;
        }
        return false;
    }
}

/**
 * Mock Database for testing
 */
class MockDatabase
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
        $this->conditions[$column] = $value;
        return $this;
    }

    public function findAll(): array
    {
        if (!isset($this->storage[$this->currentTable])) {
            return [];
        }

        $results = [];
        foreach ($this->storage[$this->currentTable] as $row) {
            $match = true;
            foreach ($this->conditions as $column => $value) {
                if (!isset($row[$column]) || $row[$column] !== $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[] = $row;
            }
        }

        return $results;
    }

    public function findOne()
    {
        $results = $this->findAll();
        return $results[0] ?? null;
    }

    public function exists(): bool
    {
        return $this->findOne() !== null;
    }

    public function insert(array $data): bool
    {
        if (!isset($this->storage[$this->currentTable])) {
            $this->storage[$this->currentTable] = [];
        }

        // Auto-generate ID
        $data['id'] = count($this->storage[$this->currentTable]) + 1;
        $this->storage[$this->currentTable][] = $data;

        return true;
    }

    public function update(array $data): bool
    {
        if (!isset($this->storage[$this->currentTable])) {
            return false;
        }

        foreach ($this->storage[$this->currentTable] as &$row) {
            $match = true;
            foreach ($this->conditions as $column => $value) {
                if (!isset($row[$column]) || $row[$column] !== $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $row = array_merge($row, $data);
                return true;
            }
        }

        return false;
    }

    public function remove(): bool
    {
        if (!isset($this->storage[$this->currentTable])) {
            return false;
        }

        $removed = false;
        $this->storage[$this->currentTable] = array_filter(
            $this->storage[$this->currentTable],
            function ($row) use (&$removed) {
                foreach ($this->conditions as $column => $value) {
                    if (!isset($row[$column]) || $row[$column] !== $value) {
                        return true;
                    }
                }
                $removed = true;
                return false;
            }
        );

        return $removed;
    }
}
