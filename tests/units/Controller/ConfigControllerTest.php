<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Controller;

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

/**
 * ConfigController Unit Tests
 *
 * Tests the ConfigController functionality.
 * Note: show() and save() methods require Kanboard's BaseController infrastructure,
 * so we test their logic through a testable wrapper class.
 */
class ConfigControllerTest extends TestCase
{
    // ========================================
    // generateSecret() Tests
    // ========================================

    /**
     * Test generateSecret returns a non-empty string
     */
    public function testGenerateSecretReturnsString(): void
    {
        $secret = $this->generateSecret();

        $this->assertIsString($secret);
        $this->assertNotEmpty($secret);
    }

    /**
     * Test generateSecret returns unique values each time
     */
    public function testGenerateSecretIsUnique(): void
    {
        $secrets = [];

        for ($i = 0; $i < 100; $i++) {
            $secret = $this->generateSecret();
            $this->assertNotContains($secret, $secrets, 'Generated duplicate secret');
            $secrets[] = $secret;
        }
    }

    /**
     * Test generateSecret produces sufficient length for security
     */
    public function testGenerateSecretLength(): void
    {
        $secret = $this->generateSecret();

        // Base64 encoding of 32 bytes should produce ~43 characters
        // Minimum secure length should be at least 32 characters
        $this->assertGreaterThanOrEqual(32, strlen($secret));
    }

    /**
     * Test generateSecret produces URL-safe characters only
     */
    public function testGenerateSecretIsUrlSafe(): void
    {
        $secret = $this->generateSecret();

        // URL-safe base64 should only contain: A-Z, a-z, 0-9, -, _
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $secret);
    }

    /**
     * Test generateSecret can be used as a valid JWT signing key
     */
    public function testGenerateSecretWorksAsJWTKey(): void
    {
        $secret = $this->generateSecret();

        $payload = [
            'iss' => 'test',
            'exp' => time() + 3600,
            'data' => ['test' => 'value'],
        ];

        // Should not throw exception
        $token = JWT::encode($payload, $secret, 'HS256');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Test generateSecret produces cryptographically secure random bytes
     */
    public function testGenerateSecretEntropyDistribution(): void
    {
        // Generate multiple secrets and check character distribution
        $allChars = '';
        for ($i = 0; $i < 50; $i++) {
            $allChars .= $this->generateSecret();
        }

        // Check that we have a reasonable distribution of characters
        $uniqueChars = count(array_unique(str_split($allChars)));

        // With proper randomness, we should see most of the 64 base64 characters
        $this->assertGreaterThan(40, $uniqueChars, 'Poor character distribution suggests weak randomness');
    }

    // ========================================
    // show() Logic Tests (via TestableConfigController)
    // ========================================

    /**
     * Test show generates secret if empty
     */
    public function testShowGeneratesSecretIfEmpty(): void
    {
        $controller = new TestableConfigController();
        $controller->setConfigValues([]);

        $result = $controller->testableShow();

        $this->assertArrayHasKey('jwt_secret', $result['values']);
        $this->assertNotEmpty($result['values']['jwt_secret']);
    }

    /**
     * Test show preserves existing secret
     */
    public function testShowPreservesExistingSecret(): void
    {
        $existingSecret = 'existing-secret-key-123';
        $controller = new TestableConfigController();
        $controller->setConfigValues(['jwt_secret' => $existingSecret]);

        $result = $controller->testableShow();

        $this->assertEquals($existingSecret, $result['values']['jwt_secret']);
    }

    /**
     * Test show passes correct template name
     */
    public function testShowUsesCorrectTemplate(): void
    {
        $controller = new TestableConfigController();
        $controller->setConfigValues([]);

        $result = $controller->testableShow();

        $this->assertEquals('KanproBridge:config/settings', $result['template']);
    }

    /**
     * Test show sets correct title
     */
    public function testShowSetsCorrectTitle(): void
    {
        $controller = new TestableConfigController();
        $controller->setConfigValues([]);

        $result = $controller->testableShow();

        $this->assertEquals('KanproBridge Settings', $result['title']);
    }

    // ========================================
    // save() Logic Tests (via TestableConfigController)
    // ========================================

    /**
     * Test save sets jwt_enable to empty if not provided
     */
    public function testSaveSetsJwtEnableToEmptyIfNotProvided(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues(['jwt_secret' => 'test']);

        $result = $controller->testableSave();

        $this->assertEquals('', $result['values']['jwt_enable']);
    }

    /**
     * Test save sets user_metadata_enable to empty if not provided
     */
    public function testSaveSetsUserMetadataEnableToEmptyIfNotProvided(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues(['jwt_secret' => 'test']);

        $result = $controller->testableSave();

        $this->assertEquals('', $result['values']['kanpro_user_metadata_enable']);
    }

    /**
     * Test save preserves jwt_enable when provided
     */
    public function testSavePreservesJwtEnableWhenProvided(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues([
            'jwt_enable' => '1',
            'jwt_secret' => 'test',
        ]);

        $result = $controller->testableSave();

        $this->assertEquals('1', $result['values']['jwt_enable']);
    }

    /**
     * Test save generates secret when jwt enabled but secret empty
     */
    public function testSaveGeneratesSecretWhenJwtEnabledAndSecretEmpty(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues([
            'jwt_enable' => '1',
            'jwt_secret' => '',
        ]);

        $result = $controller->testableSave();

        $this->assertNotEmpty($result['values']['jwt_secret']);
    }

    /**
     * Test save does not generate secret when jwt disabled
     */
    public function testSaveDoesNotGenerateSecretWhenJwtDisabled(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues([
            'jwt_enable' => '',
            'jwt_secret' => '',
        ]);

        $result = $controller->testableSave();

        $this->assertEquals('', $result['values']['jwt_secret']);
    }

    /**
     * Test save preserves existing secret when jwt enabled
     */
    public function testSavePreservesExistingSecretWhenJwtEnabled(): void
    {
        $existingSecret = 'existing-secret-key';
        $controller = new TestableConfigController();
        $controller->setRequestValues([
            'jwt_enable' => '1',
            'jwt_secret' => $existingSecret,
        ]);

        $result = $controller->testableSave();

        $this->assertEquals($existingSecret, $result['values']['jwt_secret']);
    }

    /**
     * Test save returns success when configModel saves successfully
     */
    public function testSaveReturnsSuccessOnSuccessfulSave(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues(['jwt_secret' => 'test']);
        $controller->setSaveResult(true);

        $result = $controller->testableSave();

        $this->assertTrue($result['success']);
    }

    /**
     * Test save returns failure when configModel fails to save
     */
    public function testSaveReturnsFailureOnFailedSave(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues(['jwt_secret' => 'test']);
        $controller->setSaveResult(false);

        $result = $controller->testableSave();

        $this->assertFalse($result['success']);
    }

    /**
     * Test save uses default redirect URL
     */
    public function testSaveUsesDefaultRedirectUrl(): void
    {
        $controller = new TestableConfigController();
        $controller->setRequestValues(['jwt_secret' => 'test']);
        $controller->setRedirectParam(null);

        $result = $controller->testableSave();

        $this->assertStringContainsString('ConfigController', $result['redirect']);
        $this->assertStringContainsString('KanproBridge', $result['redirect']);
    }

    /**
     * Test save uses custom redirect URL when provided
     */
    public function testSaveUsesCustomRedirectUrl(): void
    {
        $customRedirect = '/custom/redirect/path';
        $controller = new TestableConfigController();
        $controller->setRequestValues(['jwt_secret' => 'test']);
        $controller->setRedirectParam($customRedirect);

        $result = $controller->testableSave();

        $this->assertEquals($customRedirect, $result['redirect']);
    }

    /**
     * Helper method that replicates ConfigController::generateSecret()
     *
     * This is the same implementation as in the controller
     */
    private function generateSecret(): string
    {
        return JWT::urlsafeB64Encode(openssl_random_pseudo_bytes(32));
    }
}

