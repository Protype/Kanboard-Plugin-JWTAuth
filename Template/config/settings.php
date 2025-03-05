<div class="page-header">
  <h2><?= t('JWT settings') ?></h2>
</div>

<form id="JWTAuth" method="post" action="<?= $this->url->href('ConfigController', 'save', ['plugin' => 'JWTAuth']) ?>" autocomplete="off">
  <?= $this->form->csrf() ?>

  <div class="form-group">
    <label for="jwt_enable">
      <input type="checkbox" name="jwt_enable" value="1" id="jwt_enable" class="form-control" <?= ($this->task->configModel->get('jwt_enable', '') === '1') ? 'checked' : '' ?>>
      <?= t('Enable JWT Auth') ?>
    </label>
  </div>

  <div class="form-group">
    <label for="api_key"><?= t('JWT Secret') ?></label>
    <input type="text" name="jwt_secret" id="jwt_secret" class="form-control" value="<?= $this->task->configModel->get('jwt_secret', '') ?>"
      <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
    <p class="form-help"><?= t('Leave it empty if you want to generate a random secret automatically on save') ?></p>
  </div>

  <div class="form-group">
    <label for="jwt_issuer"><?= t('JWT Issuer') ?></label>
    <input type="text" name="jwt_issuer" id="jwt_issuer" class="form-control" value="<?= $this->task->configModel->get('jwt_issuer', ''); ?>"
      <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
  </div>

  <div class="form-group">
    <label for="jwt_audience"><?= t('JWT Audience') ?></label>
    <input type="text" name="jwt_audience" id="jwt_audience" class="form-control" value="<?= $this->task->configModel->get('jwt_audience', '') ?>"
      <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
  </div>

  <div class="form-group">
    <label for="jwt_expiration"><?= t('JWT Expiration (seconds)') ?></label>
    <input type="number" name="jwt_expiration" id="jwt_expiration" class="form-control" value="<?= $this->task->configModel->get('jwt_expiration', '') ?>"
      <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
    <p class="form-help"><?= t('259200 seconds in default (3 days)') ?></p>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
  </div>
</form>
