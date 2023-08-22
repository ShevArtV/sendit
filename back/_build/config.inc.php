<?php
// /usr/local/php/php-7.4/bin/php -d display_errors -d error_reporting=E_ALL art-sites.ru/htdocs/sendit/_build/build.php
if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

return [
    'name' => 'SendIt',
    'name_lower' => 'sendit',
    'version' => '1.0.0',
    'release' => 'beta',
    // Install package to site right after build
    'install' => false,
    // Which elements should be updated on package upgrade
    'update' => [
        'chunks' => true,
        'menus' => false,
        'permission' => false,
        'plugins' => true,
        'policies' => false,
        'policy_templates' => false,
        'resources' => false,
        'settings' => true,
        'snippets' => true,
        'templates' => false,
        'widgets' => false,
    ],
    // Which elements should be static by default
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    // Log settings
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() == 'cli' ? 'ECHO' : 'HTML',
    // Download transport.zip after build
    'download' => !empty($_REQUEST['download']),
];