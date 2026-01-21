<?php

namespace Kanboard\Plugin\KanproBridge\Feature\JWTAuth;

use Kanboard\Plugin\KanproBridge\Controller\ConfigController;
use Kanboard\Core\Security\PasswordAuthenticationProviderInterface;
use Kanboard\User\DatabaseUserProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

/**
 * JWT Authentication provider
 *
 * Supports dual token system (access + refresh tokens)
 */
class Provider implements PasswordAuthenticationProviderInterface
{
  /** @var mixed */
  private $container;

  /** @var string */
  private $username;

  /** @var string */
  private $jwtToken;

  /** @var array */
  private $userInfo;

  /** Default access token expiration (3 days) */
  const DEFAULT_ACCESS_EXPIRATION = 259200;

  /** Default refresh token expiration (30 days) */
  const DEFAULT_REFRESH_EXPIRATION = 2592000;

  /**
   * Constructor
   */
  public function __construct($container)
  {
    $this->container = $container;
  }

  /**
   * Get provider name
   */
  public function getName()
  {
    return 'JWTAuth';
  }

  /**
   * Get JWT secret, auto-generate if empty
   */
  private function getSecret()
  {
    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty($key)) {
      $config = new ConfigController($this->container);
      $key = $config->generateSecret();
      $this->container['configModel']->save(['jwt_secret' => $key]);
    }

