<?php

use Kirby\Cms\App as Kirby;

Kirby::plugin('owebstudio/move-pages', [
    'areas' => include __DIR__ . '/config/areas.php',
    'permissions' => include __DIR__ . '/config/permissions.php',
    'translations' => include __DIR__ . '/config/translations.php'
]);
