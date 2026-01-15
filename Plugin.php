<?php

namespace Kanboard\Plugin\JWTAuth;

use Kanboard\Core\Plugin\Base;


/*
 *
 * Plugin
 *
 */
class Plugin extends Base {


  /*
   *
   * Initialize plugin
   *
   */
  public function initialize () {

    // Register JWT config page
    $this->template->hook->attach('template:config:sidebar', 'JWTAuth:config/sidebar');
    $this->hook->on('template:layout:js', array('template' => 'plugins/JWTAuth/Assets/settings.js'));
    $this->hook->on('template:layout:css', array('template' => 'plugins/JWTAuth/Assets/settings.css'));
    
    // Register JWT config route
    $this->route->addRoute('settings/jwtauth', 'ConfigController', 'show', 'JWTAuth');
    
    // Register JWT config controller
    $this->container['configController'] = $this->container->factory(function ($c) {
      return new ConfigController($c);
    });

    // Register revoked token model
    $this->container['jwtRevokedTokenModel'] = function ($c) {
      return new Model\JWTRevokedTokenModel ($c['db']);
    };

    // If JWT authentication is enabled
    if ($this->configModel->get('jwt_enable', '') === '1') {

      $jwtAuthProvider = new Auth\JWTAuthProvider ($this->container);

      // Register JWT plugin info API method
      $this->api->getProcedureHandler()->withClassAndMethod('getJWTPlugin', $this, 'getPluginInfo');

      // Register JWT token generation API method
      $this->api->getProcedureHandler()->withClassAndMethod('getJWTToken', $jwtAuthProvider, 'generateToken');

      // Register JWT refresh token API method
      $this->api->getProcedureHandler()->withClassAndMethod('refreshJWTToken', $jwtAuthProvider, 'refreshToken');

      // Register JWT revoke token API method (own token only)
      $this->api->getProcedureHandler()->withClassAndMethod('revokeJWTToken', $jwtAuthProvider, 'revokeToken');

      // Register JWT revoke user tokens API method (admin only)
      $this->api->getProcedureHandler()->withClassAndMethod('revokeUserJWTTokens', $jwtAuthProvider, 'revokeUserTokens');

      // Register JWT revoke all tokens API method (admin only)
      $this->api->getProcedureHandler()->withClassAndMethod('revokeAllJWTTokens', $jwtAuthProvider, 'revokeAllTokens');

      // Register JWT authentication provider
      $this->authenticationManager->register ($jwtAuthProvider);
    }
  }


  /*
   *
   * Get plugin name
   *
   */
  public function getPluginName () {
    return 'JWTAuth';
  }


  /*
   *
   * Get plugin author
   *
   */
  public function getPluginAuthor () {
    return 'Protype';
  }


  /*
   *
   * Get plugin version
   *
   */
  public function getPluginVersion () {
    return '1.2.0';
  }


  /*
   *
   * Get plugin description
   *
   */
  public function getPluginDescription () {
    return 'Provide JWT authentication for Kanboard API';
  }


  /*
   *
   * Get plugin homepage
   *
   */
  public function getPluginHomepage () {
    return 'https://github.com/Protype/Kanboard-Plugin-JWTAuth';
  }


  /*
   *
   * Get plugin info for API
   *
   */
  public function getPluginInfo () {
    return [
      'name' => $this->getPluginName(),
      'version' => $this->getPluginVersion(),
      'description' => $this->getPluginDescription(),
      'methods' => [
        ['name' => 'getJWTPlugin', 'description' => 'Get plugin info and available methods'],
        ['name' => 'getJWTToken', 'description' => 'Get access + refresh tokens'],
        ['name' => 'refreshJWTToken', 'description' => 'Exchange refresh token for new access token'],
        ['name' => 'revokeJWTToken', 'description' => 'Revoke a specific token'],
        ['name' => 'revokeUserJWTTokens', 'description' => 'Revoke all tokens for a specific user (admin only)'],
        ['name' => 'revokeAllJWTTokens', 'description' => 'Revoke all tokens (admin only)'],
      ],
    ];
  }
}