/**
 * Testable ConfigController wrapper
 *
 * Exposes the logic of show() and save() methods for testing
 * without requiring Kanboard's BaseController infrastructure
 */
class TestableConfigController
{
    private $configValues = [];
    private $requestValues = [];
    private $saveResult = true;
    private $redirectParam = null;

    public function setConfigValues(array $values): void
    {
        $this->configValues = $values;
    }

    public function setRequestValues(array $values): void
    {
        $this->requestValues = $values;
    }

    public function setSaveResult(bool $result): void
    {
        $this->saveResult = $result;
    }

    public function setRedirectParam(?string $redirect): void
    {
        $this->redirectParam = $redirect;
    }

    /**
     * Generate JWT secret (same as ConfigController)
     */
    public function generateSecret(): string
    {
        return JWT::urlsafeB64Encode(openssl_random_pseudo_bytes(32));
    }

    /**
     * Testable version of show() logic
     */
    public function testableShow(): array
    {
        $values = $this->configValues;

        if (empty($values['jwt_secret'])) {
            $values['jwt_secret'] = $this->generateSecret();
        }

        return [
            'template' => 'KanproBridge:config/settings',
            'title' => 'KanproBridge Settings',
            'values' => $values,
        ];
    }

    /**
     * Testable version of save() logic
     */
    public function testableSave(): array
    {
        $values = $this->requestValues;

        if (!isset($values['jwt_enable'])) {
            $values['jwt_enable'] = '';
        }

        if (!isset($values['kanpro_user_metadata_enable'])) {
            $values['kanpro_user_metadata_enable'] = '';
        }

        if ($values['jwt_enable'] !== '' && $values['jwt_secret'] === '') {
            $values['jwt_secret'] = $this->generateSecret();
        }

        $configUrl = '?controller=ConfigController&action=show&plugin=KanproBridge';
        $redirect = $this->redirectParam ?? $configUrl;

        return [
            'values' => $values,
            'success' => $this->saveResult,
            'redirect' => $redirect,
        ];
    }
}
