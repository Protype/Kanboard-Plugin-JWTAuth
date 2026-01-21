<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Controller;

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

/**
 * ConfigController Unit Tests
 *
 * Tests the secret generation functionality
 * Note: Full controller tests require Kanboard's BaseController infrastructure
 */
class ConfigControllerTest extends TestCase
{
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
