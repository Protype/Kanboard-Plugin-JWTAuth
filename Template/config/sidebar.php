<!-- Template: config/sidebar.php -->
<li <?= $this->app->checkMenuSelection('ConfigController', 'show', 'KanproBridge') ?>>
    <?= $this->url->link(t('KanproBridge'), 'ConfigController', 'show', ['plugin' => 'KanproBridge']) ?>
</li>
