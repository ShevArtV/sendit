<?php

return [
    'si_frontend_css' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]components/sendit/web/css/index.min.css',
        'area' => 'default',
    ],
    'si_frontend_js' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]components/sendit/web/js/sendit.js',
        'area' => 'default',
    ],
    'si_js_config_path' => [
        'xtype' => 'textfield',
        'value' => './sendit.inc.js',
        'area' => 'default',
    ],
    'si_uploaddir' => [
        'xtype' => 'textfield',
        'value' => '/assets/components/sendit/uploaded_files/',
        'area' => 'default',
    ],
    'si_path_to_presets' => [
        'xtype' => 'textfield',
        'value' => '/core/components/sendit/presets/sendit.inc.php',
        'area' => 'default',
    ],
    'si_send_goal' => [
        'xtype' => 'combo-boolean',
        'value' => '',
        'area' => 'default',
    ],
    'si_counter_id' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'default',
    ],
    'si_default_email' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'default',
    ],
    'si_default_emailtpl' => [
        'xtype' => 'textfield',
        'value' => 'defaultEmail',
        'area' => 'default',
    ],
];