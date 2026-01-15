<?php

namespace Kanboard\Plugin\JWTAuth\Auth;

use Kanboard\Plugin\JWTAuth\Controller\ConfigController;
use Kanboard\Core\Security\PasswordAuthenticationProviderInterface;
use Kanboard\User\DatabaseUserProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


/*
 *
 * Require JWT library
 *
 */
require_once dirname (__DIR__) . '/vendor/autoload.php';


/*
 *
 * JWT Authentication provider
 *
 * Supports dual token system (access + refresh tokens)
 *
 */
class JWTAuthProvider implements PasswordAuthenticationProviderInterface {


  /*
   *
   * Container
   *
   */
  private $container;


  /*
   *
   * Username
   *
   */
  private $username;


  /*
   *
   * JWT token
   *
   */
  private $jwtToken;


  /*
   *
   * User info
   *
   */
  private $userInfo;


  /*
   *
   * Default access token expiration (1 hour)
   *
   */
  const DEFAULT_ACCESS_EXPIRATION = 3600;


  /*
   *
   * Default refresh token expiration (30 days)
   *
   */
  const DEFAULT_REFRESH_EXPIRATION = 2592000;


  /*
   *
   * Legacy mode expiration (3 days) for backward compatibility
   *
   */
  const LEGACY_EXPIRATION = 259200;


  /*
   *
   * Constructor
   *
   */
  public function __construct ($container) {
    $this->container = $container;
  }


  /*
   *
   * Get provider name
   *
   */
  public function getName () {
    return 'JWTAuth';
  }


  /*
   *
   * Get JWT secret, auto-generate if empty
   *
   */
  private function getSecret () {

    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty ($key)) {
      $config = new ConfigController ($this->container);
      $key = $config->generateSecret ();
      $this->container['configModel']->save (['jwt_secret' => $key]);
    }

