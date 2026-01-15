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
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->configStorage = [];

        $this->setupConfigModel();
        $this->setupUserSession();
        $this->setupHelper();
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
