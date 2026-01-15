<?php

namespace Kanboard\Plugin\JWTAuth\Controller;

use Kanboard\Controller\BaseController;
use Firebase\JWT\JWT;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Config Controller for JWT settings
 */
class ConfigController extends BaseController
{
  /**
   * Generate JWT secret
   */
  public function generateSecret()
  {
    return JWT::urlsafeB64Encode(openssl_random_pseudo_bytes(32));
  }

  /**
   * Show settings page
   */
  public function show()
  {
    $values = $this->configModel->getAll();

    if (empty($values['jwt_secret'])) {
      $values['jwt_secret'] = $this->generateSecret();
    }

    $this->response->html($this->helper->layout->config('JWTAuth:config/settings', [
      'title' => t('JWT settings'),
      'values' => $values,
    ]));
  }

  /**
   * Save settings
   */
  public function save()
  {
    $values = $this->request->getValues();

    if (!isset($values['jwt_enable'])) {
      $values['jwt_enable'] = '';
    }

    if ($values['jwt_enable'] !== '' && $values['jwt_secret'] === '') {
      $values['jwt_secret'] = $this->generateSecret();
    }

    $configUrl = $this->helper->url->to('ConfigController', 'show', ['plugin' => 'JWTAuth']);
    $redirect = $this->request->getStringParam('redirect', $configUrl);

    if ($this->configModel->save($values)) {
      $this->flash->success(t('Settings saved successfully.'));
    } else {
      $this->flash->failure(t('Unable to save your settings.'));
    }

    $this->response->redirect($redirect);
  }
}