<!-- Template: config/sidebar.php -->
<li <?= $this->app->checkMenuSelection('ConfigController', 'show', 'JWTAuth') ?>>
    <?= $this->url->link(t('JWT settings'), 'ConfigController', 'show', ['plugin' => 'JWTAuth']) ?>
</li>
