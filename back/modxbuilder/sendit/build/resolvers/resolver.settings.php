<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $packageName = 'sendit';
    $modelPath = $modx->getOption($packageName.'.core_path',null,$modx->getOption('core_path').'components/'.$packageName.'/').'model/';
    $modx->addPackage($packageName,$modelPath);
    $manager = $modx->getManager();

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_UPGRADE:
            if ($setting = $modx->getObject('modSystemSetting', ['key' => 'si_frontend_js'])) {
                if($setting->value === '[[+assetsUrl]]components/sendit/js/web/sendit.js'){
                    $setting->set('value', '[[+assetsUrl]]components/sendit/js/web/index.js');
                    $setting->save();
                }
            }
            if ($setting = $modx->getObject('modSystemSetting', ['key' => 'si_js_config_path'])) {
                if($setting->value === './sendit.inc.js'){
                    $setting->set('value', '../configs/modules.inc.js');
                    $setting->save();
                }
            }
            break;
    }
}

return true;
