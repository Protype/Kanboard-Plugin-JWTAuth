<?php

namespace Kanboard\Plugin\JWTAuth\Tests\Units\Auth;

use Kanboard\Plugin\JWTAuth\Tests\Units\Base;
use Kanboard\Plugin\JWTAuth\Auth\JWTAuthProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWTAuthProvider Unit Tests
 */
class JWTAuthProviderTest extends Base
{
    /**
     * @var string Test secret key
     */
    private $testSecret = 'test-secret-key-for-unit-tests';

    /**
     * Set up test environment with JWT configuration
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set default JWT configuration
        $this->setConfig('jwt_enable', '1');
        $this->setConfig('jwt_secret', $this->testSecret);
        $this->setConfig('jwt_issuer', 'http://test.local/');
        $this->setConfig('jwt_audience', 'http://test.local/');
        $this->setConfig('jwt_expiration', 3600); // 1 hour
    }

    /**
     * Test getName returns correct provider name
     */
    public function testGetName(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $this->assertEquals('JWTAuth', $provider->getName());
    }

    /**
     * Test generateToken returns a valid JWT string
     */
    public function testGenerateTokenReturnsValidJWT(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // JWT has 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test generateToken contains correct user data
     */
    public function testGenerateTokenContainsUserData(): void
    {
        $this->setUserSession([
            'id' => 42,
            'username' => 'testuser',
        ]);

        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Decode and verify payload
        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));

