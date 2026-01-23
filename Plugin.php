<?php

namespace Kanboard\Plugin\KanproBridge;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\KanproBridge\Feature\JWTAuth\Provider as JWTAuthProvider;
use Kanboard\Plugin\KanproBridge\Feature\JWTAuth\RevokedTokenModel;
use Kanboard\Plugin\KanproBridge\Feature\UserMetadata\Model as UserMetadataModel;
use Kanboard\Plugin\KanproBridge\Feature\UserAvatar\Model as UserAvatarModel;
use Kanboard\Plugin\KanproBridge\Feature\UserPassword\Model as UserPasswordModel;
use Kanboard\Plugin\KanproBridge\Feature\UserProfile\Model as UserProfileModel;
use Kanboard\Plugin\KanproBridge\Feature\ProjectUser\Model as ProjectUserModel;

/**
 * KanproBridge Plugin for Kanboard
 *
 * Multi-functional bridge plugin connecting Kanboard and Kanpro interface systems
 */
class Plugin extends Base
{
  /**
   * Initialize plugin
   */
  public function initialize()
  {
    $this->template->hook->attach('template:config:sidebar', 'KanproBridge:config/sidebar');
    $this->hook->on('template:layout:js', array('template' => 'plugins/KanproBridge/Assets/settings.js'));
    $this->hook->on('template:layout:css', array('template' => 'plugins/KanproBridge/Assets/settings.css'));

    $this->route->addRoute('settings/kanprobridge', 'ConfigController', 'show', 'KanproBridge');

    $this->container['configController'] = $this->container->factory(function ($c) {
      return new Controller\ConfigController($c);
    });

    $this->container['jwtRevokedTokenModel'] = function ($c) {
      return new RevokedTokenModel($c['db']);
    };

    $this->container['kanproUserMetadataModel'] = function ($c) {
      return new UserMetadataModel($c);
    };

    $this->container['kanproUserAvatarModel'] = function ($c) {
      return new UserAvatarModel($c);
    };

    $this->container['kanproUserPasswordModel'] = function ($c) {
      return new UserPasswordModel($c);
    };

    $this->container['kanproUserProfileModel'] = function ($c) {
      return new UserProfileModel($c);
    };

    $this->container['kanproProjectUserModel'] = function ($c) {
      return new ProjectUserModel($c);
    };

    if ($this->configModel->get('jwt_enable', '') === '1') {
      $this->handleApiAuthHeader();
      $this->registerJWTAuthentication();
    }

    if ($this->configModel->get('kanpro_user_metadata_enable', '') === '1') {
      $this->registerUserMetadataApi();
    }

    if ($this->configModel->get('kanpro_user_avatar_enable', '') === '1') {
      $this->registerUserAvatarApi();
    }

    if ($this->configModel->get('kanpro_user_password_enable', '') === '1') {
      $this->registerUserPasswordApi();
    }

    if ($this->configModel->get('kanpro_user_profile_enable', '') === '1') {
      $this->registerUserProfileApi();
    }

    if ($this->configModel->get('kanpro_project_user_enable', '') === '1') {
      $this->registerProjectUserApi();
    }
  }

  /**
   * Handle API authentication header validation and WWW-Authenticate interception
   *
   * 當使用自定義 header（如 X-API-Auth）時：
   * - Kanboard 核心期望 base64(username:password) 格式
   * - 此函數移除 WWW-Authenticate header 以避免瀏覽器彈出原生認證對話框
   */
  private function handleApiAuthHeader()
  {
    if (!$this->isApiRequest()) {
      return;
    }

    $headerName = defined('API_AUTHENTICATION_HEADER') ? API_AUTHENTICATION_HEADER : 'Authorization';
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
    $authHeader = $_SERVER[$serverKey] ?? null;

    // 如果沒有自定義 header，不做任何處理
    if ($authHeader === null) {
      return;
    }

    // 對於自定義 header，驗證 base64 解碼後是否包含 ':'
    // 跳過 'Basic ' 前綴（如果有的話）
    $headerValue = $authHeader;
    if (stripos($headerValue, 'Basic ') === 0) {
      $headerValue = substr($headerValue, 6);
    }

    $decoded = base64_decode($headerValue, true);
    if ($decoded === false || strpos($decoded, ':') === false) {
      $this->sendJsonRpcError(
        -32600,
        "Invalid $headerName header format. Expected: base64(username:token)"
      );
      exit;
    }

    // 註冊 callback 移除 WWW-Authenticate header
    header_register_callback(function () {
      header_remove('WWW-Authenticate');
    });
  }

