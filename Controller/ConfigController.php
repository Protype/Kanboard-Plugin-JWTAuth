<?php

namespace Kanboard\Plugin\KanproBridge\Controller;

use Kanboard\Controller\BaseController;
use Firebase\JWT\JWT;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Config Controller for KanproBridge settings
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

    $this->response->html($this->helper->layout->config('KanproBridge:config/settings', [
      'title' => t('KanproBridge Settings'),
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

    if (!isset($values['kanpro_user_metadata_enable'])) {
      $values['kanpro_user_metadata_enable'] = '';
    }

    if ($values['jwt_enable'] !== '' && $values['jwt_secret'] === '') {
      $values['jwt_secret'] = $this->generateSecret();
    }

    $configUrl = $this->helper->url->to('ConfigController', 'show', ['plugin' => 'KanproBridge']);
    $redirect = $this->request->getStringParam('redirect', $configUrl);

    if ($this->configModel->save($values)) {
      $this->flash->success(t('Settings saved successfully.'));
    } else {
      $this->flash->failure(t('Unable to save your settings.'));
    }

    $this->response->redirect($redirect);
  }
}