        $this->assertEquals(42, $decoded->data->id);
        $this->assertEquals('testuser', $decoded->data->username);
    }

    /**
     * Test generateToken includes correct claims
     */
    public function testGenerateTokenContainsCorrectClaims(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));

        $this->assertEquals('http://test.local/', $decoded->iss);
        $this->assertEquals('http://test.local/', $decoded->aud);
        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertObjectHasProperty('nbf', $decoded);
        $this->assertObjectHasProperty('exp', $decoded);
    }

    /**
     * Test generateToken auto-creates secret when empty
     */
    public function testGenerateTokenAutoCreatesSecret(): void
    {
        // Clear the secret
        $this->setConfig('jwt_secret', '');

        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Token should still be generated
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Secret should now be set
        $newSecret = $this->getConfig('jwt_secret');
        $this->assertNotEmpty($newSecret);
    }

    /**
     * Test verifyToken with valid token
     */
    public function testVerifyTokenWithValidToken(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Set username to match the token
        $provider->setUsername('admin');

        $result = $provider->verifyToken($token);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('admin', $result['username']);
    }

    /**
     * Test verifyToken with invalid token format
     */
    public function testVerifyTokenWithInvalidToken(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $provider->setUsername('admin');

        $result = $provider->verifyToken('invalid.token.string');

        $this->assertFalse($result);
    }

    /**
     * Test verifyToken with completely malformed token
     */
    public function testVerifyTokenWithMalformedToken(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $provider->setUsername('admin');

        $result = $provider->verifyToken('not-a-jwt');

        $this->assertFalse($result);
    }

    /**
     * Test verifyToken with expired token
     */
    public function testVerifyTokenWithExpiredToken(): void
    {
        // Create an expired token manually
        $payload = [
            'iss' => 'http://test.local/',
            'aud' => 'http://test.local/',
            'iat' => time() - 7200,
            'nbf' => time() - 7200,
            'exp' => time() - 3600, // Expired 1 hour ago
            'data' => [
                'id' => 1,
                'username' => 'admin',
            ],
        ];

        $expiredToken = JWT::encode($payload, $this->testSecret, 'HS256');

        $provider = new JWTAuthProvider($this->container);
        $provider->setUsername('admin');

        $result = $provider->verifyToken($expiredToken);

        $this->assertFalse($result);
    }

    /**
     * Test verifyToken with wrong secret
     */
    public function testVerifyTokenWithWrongSecret(): void
    {
        // Generate token with a different secret
        $payload = [
            'iss' => 'http://test.local/',
            'aud' => 'http://test.local/',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'data' => [
                'id' => 1,
                'username' => 'admin',
            ],
        ];

        $tokenWithDifferentSecret = JWT::encode($payload, 'different-secret', 'HS256');

        $provider = new JWTAuthProvider($this->container);
        $provider->setUsername('admin');

        $result = $provider->verifyToken($tokenWithDifferentSecret);

        $this->assertFalse($result);
    }

    /**
     * Test verifyToken with username mismatch
     */
    public function testVerifyTokenWithUsernameMismatch(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Set a different username than what's in the token
        $provider->setUsername('different_user');

        $result = $provider->verifyToken($token);

        $this->assertFalse($result);
    }

    /**
     * Test verifyToken fails when secret is not set
     */
    public function testVerifyTokenFailsWithoutSecret(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // Clear the secret after token generation
        $this->setConfig('jwt_secret', '');

        $provider->setUsername('admin');
        $result = $provider->verifyToken($token);

        $this->assertFalse($result);
    }

    /**
     * Test authenticate with valid token
     */
    public function testAuthenticateWithValidToken(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $provider->setUsername('admin');
        $provider->setPassword($token);

        $result = $provider->authenticate();

        $this->assertTrue($result);
    }

    /**
     * Test authenticate with invalid token
     */
    public function testAuthenticateWithInvalidToken(): void
    {
        $provider = new JWTAuthProvider($this->container);

        $provider->setUsername('admin');
        $provider->setPassword('invalid-token');

        $result = $provider->authenticate();

        $this->assertFalse($result);
    }

    /**
     * Test authenticate with empty token
     */
    public function testAuthenticateWithEmptyToken(): void
    {
        $provider = new JWTAuthProvider($this->container);

        $provider->setUsername('admin');
        $provider->setPassword('');

        $result = $provider->authenticate();

        $this->assertFalse($result);
    }

    /**
     * Test getUser returns null before authentication
     */
    public function testGetUserReturnsNullBeforeAuth(): void
    {
        $provider = new JWTAuthProvider($this->container);

        $user = $provider->getUser();

        $this->assertNull($user);
    }

    /**
     * Test getUser returns DatabaseUserProvider after successful authentication
     */
    public function testGetUserReturnsDatabaseUserProvider(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $provider->setUsername('admin');
        $provider->setPassword($token);
        $provider->authenticate();

        $user = $provider->getUser();

        $this->assertInstanceOf('Kanboard\User\DatabaseUserProvider', $user);
    }

    /**
     * Test getUser returns null after failed authentication
     */
    public function testGetUserReturnsNullAfterFailedAuth(): void
    {
        $provider = new JWTAuthProvider($this->container);

        $provider->setUsername('admin');
        $provider->setPassword('invalid-token');
        $provider->authenticate();

        $user = $provider->getUser();

        $this->assertNull($user);
    }

    /**
     * Test token with default expiration when not configured
     */
    public function testTokenUsesDefaultExpiration(): void
    {
        // Remove expiration config
        $this->setConfig('jwt_expiration', '');

        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));

        // Default is 259200 seconds (3 days)
        $expectedExp = $decoded->iat + 259200;
        $this->assertEquals($expectedExp, $decoded->exp);
    }

    /**
     * Test token uses application URL as default issuer
     */
    public function testTokenUsesApplicationUrlAsDefaultIssuer(): void
    {
        // Clear issuer config but set application URL
        $this->setConfig('jwt_issuer', '');
        $this->setConfig('application_url', 'http://myapp.example.com/');

        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));

        $this->assertEquals('http://myapp.example.com/', $decoded->iss);
    }

    /**
     * Test setUsername and setPassword store values correctly
     */
    public function testSettersStoreValuesCorrectly(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $token = $provider->generateToken();

        // These should not throw exceptions
        $provider->setUsername('testuser');
        $provider->setPassword($token);

        // Verify by checking that authenticate works with matching username
        $this->setUserSession(['username' => 'testuser']);
        $newProvider = new JWTAuthProvider($this->container);
        $newToken = $newProvider->generateToken();

        $newProvider->setUsername('testuser');
        $newProvider->setPassword($newToken);

        $this->assertTrue($newProvider->authenticate());
    }

    // ========================================
    // Phase 1.1: Dual Token Structure Tests
    // ========================================

    /**
     * Test generateToken returns both access and refresh tokens
     */
    public function testGenerateTokenReturnsAccessAndRefreshTokens(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->generateToken();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertNotEmpty($result['access_token']);
        $this->assertNotEmpty($result['refresh_token']);
    }

    /**
     * Test access token has correct type claim
     */
    public function testAccessTokenHasCorrectType(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->generateToken();

        $decoded = JWT::decode($result['access_token'], new Key($this->testSecret, 'HS256'));

        $this->assertObjectHasProperty('type', $decoded);
        $this->assertEquals('access', $decoded->type);
    }

    /**
     * Test refresh token has correct type claim
     */
    public function testRefreshTokenHasCorrectType(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->generateToken();

        $decoded = JWT::decode($result['refresh_token'], new Key($this->testSecret, 'HS256'));

        $this->assertObjectHasProperty('type', $decoded);
        $this->assertEquals('refresh', $decoded->type);
    }

    /**
     * Test access token expires in configured time (default 1 hour)
     */
    public function testAccessTokenExpiresInOneHour(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->generateToken();

        $decoded = JWT::decode($result['access_token'], new Key($this->testSecret, 'HS256'));

        $expectedExp = $decoded->iat + 3600;
        $this->assertEquals($expectedExp, $decoded->exp);
    }

    /**
     * Test refresh token expires in configured time (default 30 days)
     */
    public function testRefreshTokenExpiresInThirtyDays(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->generateToken();

        $decoded = JWT::decode($result['refresh_token'], new Key($this->testSecret, 'HS256'));

        $expectedExp = $decoded->iat + 2592000;
        $this->assertEquals($expectedExp, $decoded->exp);
    }

    /**
     * Test both tokens have unique JTI (JWT ID)
     */
    public function testTokensHaveUniqueJti(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->generateToken();

        $accessDecoded = JWT::decode($result['access_token'], new Key($this->testSecret, 'HS256'));
        $refreshDecoded = JWT::decode($result['refresh_token'], new Key($this->testSecret, 'HS256'));

        $this->assertObjectHasProperty('jti', $accessDecoded);
        $this->assertObjectHasProperty('jti', $refreshDecoded);
        $this->assertNotEmpty($accessDecoded->jti);
        $this->assertNotEmpty($refreshDecoded->jti);
        $this->assertNotEquals($accessDecoded->jti, $refreshDecoded->jti);
    }

    // ========================================
    // Phase 1.2: Refresh Token Tests
    // ========================================

    /**
     * Test refreshToken with valid refresh token returns new tokens
     */
    public function testRefreshTokenWithValidRefreshToken(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        $result = $provider->refreshToken($tokens['refresh_token']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
    }

    /**
     * Test refreshToken returns new access token with correct type
     */
    public function testRefreshTokenReturnsNewAccessToken(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        $result = $provider->refreshToken($tokens['refresh_token']);

        $decoded = JWT::decode($result['access_token'], new Key($this->testSecret, 'HS256'));
        $this->assertEquals('access', $decoded->type);
    }

    /**
     * Test refreshToken fails when using access token instead of refresh token
     */
    public function testRefreshTokenFailsWithAccessToken(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        $result = $provider->refreshToken($tokens['access_token']);

        $this->assertFalse($result);
    }

    /**
     * Test refreshToken fails with expired refresh token
     */
    public function testRefreshTokenFailsWithExpiredToken(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        // Create an expired refresh token manually
        $payload = [
            'jti' => bin2hex(random_bytes(16)),
            'type' => 'refresh',
            'iss' => 'http://test.local/',
            'aud' => 'http://test.local/',
            'iat' => time() - 3600000,
            'nbf' => time() - 3600000,
            'exp' => time() - 3600, // Expired
            'data' => [
                'id' => 1,
                'username' => 'admin',
            ],
        ];

        $expiredRefreshToken = JWT::encode($payload, $this->testSecret, 'HS256');

        $provider = new JWTAuthProvider($this->container);
        $result = $provider->refreshToken($expiredRefreshToken);

        $this->assertFalse($result);
    }

    /**
     * Test refreshToken fails with invalid token
     */
    public function testRefreshTokenFailsWithInvalidToken(): void
    {
        $provider = new JWTAuthProvider($this->container);
        $result = $provider->refreshToken('invalid-token-string');

        $this->assertFalse($result);
    }

    // ========================================
    // Phase 1.3: Revoke Token Tests
    // ========================================

    /**
     * Test revokeToken successfully revokes a token
     */
    public function testRevokeTokenSuccess(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        $result = $provider->revokeToken($tokens['access_token']);

        $this->assertTrue($result);
    }

    /**
     * Test revoked token cannot be used for authentication
     */
    public function testRevokedTokenCannotAuthenticate(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        // Revoke the access token
        $provider->revokeToken($tokens['access_token']);

        // Try to authenticate with revoked token
        $provider->setUsername('admin');
        $provider->setPassword($tokens['access_token']);

        $result = $provider->authenticate();

        $this->assertFalse($result);
    }

    /**
     * Test revoked refresh token cannot be used to refresh
     */
    public function testRevokedRefreshTokenCannotRefresh(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);
        $tokens = $provider->generateToken();

        // Revoke the refresh token
        $provider->revokeToken($tokens['refresh_token']);

        // Try to refresh with revoked token
        $result = $provider->refreshToken($tokens['refresh_token']);

        $this->assertFalse($result);
    }

    /**
     * Test revokeAllTokens revokes all tokens for a user
     */
    public function testRevokeAllUserTokens(): void
    {
        $this->setConfig('jwt_access_expiration', 3600);
        $this->setConfig('jwt_refresh_expiration', 2592000);

        $provider = new JWTAuthProvider($this->container);

        // Generate multiple token pairs
        $tokens1 = $provider->generateToken();
        $tokens2 = $provider->generateToken();

        // Revoke all tokens for user
        $result = $provider->revokeAllTokens(1);

        $this->assertTrue($result);

        // Both should fail authentication
        $provider->setUsername('admin');
        $provider->setPassword($tokens1['access_token']);
        $this->assertFalse($provider->authenticate());

        $provider->setPassword($tokens2['access_token']);
        $this->assertFalse($provider->authenticate());
    }
}
