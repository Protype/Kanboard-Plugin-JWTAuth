<div class="page-header">
  <h2><?= t('KanproBridge Settings') ?></h2>
</div>

<form id="KanproBridge" method="post" action="<?= $this->url->href('ConfigController', 'save', ['plugin' => 'KanproBridge']) ?>" autocomplete="off">
  <?= $this->form->csrf() ?>

  <!-- JWT Authentication Section -->
  <fieldset>
    <legend><?= t('JWT Authentication') ?></legend>

    <div class="form-group">
      <label for="jwt_enable">
        <input type="checkbox" name="jwt_enable" value="1" id="jwt_enable" class="form-control" <?= ($this->task->configModel->get('jwt_enable', '') === '1') ? 'checked' : '' ?>>
        <?= t('Enable JWT Auth') ?>
      </label>
    </div>

    <div class="form-group">
      <label for="jwt_secret"><?= t('JWT Secret') ?> *</label>
      <input type="text" name="jwt_secret" id="jwt_secret" class="form-control" value="<?= $this->task->configModel->get('jwt_secret', '') ?>"
        <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
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
      <label for="jwt_access_expiration"><?= t('Access Token Expiration (seconds)') ?></label>
      <input type="number" name="jwt_access_expiration" id="jwt_access_expiration" class="form-control" value="<?= $this->task->configModel->get('jwt_access_expiration', '') ?>"
        placeholder="259200"
        <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
    </div>

    <div class="form-group">
      <label for="jwt_refresh_expiration"><?= t('Refresh Token Expiration (seconds)') ?></label>
      <input type="number" name="jwt_refresh_expiration" id="jwt_refresh_expiration" class="form-control" value="<?= $this->task->configModel->get('jwt_refresh_expiration', '') ?>"
        placeholder="2592000"
        <?= $this->task->configModel->get('jwt_enable', false) ? '' : 'readonly' ?>>
    </div>
  </fieldset>

  <!-- User Metadata Section -->
  <fieldset>
    <legend><?= t('User Metadata') ?></legend>

    <div class="form-group">
      <label for="kanpro_user_metadata_enable">
        <input type="checkbox" name="kanpro_user_metadata_enable" value="1" id="kanpro_user_metadata_enable" class="form-control" <?= ($this->task->configModel->get('kanpro_user_metadata_enable', '') === '1') ? 'checked' : '' ?>>
        <?= t('Enable User Metadata API') ?>
      </label>
      <p class="form-help"><?= t('Allows storing custom key-value pairs for users. Only the user themselves or administrators can access their metadata.') ?></p>
    </div>
  </fieldset>

  <!-- User Avatar Section -->
  <fieldset>
    <legend><?= t('User Avatar') ?></legend>

    <div class="form-group">
      <label for="kanpro_user_avatar_enable">
        <input type="checkbox" name="kanpro_user_avatar_enable" value="1" id="kanpro_user_avatar_enable" class="form-control" <?= ($this->task->configModel->get('kanpro_user_avatar_enable', '') === '1') ? 'checked' : '' ?>>
        <?= t('Enable User Avatar API') ?>
      </label>
      <p class="form-help"><?= t('Allows uploading and managing user avatars via API. Accepts base64 encoded images (PNG, JPG, GIF).') ?></p>
    </div>
  </fieldset>

  <!-- User Password Section -->
  <fieldset>
    <legend><?= t('User Password') ?></legend>

    <div class="form-group">
      <label for="kanpro_user_password_enable">
        <input type="checkbox" name="kanpro_user_password_enable" value="1" id="kanpro_user_password_enable" class="form-control" <?= ($this->task->configModel->get('kanpro_user_password_enable', '') === '1') ? 'checked' : '' ?>>
        <?= t('Enable User Password API') ?>
      </label>
      <p class="form-help"><?= t('User: change own password (requires current). Admin: reset any password.') ?></p>
    </div>
  </fieldset>

  <!-- User Profile Section -->
  <fieldset>
    <legend><?= t('User Profile') ?></legend>

    <div class="form-group">
      <label for="kanpro_user_profile_enable">
        <input type="checkbox" name="kanpro_user_profile_enable" value="1" id="kanpro_user_profile_enable" class="form-control" <?= ($this->task->configModel->get('kanpro_user_profile_enable', '') === '1') ? 'checked' : '' ?>>
        <?= t('Enable User Profile API') ?>
      </label>
      <p class="form-help"><?= t('Get and update profile: username, name, email, theme, timezone, language, filter.') ?></p>
    </div>
  </fieldset>

  <!-- Project User Section -->
  <fieldset>
    <legend><?= t('Project User') ?></legend>

    <div class="form-group">
      <label for="kanpro_project_user_enable">
        <input type="checkbox" name="kanpro_project_user_enable" value="1" id="kanpro_project_user_enable" class="form-control" <?= ($this->task->configModel->get('kanpro_project_user_enable', '') === '1') ? 'checked' : '' ?>>
        <?= t('Enable Project User API') ?>
      </label>
      <p class="form-help"><?= t('Extended getProjectUsers/getAssignableUsers that return full user objects (id, username, name, email, role, is_active, project_role).') ?></p>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
  </div>
</form>
