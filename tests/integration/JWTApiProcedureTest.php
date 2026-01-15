<?php

namespace Kanboard\Plugin\JWTAuth\Tests\Integration;

use Kanboard\Plugin\JWTAuth\Tests\Units\Base;
use Kanboard\Plugin\JWTAuth\Auth\JWTAuthProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT API Procedure Integration Tests
 *
 * Tests the complete JWT authentication workflow
 */
class JWTApiProcedureTest extends Base
{
    /**
     * @var string Test secret key
     */
    private $testSecret = 'integration-test-secret-key';

    /**
     * Set up integration test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfig('jwt_enable', '1');
        $this->setConfig('jwt_secret', $this->testSecret);
        $this->setConfig('jwt_issuer', 'http://kanboard.test/');
        $this->setConfig('jwt_audience', 'http://kanboard.test/');
        $this->setConfig('jwt_expiration', 3600);
    }

    /**
     * Test complete flow: get token then authenticate with it
     */
    public function testGetJWTTokenWithValidCredentials(): void
    {
        // Step 1: Simulate authenticated user session (like after basic auth)
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
        ]);

        // Step 2: Generate token (simulates getJWTToken API call)
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Verify token is valid
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verify token contains correct data
        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));
        $this->assertEquals(1, $decoded->data->id);
        $this->assertEquals('admin', $decoded->data->username);
    }

    /**
     * Test using JWT token for subsequent API requests
     */
    public function testUseJWTTokenForApiRequest(): void
    {
        // Step 1: Get token
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
        ]);

        $provider1 = new JWTAuthProvider($this->container);
        $token = $provider1->generateToken();

        // Step 2: Use token for new request (simulates subsequent API call)
        $provider2 = new JWTAuthProvider($this->container);
        $provider2->setUsername('admin');
        $provider2->setPassword($token);

        $authenticated = $provider2->authenticate();

        $this->assertTrue($authenticated);

        // Verify user data is accessible
        $user = $provider2->getUser();
        $this->assertNotNull($user);
        $this->assertInstanceOf('Kanboard\User\DatabaseUserProvider', $user);
    }

    /**
     * Test expired token returns error
     */
    public function testExpiredTokenReturnsError(): void
    {
        // Create an expired token
        $payload = [
            'iss' => 'http://kanboard.test/',
            'aud' => 'http://kanboard.test/',
            'iat' => time() - 7200,
            'nbf' => time() - 7200,
            'exp' => time() - 3600, // Expired 1 hour ago
            'data' => [
                'id' => 1,
                'username' => 'admin',
            ],
        ];

        $expiredToken = JWT::encode($payload, $this->testSecret, 'HS256');

        // Try to authenticate with expired token
        $provider = new JWTAuthProvider($this->container);
        $provider->setUsername('admin');
        $provider->setPassword($expiredToken);

        $authenticated = $provider->authenticate();

        $this->assertFalse($authenticated);
        $this->assertNull($provider->getUser());
    }

    /**
     * Test token from different user cannot be used
     */
    public function testTokenCannotBeUsedByDifferentUser(): void
    {
        // Generate token for user 'alice'
        $this->setUserSession([
            'id' => 2,
            'username' => 'alice',
        ]);

        $provider1 = new JWTAuthProvider($this->container);
        $token = $provider1->generateToken();

        // Try to use token as user 'bob'
        $provider2 = new JWTAuthProvider($this->container);
        $provider2->setUsername('bob');
        $provider2->setPassword($token);

        $authenticated = $provider2->authenticate();

        $this->assertFalse($authenticated);
    }

    /**
     * Test multiple sequential token generations
     */
    public function testMultipleTokenGenerations(): void
    {
        $tokens = [];

        for ($i = 1; $i <= 5; $i++) {
            $this->setUserSession([
                'id' => $i,
                'username' => "user{$i}",
            ]);

            $provider = new JWTAuthProvider($this->container);
            $token = $provider->generateToken();

            $this->assertNotContains($token, $tokens, 'Generated duplicate token');
            $tokens[] = $token;

            // Verify each token can be authenticated
            $authProvider = new JWTAuthProvider($this->container);
            $authProvider->setUsername("user{$i}");
            $authProvider->setPassword($token);

            $this->assertTrue($authProvider->authenticate());
        }
    }

    /**
     * Test token generation with custom expiration
     */
    public function testTokenWithCustomExpiration(): void
    {
        $customExpiration = 60; // 1 minute
        $this->setConfig('jwt_expiration', $customExpiration);

        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));

        // Verify expiration is approximately iat + custom expiration
        $this->assertEquals($decoded->iat + $customExpiration, $decoded->exp);
    }

    /**
     * Test complete authentication cycle multiple times
     */
    public function testRepeatedAuthenticationCycles(): void
    {
        for ($cycle = 0; $cycle < 3; $cycle++) {
            // Reset user session for each cycle
            $this->setUserSession([
                'id' => 1,
                'username' => 'admin',
            ]);

            // Generate new token
            $generator = new JWTAuthProvider($this->container);
            $token = $generator->generateToken();

            // Authenticate with token
            $authenticator = new JWTAuthProvider($this->container);
            $authenticator->setUsername('admin');
            $authenticator->setPassword($token);

            $this->assertTrue(
                $authenticator->authenticate(),
                "Authentication failed on cycle {$cycle}"
            );

            $user = $authenticator->getUser();
            $this->assertNotNull($user, "User was null on cycle {$cycle}");
        }
    }

    /**
     * Test token with modified payload is rejected
     */
    public function testTamperedTokenIsRejected(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Decode, modify, and re-encode with a different key (tampering)
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);
        $payload['data']['id'] = 999; // Modify user ID
        $parts[1] = base64_encode(json_encode($payload));
        $tamperedToken = implode('.', $parts);

        // Try to authenticate with tampered token
        $authProvider = new JWTAuthProvider($this->container);
        $authProvider->setUsername('admin');
        $authProvider->setPassword($tamperedToken);

        $authenticated = $authProvider->authenticate();

        $this->assertFalse($authenticated);
    }

    /**
     * Test authentication fails when JWT is disabled
     */
    public function testAuthenticationFailsWhenJWTDisabled(): void
    {
        // First generate a valid token
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Disable JWT (this simulates the config being changed)
        $this->setConfig('jwt_enable', '0');

        // Token should still be verifiable (the provider doesn't check jwt_enable)
        // This test documents current behavior
        $authProvider = new JWTAuthProvider($this->container);
        $authProvider->setUsername('admin');
        $authProvider->setPassword($token);

        // Note: The provider itself doesn't check jwt_enable
        // That check is done in Plugin.php when registering the provider
        $this->assertTrue($authProvider->authenticate());
    }

    // ========================================
    // Phase 1.4: Dual Token Integration Tests
    // ========================================

    /**
     * Test complete token lifecycle with dual tokens
     */
    public function testCompleteTokenLifecycle(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
        ]);

        // Step 1: Get initial tokens
        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        $this->assertIsArray($tokens);
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);

        // Step 2: Use access token for authentication
        $authProvider = new JWTAuthProvider($this->container);
        $authProvider->setUsername('admin');
        $authProvider->setPassword($tokens['access_token']);

        $this->assertTrue($authProvider->authenticate());
        $this->assertNotNull($authProvider->getUser());

        // Step 3: Refresh to get new access token
        $newTokens = $provider->refreshToken($tokens['refresh_token']);

        $this->assertIsArray($newTokens);
        $this->assertArrayHasKey('access_token', $newTokens);

        // Step 4: New access token should work
        $newAuthProvider = new JWTAuthProvider($this->container);
        $newAuthProvider->setUsername('admin');
        $newAuthProvider->setPassword($newTokens['access_token']);

        $this->assertTrue($newAuthProvider->authenticate());
    }

    /**
     * Test refresh token flow end-to-end
     */
    public function testRefreshTokenFlow(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
        ]);

        $provider = new JWTAuthProvider($this->container);

        // Get initial tokens
        $tokens = $provider->generateToken();

        // Refresh multiple times
        for ($i = 0; $i < 3; $i++) {
            $newTokens = $provider->refreshToken($tokens['refresh_token']);

            $this->assertIsArray($newTokens, "Refresh failed on iteration {$i}");
            $this->assertArrayHasKey('access_token', $newTokens);

            // Verify new access token works
            $authProvider = new JWTAuthProvider($this->container);
            $authProvider->setUsername('admin');
            $authProvider->setPassword($newTokens['access_token']);

            $this->assertTrue($authProvider->authenticate(), "Auth failed on iteration {$i}");
        }
    }

    /**
     * Test revoke token flow end-to-end
     */
    public function testRevokeTokenFlow(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
        ]);

        $provider = new JWTAuthProvider($this->container);

        // Get tokens
        $tokens = $provider->generateToken();

        // Verify access token works before revocation
        $authProvider = new JWTAuthProvider($this->container);
        $authProvider->setUsername('admin');
        $authProvider->setPassword($tokens['access_token']);
        $this->assertTrue($authProvider->authenticate());

        // Revoke access token
        $this->assertTrue($provider->revokeToken($tokens['access_token']));

        // Verify access token no longer works
        $authProvider2 = new JWTAuthProvider($this->container);
        $authProvider2->setUsername('admin');
        $authProvider2->setPassword($tokens['access_token']);
        $this->assertFalse($authProvider2->authenticate());

        // Verify refresh token still works (wasn't revoked)
        $newTokens = $provider->refreshToken($tokens['refresh_token']);
        $this->assertIsArray($newTokens);
    }

    /**
     * Test revoke all tokens for user (admin only)
     */
    public function testRevokeAllTokensFlow(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $provider = new JWTAuthProvider($this->container);

        // Generate multiple token pairs
        $tokens1 = $provider->generateToken();
        $tokens2 = $provider->generateToken();
        $tokens3 = $provider->generateToken();

        // Revoke all tokens for user 1 (admin method)
        $this->assertTrue($provider->revokeUserTokens(1));

        // All access tokens should fail
        foreach ([$tokens1, $tokens2, $tokens3] as $tokens) {
            $authProvider = new JWTAuthProvider($this->container);
            $authProvider->setUsername('admin');
            $authProvider->setPassword($tokens['access_token']);
            $this->assertFalse($authProvider->authenticate());
        }

        // All refresh tokens should fail
        foreach ([$tokens1, $tokens2, $tokens3] as $tokens) {
            $result = $provider->refreshToken($tokens['refresh_token']);
            $this->assertFalse($result);
        }
    }

    /**
     * Test dual token system with different users
     */
    public function testDualTokenWithMultipleUsers(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $userTokens = [];

        // Generate tokens for multiple users
        for ($userId = 1; $userId <= 3; $userId++) {
            $this->setUserSession([
                'id' => $userId,
                'username' => "user{$userId}",
            ]);

            $provider = new JWTAuthProvider($this->container);
            $userTokens[$userId] = $provider->generateToken();
        }

        // Each user's token should only work for that user
        foreach ($userTokens as $userId => $tokens) {
            $authProvider = new JWTAuthProvider($this->container);
            $authProvider->setUsername("user{$userId}");
            $authProvider->setPassword($tokens['access_token']);

            $this->assertTrue($authProvider->authenticate(), "User {$userId} auth failed");

            // Try with wrong username
            $wrongProvider = new JWTAuthProvider($this->container);
            $wrongProvider->setUsername("wronguser");
            $wrongProvider->setPassword($tokens['access_token']);

            $this->assertFalse($wrongProvider->authenticate());
        }
    }
}
