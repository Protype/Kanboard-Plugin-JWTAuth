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
}