  /**
   * Check if current request is an API request
   */
  private function isApiRequest()
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($uri, 'jsonrpc.php') !== false;
  }

  /**
   * Send JSON-RPC error response
   */
  private function sendJsonRpcError($code, $message)
  {
    header('Content-Type: application/json');
    echo json_encode([
      'jsonrpc' => '2.0',
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
      'id' => null,
    ]);
  }

  /**
   * Register JWT authentication provider and API methods
   */
  private function registerJWTAuthentication()
  {
    $jwtAuthProvider = new JWTAuthProvider($this->container);
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('getKanproBridgeStatus', $this, 'getPluginInfo');
    $procedureHandler->withClassAndMethod('getJWTToken', $jwtAuthProvider, 'generateToken');
    $procedureHandler->withClassAndMethod('refreshJWTToken', $jwtAuthProvider, 'refreshToken');
    $procedureHandler->withClassAndMethod('revokeJWTToken', $jwtAuthProvider, 'revokeToken');
    $procedureHandler->withClassAndMethod('revokeUserJWTTokens', $jwtAuthProvider, 'revokeUserTokens');
    $procedureHandler->withClassAndMethod('revokeAllJWTTokens', $jwtAuthProvider, 'revokeAllTokens');

    $this->authenticationManager->register($jwtAuthProvider);
  }

  /**
   * Register User Metadata API methods
   */
  private function registerUserMetadataApi()
  {
    $model = $this->container['kanproUserMetadataModel'];
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('getUserMetadata', $model, 'getAll');
    $procedureHandler->withClassAndMethod('getUserMetadataByName', $model, 'get');
    $procedureHandler->withClassAndMethod('saveUserMetadata', $model, 'save');
    $procedureHandler->withClassAndMethod('removeUserMetadata', $model, 'remove');
  }

  /**
   * Register User Avatar API methods
   */
  private function registerUserAvatarApi()
  {
    $model = $this->container['kanproUserAvatarModel'];
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('uploadUserAvatar', $model, 'upload');
    $procedureHandler->withClassAndMethod('getUserAvatar', $model, 'get');
    $procedureHandler->withClassAndMethod('removeUserAvatar', $model, 'remove');
  }

  /**
   * Register User Password API methods
   */
  private function registerUserPasswordApi()
  {
    $model = $this->container['kanproUserPasswordModel'];
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('changeUserPassword', $model, 'change');
    $procedureHandler->withClassAndMethod('resetUserPassword', $model, 'reset');
  }

  /**
   * Register User Profile API methods
   */
  private function registerUserProfileApi()
  {
    $model = $this->container['kanproUserProfileModel'];
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('getUserProfile', $model, 'get');
    $procedureHandler->withClassAndMethod('updateUserProfile', $model, 'update');
  }

  /**
   * Register Project User API methods
   */
  private function registerProjectUserApi()
  {
    $model = $this->container['kanproProjectUserModel'];
    $procedureHandler = $this->api->getProcedureHandler();

    $procedureHandler->withClassAndMethod('getProjectUsersExtended', $model, 'getProjectUsers');
    $procedureHandler->withClassAndMethod('getAssignableUsersExtended', $model, 'getAssignableUsers');
  }

  /**
   * Get plugin name
   */
  public function getPluginName()
  {
    return 'KanproBridge';
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
    return '2.3.0';
  }

  /**
   * Get plugin description
   */
  public function getPluginDescription()
  {
    return 'Multi-functional bridge plugin connecting Kanboard and Kanpro interface systems';
  }

  /**
   * Get plugin homepage
   */
  public function getPluginHomepage()
  {
    return 'https://github.com/Protype/Kanboard-Plugin-KanproBridge';
  }

  /**
   * Get plugin info for API
   */
  public function getPluginInfo()
  {
    $jwtEnabled = $this->configModel->get('jwt_enable', '') === '1';
    $userMetadataEnabled = $this->configModel->get('kanpro_user_metadata_enable', '') === '1';
    $userAvatarEnabled = $this->configModel->get('kanpro_user_avatar_enable', '') === '1';
    $userPasswordEnabled = $this->configModel->get('kanpro_user_password_enable', '') === '1';
    $userProfileEnabled = $this->configModel->get('kanpro_user_profile_enable', '') === '1';
    $projectUserEnabled = $this->configModel->get('kanpro_project_user_enable', '') === '1';

    return [
      'name' => $this->getPluginName(),
      'version' => $this->getPluginVersion(),
      'description' => $this->getPluginDescription(),
      'features' => [
        'jwt_auth' => [
          'enabled' => $jwtEnabled,
          'methods' => [
            ['name' => 'getKanproBridgeStatus', 'description' => 'Get plugin info and available methods'],
            ['name' => 'getJWTToken', 'description' => 'Get access + refresh tokens'],
            ['name' => 'refreshJWTToken', 'description' => 'Exchange refresh token for new access token'],
            ['name' => 'revokeJWTToken', 'description' => 'Revoke a specific token'],
            ['name' => 'revokeUserJWTTokens', 'description' => 'Revoke all tokens for a specific user (admin only)'],
            ['name' => 'revokeAllJWTTokens', 'description' => 'Revoke all tokens (admin only)'],
          ],
        ],
        'user_metadata' => [
          'enabled' => $userMetadataEnabled,
          'methods' => [
            ['name' => 'getUserMetadata', 'description' => 'Get all metadata for a user'],
            ['name' => 'getUserMetadataByName', 'description' => 'Get a specific metadata value by name'],
            ['name' => 'saveUserMetadata', 'description' => 'Save metadata for a user'],
            ['name' => 'removeUserMetadata', 'description' => 'Remove a specific metadata entry'],
          ],
        ],
        'user_avatar' => [
          'enabled' => $userAvatarEnabled,
          'methods' => [
            ['name' => 'uploadUserAvatar', 'description' => 'Upload avatar image (base64)'],
            ['name' => 'getUserAvatar', 'description' => 'Get avatar image (base64)'],
            ['name' => 'removeUserAvatar', 'description' => 'Remove avatar image'],
          ],
        ],
        'user_password' => [
          'enabled' => $userPasswordEnabled,
          'methods' => [
            ['name' => 'changeUserPassword', 'description' => 'Change own password (requires current password)'],
            ['name' => 'resetUserPassword', 'description' => 'Reset user password (admin only)'],
          ],
        ],
        'user_profile' => [
          'enabled' => $userProfileEnabled,
          'methods' => [
            ['name' => 'getUserProfile', 'description' => 'Get user profile data'],
            ['name' => 'updateUserProfile', 'description' => 'Update user profile fields'],
          ],
        ],
        'project_user' => [
          'enabled' => $projectUserEnabled,
          'methods' => [
            ['name' => 'getProjectUsersExtended', 'description' => 'Get full user objects for project members'],
            ['name' => 'getAssignableUsersExtended', 'description' => 'Get full user objects for assignable users'],
          ],
        ],
      ],
    ];
  }
}
