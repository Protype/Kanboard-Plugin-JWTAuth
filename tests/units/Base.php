<?php

namespace Kanboard\Plugin\JWTAuth\Tests\Units;

use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * Base test class for JWTAuth plugin tests
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
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->configStorage = [];
        $this->revokedTokensStorage = [];

        $this->setupConfigModel();
        $this->setupUserSession();
        $this->setupHelper();
        $this->setupRevokedTokenModel();
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
