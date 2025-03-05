<?php

namespace Kanboard\Plugin\JWTAuth\Controller;

use Kanboard\Controller\BaseController;
use Firebase\JWT\JWT;


/*
 *
 * Require JWT library
 *
 */
require_once dirname (__DIR__) . '/vendor/autoload.php';


/*
 *
 * Config Controller
 *
 */
class ConfigController extends BaseController {


  /*
   *
   * Generate jwt secret
   *
   */
  public function generateSecret () {
    return JWT::urlsafeB64Encode (openssl_random_pseudo_bytes (32));
  }

  
  /*
   *
   * Show settings html
   *
   */
  public function show () {
    $this->response->html ($this->helper->layout->config ('JWTAuth:config/settings', [
      'title' => t('JWT settings'),
      'values' => $this->configModel->getAll (),
    ]));
  }


  /*
   *
   * Save settings
   *
   */
  public function save () {

    $values = $this->request->getValues ();

    if (! isset ($values['jwt_enable']))
      $values['jwt_enable'] = '';

    // Generate secret automatically if jwt is enabled and secret is empty
    if ($values['jwt_enable'] !== '' && $values['jwt_secret'] === '')
      $values['jwt_secret'] = $this->generateSecret ();

    $configUrl = $this->helper->url->to ('ConfigController', 'show', ['plugin' => 'JWTAuth']);
    $redirect = $this->request->getStringParam ('redirect', $configUrl);

    if ($this->configModel->save ($values))
      $this->flash->success (t('Settings saved successfully.'));

    else
      $this->flash->failure (t('Unable to save your settings.'));

    $this->response->redirect ($redirect);
  }
}