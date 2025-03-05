<?php

namespace Kanboard\Plugin\JWTAuth\Auth;

use Kanboard\Plugin\JWTAuth\Controller\ConfigController;
use Kanboard\Core\Security\PasswordAuthenticationProviderInterface;
use Kanboard\User\DatabaseUserProvider;
use Firebase\JWT\JWT;


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
   * Generate JWT token
   *
   */
  public function generateToken () {

    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty ($key)) {

      $config = new ConfigController ($this->container);

      // Create secret key and save it to config
      $key = $config->generateSecret ();
      $this->container['configModel']->save (['jwt_secret' => $key]);
    }

    $appUrl = $this->container['configModel']->get ('application_url', $this->container['helper']->url->base ());
    $issuer = $this->container['configModel']->get ('jwt_issuer', $appUrl);
    $audience = $this->container['configModel']->get ('jwt_audience', $appUrl);
    $expiration = $this->container['configModel']->get ('jwt_expiration', 259200); // 3 * 24 * 60 * 60 seconds
    $userSess = $this->container['userSession']->getAll ();

    $payload = array(
      "iss" => $issuer,
      "aud" => $audience,
      "iat" => time(),
      "nbf" => time(),
      "exp" => time() + $expiration,
      "data" => [
        'id' => $userSess['id'],
        'username' => $userSess['username'],
      ]
    );

    return JWT::encode ($payload, $key);
  }


  /*
   *
   * Verify JWT token
   *
   */
  public function verifyToken ($token) {

    $key = $this->container['configModel']->get('jwt_secret', '');

    if (empty ($key))
      return false;

    try {

      $decoded = JWT::decode ($token, $key, array('HS256'));
      $userSess = $decoded->data;

      if (empty ($userSess) || $this->username !== $userSess->username)
        return false;

      return (array) $userSess;
    }
    
    catch (\Exception $e) {
      return false;
    }
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