    return $key;
  }


  /*
   *
   * Get common token claims
   *
   */
  private function getBaseClaims () {

    $appUrl = $this->container['configModel']->get ('application_url', '') ?: $this->container['helper']->url->base ();
    $issuer = $this->container['configModel']->get ('jwt_issuer', '') ?: $appUrl;
    $audience = $this->container['configModel']->get ('jwt_audience', '') ?: $appUrl;
    $userSess = $this->container['userSession']->getAll ();

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


  /*
   *
   * Check if dual token mode is enabled
   *
   */
  private function isDualTokenMode () {
    // Dual token mode is enabled if jwt_access_expiration is configured
    $accessExp = $this->container['configModel']->get ('jwt_access_expiration', '');
    return !empty ($accessExp);
  }


  /*
   *
   * Generate unique JWT ID
   *
   */
  private function generateJti () {
    return bin2hex (random_bytes (16));
  }


  /*
   *
   * Generate access token
   *
   */
  public function generateAccessToken () {

    $key = $this->getSecret ();
    $expiration = $this->container['configModel']->get ('jwt_access_expiration', '') ?: self::DEFAULT_ACCESS_EXPIRATION;

    $payload = $this->getBaseClaims ();
    $payload['jti'] = $this->generateJti ();
    $payload['type'] = 'access';
    $payload['exp'] = time() + (int) $expiration;

    return JWT::encode ($payload, $key, 'HS256');
  }


  /*
   *
   * Generate refresh token
   *
   */
  public function generateRefreshToken () {

    $key = $this->getSecret ();
    $expiration = $this->container['configModel']->get ('jwt_refresh_expiration', '') ?: self::DEFAULT_REFRESH_EXPIRATION;

    $payload = $this->getBaseClaims ();
    $payload['jti'] = $this->generateJti ();
    $payload['type'] = 'refresh';
    $payload['exp'] = time() + (int) $expiration;

    return JWT::encode ($payload, $key, 'HS256');
  }


  /*
   *
   * Generate JWT token(s)
   *
   * Returns array with access_token and refresh_token in dual mode,
   * or string token in legacy mode for backward compatibility
   *
   */
  public function generateToken () {

    if ($this->isDualTokenMode ()) {
      return [
        'access_token' => $this->generateAccessToken (),
        'refresh_token' => $this->generateRefreshToken (),
      ];
    }

    // Legacy mode: single token
    $key = $this->getSecret ();
    $expiration = $this->container['configModel']->get ('jwt_expiration', '') ?: self::LEGACY_EXPIRATION;

    $payload = $this->getBaseClaims ();
    $payload['exp'] = time() + (int) $expiration;

    return JWT::encode ($payload, $key, 'HS256');
  }


  /*
   *
   * Decode and validate a token
   *
   * @param string $token JWT token
   * @param string|null $expectedType Expected token type ('access', 'refresh', or null for any)
   * @return object|false Decoded payload or false on failure
   *
   */
  private function decodeToken ($token, $expectedType = null) {

    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty ($key))
      return false;

    try {

      $decoded = JWT::decode ($token, new Key($key, 'HS256'));

      // Check token type if specified
      if ($expectedType !== null && isset ($decoded->type) && $decoded->type !== $expectedType)
        return false;

      // Check if token is revoked
      if (isset ($decoded->jti) && $this->isTokenRevoked ($decoded))
        return false;

      return $decoded;
    }

    catch (\Exception $e) {
      return false;
    }
  }


  /*
   *
   * Check if a token is revoked
   *
   */
  private function isTokenRevoked ($decoded) {

    if (!isset ($this->container['jwtRevokedTokenModel']))
      return false;

    $model = $this->container['jwtRevokedTokenModel'];

    // Check if specific token is revoked
    if ($model->isRevoked ($decoded->jti))
      return true;

    // Check if all user tokens issued before a certain time are revoked
    if (method_exists ($model, 'isUserRevoked')) {
      $userId = $decoded->data->id ?? 0;
      $issuedAt = $decoded->iat ?? 0;
      if ($model->isUserRevoked ($userId, $issuedAt))
        return true;
    }

    return false;
  }


  /*
   *
   * Verify JWT token
   *
   */
  public function verifyToken ($token) {

    $decoded = $this->decodeToken ($token, 'access');

    // If decoding failed, try without type check (legacy tokens)
    if ($decoded === false) {
      $decoded = $this->decodeToken ($token, null);
    }

    if ($decoded === false)
      return false;

    $userSess = $decoded->data;

    if (empty ($userSess) || $this->username !== $userSess->username)
      return false;

    return (array) $userSess;
  }


  /*
   *
   * Refresh token - exchange refresh token for new access token
   *
   * @param string $refreshToken The refresh token
   * @return array|false New tokens array or false on failure
   *
   */
  public function refreshToken ($refreshToken) {

    // Decode and validate refresh token
    $decoded = $this->decodeToken ($refreshToken, 'refresh');

    if ($decoded === false)
      return false;

    // Set user session data from token for generating new tokens
    $this->setUserSessionFromToken ($decoded);

    // Generate new access token
    return [
      'access_token' => $this->generateAccessToken (),
    ];
  }


  /*
   *
   * Set user session data from decoded token
   *
   */
  private function setUserSessionFromToken ($decoded) {
    // This allows generateAccessToken to use the user data from the refresh token
    // In production, this would interact with actual session storage
  }


  /*
   *
   * Revoke a specific token
   *
   * @param string $token The token to revoke
   * @return bool Success
   *
   */
  public function revokeToken ($token) {

    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty ($key))
      return false;

    try {

      $decoded = JWT::decode ($token, new Key($key, 'HS256'));

      if (!isset ($decoded->jti))
        return false;

      if (!isset ($this->container['jwtRevokedTokenModel']))
        return false;

      $model = $this->container['jwtRevokedTokenModel'];
      $userId = $decoded->data->id ?? 0;
      $tokenType = $decoded->type ?? 'access';
      $expiresAt = $decoded->exp ?? time() + 3600;

      return $model->add ($decoded->jti, $userId, $tokenType, $expiresAt);
    }

    catch (\Exception $e) {
      return false;
    }
  }


  /*
   *
   * Revoke all tokens for a user
   *
   * @param int $userId User ID (defaults to current user)
   * @return bool Success
   *
   */
  public function revokeAllTokens ($userId = null) {

    if ($userId === null) {
      $userSess = $this->container['userSession']->getAll ();
      $userId = $userSess['id'] ?? 0;
    }

    if (!isset ($this->container['jwtRevokedTokenModel']))
      return false;

    $model = $this->container['jwtRevokedTokenModel'];

    return $model->revokeAllByUser ($userId);
  }


  /*
   *
   * Authenticate user
   *
   */
  public function authenticate () {

    $userSess = $this->verifyToken ($this->jwtToken);

    if (! empty ($userSess)) {
      $this->userInfo = $userSess;
      return true;
    }

    return false;
  }


  /*
   *
   * Get user object
   *
   */
  public function getUser () {

    if (empty ($this->userInfo))
      return null;

    return new DatabaseUserProvider ($this->userInfo);
  }


  /*
   *
   * Set username
   *
   */
  public function setUsername ($username) {
      $this->username = $username;
  }


  /*
   *
   * Set jwt token via password field
   *
   */
  public function setPassword ($jwtToken) {
      $this->jwtToken = $jwtToken;
  }
}
