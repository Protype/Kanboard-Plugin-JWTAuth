<?php

namespace Kanboard\Plugin\JWTAuth;

use Kanboard\Core\Plugin\Base;

/**
 * JWT Authentication Plugin for Kanboard
 */
class Plugin extends Base
{
  /**
   * Initialize plugin
   */
  public function initialize()
  {
    $this->template->hook->attach('template:config:sidebar', 'JWTAuth:config/sidebar');
    $this->hook->on('template:layout:js', array('template' => 'plugins/JWTAuth/Assets/settings.js'));
    $this->hook->on('template:layout:css', array('template' => 'plugins/JWTAuth/Assets/settings.css'));

    $this->route->addRoute('settings/jwtauth', 'ConfigController', 'show', 'JWTAuth');

    $this->container['configController'] = $this->container->factory(function ($c) {
      return new ConfigController($c);
    });

    $this->container['jwtRevokedTokenModel'] = function ($c) {
      return new Model\JWTRevokedTokenModel($c['db']);
    };

    if ($this->configModel->get('jwt_enable', '') === '1') {
      $this->registerJWTAuthentication();
    }
  }

  /**
   * Register JWT authentication provider and API methods
   */
  private function registerJWTAuthentication()
  {
    $jwtAuthProvider = new Auth\JWTAuthProvider($this->container);
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('getJWTPlugin', $this, 'getPluginInfo');
    $procedureHandler->withClassAndMethod('getJWTToken', $jwtAuthProvider, 'generateToken');
    $procedureHandler->withClassAndMethod('refreshJWTToken', $jwtAuthProvider, 'refreshToken');
    $procedureHandler->withClassAndMethod('revokeJWTToken', $jwtAuthProvider, 'revokeToken');
    $procedureHandler->withClassAndMethod('revokeUserJWTTokens', $jwtAuthProvider, 'revokeUserTokens');
    $procedureHandler->withClassAndMethod('revokeAllJWTTokens', $jwtAuthProvider, 'revokeAllTokens');

    $this->authenticationManager->register($jwtAuthProvider);
  }

  /**
   * Get plugin name
   */
  public function getPluginName()
  {
    return 'JWTAuth';
  }

  /**
   * Get plugin author
   */
  public function getPluginAuthor()
  {
    return 'Protype';
  }

  /**
   * Get plugin version
   */
  public function getPluginVersion()
  {
    return '1.3.0';
  }

  /**
   * Get plugin description
   */
  public function getPluginDescription()
  {
    return 'Provide JWT authentication for Kanboard API';
  }

  /**
   * Get plugin homepage
   */
  public function getPluginHomepage()
  {
    return 'https://github.com/Protype/Kanboard-Plugin-JWTAuth';
  }

  /**
   * Get plugin info for API
   */
  public function getPluginInfo()
  {
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