    return $key;
  }

  /**
   * Get common token claims
   */
  private function getBaseClaims()
  {
    $appUrl = $this->container['configModel']->get('application_url', '') ?: $this->container['helper']->url->base();
    $issuer = $this->container['configModel']->get('jwt_issuer', '') ?: $appUrl;
    $audience = $this->container['configModel']->get('jwt_audience', '') ?: $appUrl;
    $userSess = $this->container['userSession']->getAll();

    return [
      'iss' => $issuer,
      'aud' => $audience,
      'iat' => time(),
      'nbf' => time(),
      'data' => [
        'id' => $userSess['id'],
        'username' => $userSess['username'],
      ]
    ];
  }

  /**
   * Generate unique JWT ID
   */
  private function generateJti()
  {
    return bin2hex(random_bytes(16));
  }

  /**
   * Generate access token
   */
  public function generateAccessToken()
  {
    $key = $this->getSecret();
    $expiration = $this->container['configModel']->get('jwt_access_expiration', '') ?: self::DEFAULT_ACCESS_EXPIRATION;

    $payload = $this->getBaseClaims();
    $payload['jti'] = $this->generateJti();
    $payload['type'] = 'access';
    $payload['exp'] = time() + (int) $expiration;

    return JWT::encode($payload, $key, 'HS256');
  }

  /**
   * Generate refresh token
   */
  public function generateRefreshToken()
  {
    $key = $this->getSecret();
    $expiration = $this->container['configModel']->get('jwt_refresh_expiration', '') ?: self::DEFAULT_REFRESH_EXPIRATION;

    $payload = $this->getBaseClaims();
    $payload['jti'] = $this->generateJti();
    $payload['type'] = 'refresh';
    $payload['exp'] = time() + (int) $expiration;

    return JWT::encode($payload, $key, 'HS256');
  }

  /**
   * Generate JWT tokens
   *
   * @return array Array with access_token and refresh_token
   */
  public function generateToken()
  {
    return [
      'access_token' => $this->generateAccessToken(),
      'refresh_token' => $this->generateRefreshToken(),
    ];
  }

  /**
   * Decode and validate a token
   *
   * @param string $token JWT token
   * @param string|null $expectedType Expected token type ('access', 'refresh', or null for any)
   * @return object|false Decoded payload or false on failure
   */
  private function decodeToken($token, $expectedType = null)
  {
    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty($key)) {
      return false;
    }

    try {
      $decoded = JWT::decode($token, new Key($key, 'HS256'));

      if ($expectedType !== null && isset($decoded->type) && $decoded->type !== $expectedType) {
        return false;
      }

      if (isset($decoded->jti) && $this->isTokenRevoked($decoded)) {
        return false;
      }

      return $decoded;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Check if a token is revoked
   */
  private function isTokenRevoked($decoded)
  {
    if (!isset($this->container['jwtRevokedTokenModel'])) {
      return false;
    }

    try {
      $model = $this->container['jwtRevokedTokenModel'];

      if ($model->isRevoked($decoded->jti)) {
        return true;
      }

      if (method_exists($model, 'isUserRevoked')) {
        $userId = $decoded->data->id ?? 0;
        $issuedAt = $decoded->iat ?? 0;
        if ($model->isUserRevoked($userId, $issuedAt)) {
          return true;
        }
      }

      return false;
    } catch (\Exception $e) {
      // Table might not exist yet, skip revocation check
      return false;
    }
  }

  /**
   * Verify JWT token
   */
  public function verifyToken($token)
  {
    $decoded = $this->decodeToken($token, 'access');

    if ($decoded === false) {
      return false;
    }

    $userSess = $decoded->data;

    if (empty($userSess) || $this->username !== $userSess->username) {
      return false;
    }

    return (array) $userSess;
  }

  /**
   * Refresh token - exchange refresh token for new access token
   *
   * @param string $refreshToken The refresh token
   * @return array|false New tokens array or false on failure
   */
  public function refreshToken($refreshToken)
  {
    $decoded = $this->decodeToken($refreshToken, 'refresh');

    if ($decoded === false) {
      return false;
    }

    $this->setUserSessionFromToken($decoded);

    return [
      'access_token' => $this->generateAccessToken(),
      'refresh_token' => $this->generateRefreshToken(),
    ];
  }

  /**
   * Set user session data from decoded token
   *
   * Placeholder for session storage interaction during token refresh.
   * Currently a no-op as generateAccessToken uses the existing session.
   */
  private function setUserSessionFromToken($decoded)
  {
    // Intentionally empty - session data comes from existing session
  }

  /**
   * Revoke a specific token (own token only)
   *
   * @param string $token The token to revoke
   * @return bool Success
   */
  public function revokeToken($token)
  {
    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty($key)) {
      return false;
    }

    try {
      $decoded = JWT::decode($token, new Key($key, 'HS256'));

      if (!isset($decoded->jti)) {
        return false;
      }

      if (!isset($this->container['jwtRevokedTokenModel'])) {
        return false;
      }

      $tokenUserId = $decoded->data->id ?? 0;
      $currentUserId = $this->container['userSession']->getId();

      if ($tokenUserId !== $currentUserId) {
        return false;
      }

      $model = $this->container['jwtRevokedTokenModel'];
      $tokenType = $decoded->type ?? 'access';
      $expiresAt = $decoded->exp ?? time() + 3600;

      return $model->add($decoded->jti, $tokenUserId, $tokenType, $expiresAt);
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Revoke all tokens for a specific user (admin only)
   *
   * @param int $userId User ID to revoke tokens for
   * @return bool Success
   */
  public function revokeUserTokens($userId)
  {
    if (!$this->container['userSession']->isAdmin()) {
      return false;
    }

    if (!isset($this->container['jwtRevokedTokenModel'])) {
      return false;
    }

    $model = $this->container['jwtRevokedTokenModel'];
    return $model->revokeAllByUser((int) $userId);
  }

  /**
   * Revoke all tokens in the system (admin only)
   *
   * @return bool Success
   */
  public function revokeAllTokens()
  {
    if (!$this->container['userSession']->isAdmin()) {
      return false;
    }

    if (!isset($this->container['jwtRevokedTokenModel'])) {
      return false;
    }

    $model = $this->container['jwtRevokedTokenModel'];
    return $model->revokeAll();
  }

  /**
   * Authenticate user
   */
  public function authenticate()
  {
    $userSess = $this->verifyToken($this->jwtToken);

    if (!empty($userSess)) {
      $this->userInfo = $userSess;
      return true;
    }

    return false;
  }

  /**
   * Get user object
   */
  public function getUser()
  {
    if (empty($this->userInfo)) {
      return null;
    }

    return new DatabaseUserProvider($this->userInfo);
  }

  /**
   * Set username
   */
  public function setUsername($username)
  {
    $this->username = $username;
  }

  /**
   * Set JWT token via password field
   */
  public function setPassword($jwtToken)
  {
    $this->jwtToken = $jwtToken;
  }
}
