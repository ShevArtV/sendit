<?php


/**
 *
 */
class SendIt
{
    /**
     * @var modX
     */
    public modX $modx;
    /**
     * @var string|null
     */
    public string $formName;
    /**
     * @var string
     */
    public string $presetName;
    /**
     * @var string
     */
    public string $basePath;
    /**
     * @var string
     */
    public string $assetsPath;
    /**
     * @var string
     */
    public string $jsConfigPath;
    /**
     * @var string
     */
    public string $uploaddir;
    /**
     * @var string
     */
    public string $pathToPresets;
    /**
     * @var string
     */
    public string $corePath;
    /**
     * @var string
     */
    public string $presetKey;
    /**
     * @var array
     */
    public array $presets;
    /**
     * @var array
     */
    public array $preset;
    /**
     * @var array
     */
    public array $extendsPreset;
    /**
     * @var array
     */
    public array $pluginParams;
    /**
     * @var array
     */
    public array $params;
    /**
     * @var array
     */
    public array $validates;
    /**
     * @var array
     */
    public array $defaultValidators;
    /**
     * @var array
     */
    public array $hooks;
    /**
     * @var array
     */
    public array $session;
    /**
     * @var int
     */
    public int $roundPrecision;

    /**
     * @var object
     */
    public object $parser;

    /**
     * @var array
     */
    public array $response;

    /**
     * @var bool
     */
    public bool $forceRemove = false;
    /**
     * @var array
     */
    private array $unsetParamsList;

    /**
     * @var array
     */
    public array $webConfig = [];
    /**
     * @var Sanitizer $sanitizer
     */
    public Sanitizer $sanitizer;
    /**
     * @var array|mixed|string|null $newValue
     */
    public $newValue;


    /**
     * @param modX $modx
     * @param string|null $presetName
     * @param string|null $formName
     */
    public function __construct(modX $modx, ?string $presetName = '', ?string $formName = '')
    {
        $this->modx = $modx;
        $this->formName = $formName ?: $presetName;
        $this->presetName = $presetName ?: '';
        $this->initialize();
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        include_once 'sanitizer.class.php';
        $this->sanitizer = new Sanitizer();
        $version = $this->modx->getVersionData();
        $this->session = SendIt::getSession($this->modx) ?: [];
        $this->basePath = $this->modx->getOption('base_path');
        $this->corePath = $this->modx->getOption('core_path');
        $this->assetsPath = $this->modx->getOption('assets_path');
        $this->jsConfigPath = $this->modx->getOption('si_js_config_path', '', '../configs/modules.inc.js');
        $this->roundPrecision = $this->modx->getOption('si_precision', '', 2);
        $unsetParamsList = $this->modx->getOption('si_unset_params', '', 'emailTo,extends');
        $this->unsetParamsList = explode(',', $unsetParamsList);
        $uploaddir = $this->modx->getOption('si_uploaddir', '', '[[+asseetsUrl]]components/sendit/uploaded_files/');
        $this->uploaddir = str_replace('[[+asseetsUrl]]', $this->assetsPath, $uploaddir);
        $pathToPresets = $this->modx->getOption('si_path_to_presets', '', 'components/sendit/presets/sendit.inc.php');
        $this->presetKey = str_replace('.inc.php', '', basename($pathToPresets));
        $this->pathToPresets = dirname($this->corePath . $pathToPresets);
        (int)$version['version'] === 2 ? $this->setParser() : $this->setParserModx3();
        $this->setPresets();
        $sessionPreset = $this->session['presets'][$this->presetName] ?? [];
        $this->preset = array_merge($sessionPreset, $this->presets[$this->presetKey][$this->presetName] ?? []);
        $this->pluginParams = [];
        $this->getFormParams();
        $this->params = [];
        $this->validates = [];
        $this->defaultValidators = [
            'blank',
            'required',
            'password_confirm',
            'email',
            'minLength',
            'maxLength',
            'minValue',
            'maxValue',
            'contains',
            'strip',
            'stripTags',
            'allowTags',
            'isNumber',
            'allowSpecialChars',
            'isDate',
            'regexp',
            'checkbox'
        ];
        if (empty($this->presets)) {
            $this->modx->log(1, 'Путь к пресетам не задан или задан не корректно!');
        }

        $this->modx->lexicon->load('sendit:default');

        $this->extendsPreset = !empty($this->preset['extends']) ? $this->getExtends($this->preset['extends'], []) : [];

        $this->setParams();

        if (!empty($this->params['useLexicons'])) {
            $this->replaceLexicons();
        }

        $this->prepareValues();

        $this->removeUselessField();

        if (!empty($this->params['fieldNames'])) {
            $this->setFieldsAliases();
        }

        if (!empty($this->params['attachFilesToEmail']) && !empty($this->params['allowFiles'])) {
            $this->attachFiles();
        }

        //$this->modx->log(1, print_r($this->validates, 1));
        //$this->modx->log(1, print_r($_POST, 1));
        //$this->modx->log(1, print_r($this->extendsPreset, 1));
        //$this->modx->log(1, print_r($this->params, 1));
        $this->setValidate();
    }

    /**
     * @return void
     */
    private function replaceLexicons(): void
    {
        $lexicons = explode(',', $this->params['useLexicons']);
        foreach ($lexicons as $lexicon) {
            $this->modx->lexicon->load($lexicon);
        }

        foreach ($this->params as $key => $value) {
            if (strpos($key, 'Message') !== false
                || strpos($key, 'message') !== false
                || strpos($key, 'vText') !== false) {
                $this->params[$key] = $this->modx->lexicon($value);
            }
            if ($key === 'fieldNames') {
                $fieldNames = explode('==', $value);
                foreach ($fieldNames as $k => $v) {
                    $fieldNames[$k] = $this->modx->lexicon($v);
                }
                $this->params[$key] = implode('==', $fieldNames);
            }
        }
    }

    /**
     * @return void
     */
    private function prepareValues(): void
    {
        foreach ($_POST as $k => $v) {
            $this->setValue($v, $k);
            if (is_array($v)) {
                $_POST[$k] = json_encode($v);
            }
        }
        $_POST['fields'] = json_encode($_POST);
    }

    /**
     * @return void
     */
    private function setParser(): void
    {
        $this->parser = $this->modx->getService('pdoTools') ?: $this->modx;
    }

    /**
     * @return void
     */
    private function setParserModx3(): void
    {
        $this->parser = $this->modx->services->has('pdoTools') ? $this->modx->services->get('pdoTools') : $this->modx;
    }

    /**
     * @return void
     */
    private function setPresets(): void
    {
        $this->presets = [];
        if (file_exists($this->pathToPresets)) {
            $files = scandir($this->pathToPresets);
            unset($files[0], $files[1]);
            if (count($files)) {
                foreach ($files as $file) {
                    $presets_file_path = $this->pathToPresets . '/' . $file;
                    $this->presets[str_replace('.inc.php', '', $file)] = include($presets_file_path);
                }
            }
        }
    }

    /**
     * @param string $preset
     * @param array $extends
     * @return array
     */
    private function getExtends(string $preset, array $extends): array
    {
        $preset = explode('.', $preset);
        if (count($preset) < 2) {
            $preset[1] = $preset[0];
            $preset[0] = $this->presetKey;
        }
        $presetData = $this->presets[$preset[0]][$preset[1]];
        if ($presetData && is_array($presetData)) {
            $extends = array_merge($extends, $presetData);
            if (!empty($presetData['extends'])) {
                $extends = $this->getExtends($presetData['extends'], $extends);
            }
        }
        return $extends;
    }

    /**
     * @return void
     */
    private function setParams(): void
    {
        $adminID = $this->modx->getOption('si_default_admin', '', 1);
        $mgrEmail = '';
        $http_host = $this->modx->getOption('http_host', '', 'domain.com');
        $useSMTP = $this->modx->getOption('mail_use_smtp', '', false);
        $emailFrom = $useSMTP ? $this->modx->getOption('emailsender') : "noreply@{$http_host}";
        if ($profile = $this->modx->getObject('modUserProfile', ['internalKey' => $adminID])) {
            $mgrEmail = $profile->get('email');
        }
        $email = $this->modx->getOption('si_default_email') ?: $mgrEmail;
        $email = $email ?: $this->modx->getOption('ms2_email_manager');
        $emailTpl = $this->modx->getOption('si_default_emailtpl', '', 'siDefaultEmail');
        $hooks = $email ? 'FormItSaveForm,email' : 'FormItSaveForm';
        $default = [
            'successMessage' => $this->modx->lexicon('si_msg_success'),
            'hooks' => $hooks,
            'emailTpl' => $emailTpl,
            'emailTo' => $email,
            'emailFrom' => $emailFrom,
            'formName' => $this->modx->lexicon('si_default_formname'),
            'emailSubject' => $this->modx->lexicon('si_default_subject', ['host' => $this->modx->getOption('http_host')]),
        ];

        $this->params = array_merge($this->extendsPreset, $this->preset, $this->pluginParams);
        if (!isset($this->params['snippet']) || $this->params['snippet'] === 'FormIt') {
            $this->params = array_merge($default, $this->params);
        }

        $this->hooks = !empty($this->params['hooks']) ? explode(',', $this->params['hooks']) : [];

        $this->params['sendGoal'] = $this->params['sendGoal'] ?? $this->modx->getOption('si_send_goal', '', false);
        $this->params['counterId'] = $this->params['counterId'] ?? $this->modx->getOption('si_counter_id', '', '');
        $this->params['formName'] = !empty($this->params['formName']) && $this->params['formName'] !== $this->modx->lexicon(
            'si_default_formname'
        ) ? $this->params['formName'] : $this->formName;
        if (!empty($this->params['validate'])) {
            $this->validates = $this->getValidate($this->params['validate']);
        }
    }

    /**
     * @param modX $modx
     * @return void
     */
    public function loadCssJs(): void
    {
        $frontend_js = $this->modx->getOption('si_frontend_js', '', '[[+assetsUrl]]components/sendit/js/web/index.js');
        $frontend_css = $this->modx->getOption('si_frontend_css', '', '[[+assetsUrl]]components/sendit/css/web/index.css');
        $basePath = $this->modx->getOption('base_path');
        $assetsUrl = str_replace($basePath, '', $this->modx->getOption('assets_path'));
        $this->getWebConfig();

        if (!empty($this->webConfig)) {
            $webConfig = json_encode($this->webConfig, JSON_UNESCAPED_UNICODE);
            $this->modx->regClientScript(
                "<script> window.siConfig = $webConfig; </script>",
                1
            );
        }
        if ($frontend_js) {
            $scriptPath = str_replace('[[+assetsUrl]]', $assetsUrl, $frontend_js);
            $this->modx->regClientScript(
                '<script type="module" src="' . $scriptPath . $this->webConfig['version'] . '"></script>',
                true
            );
        }
        if ($frontend_css) {
            $stylePath = str_replace('[[+assetsUrl]]', $assetsUrl, $frontend_css);
            $this->modx->regClientCSS($stylePath . $this->webConfig['version']);
        }
    }

    public function getWebConfig(): void
    {
        $packageVersion = $this->getPackageVersion();
        $scriptsVersion = $packageVersion ? '?v=' . md5($packageVersion) : '';
        //$scriptsVersion = '?v=' . time();

        $this->webConfig = [
            'version' => $scriptsVersion,
            'actionUrl' => '/assets/components/sendit/action.php',
            'modulesConfigPath' => $this->jsConfigPath,
            'cookieName' => 'SendIt',
        ];

        $this->modx->invokeEvent('senditOnGetWebConfig', [
            'webConfig' => $this->webConfig,
            'object' => $this
        ]);
    }

    private function getPackageVersion(): string
    {
        $q = $this->modx->newQuery('transport.modTransportPackage');
        $q->select('signature');
        $q->sortby('installed', 'DESC');
        $q->limit(1);
        $q->prepare();
        if (!$q->stmt->execute()) {
            return '';
        }
        return $q->stmt->fetchColumn();
    }

    /**
     * @return void
     */
    private function attachFiles(): void
    {
        $fileList = $_POST[$this->params['allowFiles']];
        $fieldKey = $this->params['attachFilesToEmail'];
        if ($fileList && $fieldKey) {
            $fileList = explode(',', $fileList);
            $_FILES[$fieldKey]['name'] = [];
            $_FILES[$fieldKey]['type'] = [];
            $_FILES[$fieldKey]['tmp_name'] = [];
            $_FILES[$fieldKey]['error'] = [];
            $_FILES[$fieldKey]['size'] = [];
            foreach ($fileList as $path) {
                $fullpath = $this->uploaddir . session_id() . '/' . $path;
                $_FILES[$fieldKey]['name'][] = basename($path);
                $_FILES[$fieldKey]['type'][] = filetype($fullpath);
                $_FILES[$fieldKey]['tmp_name'][] = $fullpath;
                $_FILES[$fieldKey]['error'][] = 0;
                $_FILES[$fieldKey]['size'][] = filesize($fullpath);
            }
        }
    }

    /**
     * @return void
     */
    private function removeUselessField(): void
    {
        $allValidators = [];
        foreach ($this->validates as $fieldName => &$validators) {
            $allValidators = array_merge($allValidators, $validators);
            if (!isset($_POST[$fieldName]) && !in_array('checkbox', $validators)) {
                unset($this->validates[$fieldName]);
            }
            $k = array_search('checkbox', $validators);
            if ($k !== false) {
                unset($validators[$k]);
                if (!isset($_POST[$fieldName])) {
                    $_POST[$fieldName] = '';
                }
            }
        }
        $allValidators = array_unique($allValidators);
        $this->setCustomValidators($allValidators);
    }

    /**
     * @param array $allValidators
     * @return void
     */
    private function setCustomValidators(array $allValidators): void
    {
        if (!empty($allValidators)) {
            $output = [];
            foreach ($allValidators as $validator) {
                $items = explode('=', $validator);
                if (!in_array($items[0], $this->defaultValidators)) {
                    $output[] = $items[0];
                }
            }

            if (!empty($output)) {
                $this->params['customValidators'] = implode(',', array_unique($output));
            }
        }
    }

    /**
     * @return void
     */
    private function setFieldsAliases(): void
    {
        $fields = explode(',', $this->params['fieldNames']);
        $result = [];
        foreach ($fields as $field) {
            $f = explode('==', trim($field));
            $result[$f[0]] = $f[1];
        }
        $_POST['fieldsAliases'] = $this->modx->toJSON($result);
    }


    /**
     * @param $value
     * @param $key
     * @return void
     */
    private function setValue($value, $key): void
    {
        if ($key === 'fields') {
            return;
        }
        if (!is_array($value)) {
            $this->newValue = $this->sanitizer->process($value);
            $this->modx->invokeEvent('senditOnSetValue', [
                'key' => $key,
                'value' => $value,
                'SendIt' => $this
            ]);

            $_POST[$key] = $this->newValue;
            $k = preg_replace('/\[\d*?\]/', '[*]', $key);
            if (!empty($this->validates[$k]) && !isset($this->validates[$key])) {
                $this->validates[$key] = $this->validates[$k];
            }
        } else {
            $_POST[$key . '[]'] = implode(', ', $value);
            foreach ($value as $k => $v) {
                $this->setValue($v, $key . '[' . $k . ']');
            }
        }
    }

    /**
     * @param string|null $validate
     * @return array
     */
    private function getValidate(?string $validate = ''): array
    {
        $output = [];
        if (!$validate) {
            return $output;
        }
        $validate = str_replace(["\r", "\n", ', '], ['', '', ','], $validate);
        $validates = explode(',', $validate);

        foreach ($validates as $v) {
            $v = trim($v);
            $items = explode(':', $v);
            $key = $items[0];
            unset($items[0]);
            $output[$key] = $items;
        }
        return $output;
    }

    /**
     * @return void
     */
    private function setValidate(): void
    {
        $output = [];
        foreach ($this->validates as $fieldName => $validators) {
            $output[] = $fieldName . ':' . implode(':', $validators);
        }
        $this->params['validate'] = implode(',', $output);
    }

    /**
     * @return void
     */
    private function getFormParams(): void
    {
        $this->modx->invokeEvent('OnGetFormParams', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this
        ]);
    }

    /**
     * @return array|string
     */
    public function process()
    {
        $result = $this->checkPossibilityWork();

        $this->modx->invokeEvent('OnCheckPossibilityWork', [
            'formName' => $this->formName,
            'result' => $result
        ]);

        $response = isset($this->modx->event->returnedValues) && !empty($this->modx->event->returnedValues['result']) ? $this->modx->event->returnedValues['result'] : '';
        if (!empty($response)) {
            $result = $response;
        }

        if ($result['success']) {
            if (in_array('FormItAutoResponder', $this->hooks) || !empty($this->params['antispam'])) {
                $countSending = 0;
                if (isset($this->session['sendingLimits'][$this->formName]['countSending'])) {
                    $countSending = (int)$this->session['sendingLimits'][$this->formName]['countSending'];
                }
                $this->session['sendingLimits'][$this->formName]['countSending'] = ++$countSending;
                $this->session['sendingLimits'][$this->formName]['lastSendingTime'] = time();
                SendIt::setSession($this->modx, ['sendingLimits' => $this->session['sendingLimits']]);
            }

            $snippet = !empty($this->params['snippet']) ? $this->params['snippet'] : 'FormIt';
            if ($snippet !== 'FormIt') {
                if (!empty($this->params['validate'])) {
                    $this->runSnippet('FormIt');
                    $result = $this->handleFormIt();
                    if (!$result['success']) {
                        return $this->error($result['message'], $result['data']);
                    }
                }
                $this->params['SendIt'] = $this;
                return $this->runSnippet($snippet);
            } else {
                $this->runSnippet('FormIt');
                $result = $this->handleFormIt();
                $status = $result['success'] ? 'success' : 'error';
                return $this->$status($result['message'], $result['data']);
            }
        }
        return $result;
    }

    /**
     * @param string $snippet
     * @return mixed
     */
    private function runSnippet(string $snippet)
    {
        return $this->parser->runSnippet($snippet, $this->params);
    }

    /**
     * @return array
     */
    public function paginationHandler(): array
    {
        $snippetName = $this->params['render'] ?? '!pdoResources';
        $pageKey = ($this->params['pagination'] ?? '') . 'page';
        unset($this->params['SendIt']);
        $this->params['limit'] = (int)($_REQUEST['limit'] ?? $this->params['limit']) ?? 10;
        $currentPage = !empty($_REQUEST[$pageKey]) ? (int)$_REQUEST[$pageKey] : 1;
        $hashParams = [];
        $this->modx->invokeEvent('OnBeforePageRender', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this
        ]);

        $this->params['hashParams'] = !empty($this->params['hashParams']) ? explode(',', $this->params['hashParams']) : [];
        $this->params['hashParams'] = array_unique(array_merge(['pagination', 'limit', 'presetName'], $this->params['hashParams']));
        foreach ($this->params as $key => $value) {
            if (in_array($key, $this->params['hashParams'])) {
                $hashParams[$key] = $value;
            }
        }

        $resultShowMethod = $_REQUEST['resultShowMethod'] ?? $this->params['resultShowMethod'] ?? 'insert';
        $oldHash = $this->session['hash'][$this->presetName] ?? '';
        $newHash = md5(json_encode($hashParams));
        if ($oldHash !== $newHash) {
            $this->session['hash'][$this->presetName] = $newHash;
            SendIt::setSession($this->modx, [
                'hash' => $this->session['hash']
            ]);
            $currentPage = !$oldHash ? $currentPage : 1;
            $resultShowMethod = 'insert';
        }

        $this->params['offset'] = $this->params['offset'] ?? $this->params['limit'] * ($currentPage - 1);
        $html = $this->runSnippet($snippetName);
        $total = $this->modx->getPlaceholder($this->params['totalVar'] ?? 'total');
        $totalPages = ceil($total / $this->params['limit']);
        if ($totalPages && $currentPage > $totalPages) {
            $this->params['offset'] = $this->params['offset'] ?? ($totalPages - 1) * $this->params['limit'];
            $currentPage = $totalPages;
            $html = $this->runSnippet($snippetName);
        }

        if (!$html && $this->params['tplEmpty']) {
            $html = $this->parser->getChunk($this->params['tplEmpty'], $this->params);
        }

        if ((int)$totalPages === 1) {
            $resultShowMethod = 'insert';
        }

        return $this->success('', [
            'html' => $html,
            'totalPages' => $totalPages ?: 1,
            'total' => $total ?: 0,
            'limit' => $this->params['limit'],
            'pagination' => $this->params['pagination'],
            'currentPage' => $currentPage,
            'resultShowMethod' => $resultShowMethod,
            'pageList' => $this->getPageList($currentPage, $totalPages)
        ]);
    }

    /**
     * @param int $currentPage
     * @param int $totalPages
     * @return string
     */
    private function getPageList(int $currentPage, int $totalPages): string
    {
        $maxPageListItems = $this->params['maxPageListItems'] ?? 0;
        if (!$maxPageListItems) {
            return '';
        }

        if ($maxPageListItems > $totalPages) {
            $maxPageListItems = $totalPages;
        }

        $firstValue = $currentPage > 1 ? $currentPage - 1 : $currentPage;
        $lastValue = $firstValue + ($maxPageListItems - 1);
        if ($lastValue > $totalPages) {
            $firstValue -= $lastValue - $totalPages;
        }
        $pageKey = ($this->params['pagination'] ?? '') . 'page';
        $tplPageListItem = $this->params['tplPageListItem'] ?? 'siPageListItem';
        $tplPageListWrapper = $this->params['tplPageListWrapper'] ?? 'siPageListWrapper';
        $items = '';
        for ($i = 0; $i < $maxPageListItems; $i++) {
            $items .= $this->parser->getChunk($tplPageListItem, [
                'page' => $firstValue++,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'pageKey' => $pageKey
            ]);
        }
        if ($items) {
            return $this->parser->getChunk($tplPageListWrapper, [
                'items' => $items,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'pageKey' => $pageKey
            ]);
        }
        return '';
    }


    /**
     * @return array
     */
    private function checkPossibilityWork(): array
    {
        if (!in_array('email', $this->hooks)
            && !in_array('FormItAutoResponder', $this->hooks)
            && empty($this->params['pauseBetweenSending'])
            && empty($this->params['sendingPerSession'])
        ) {
            return $this->success();
        }

        $pause = $this->params['pauseBetweenSending'] ?? $this->modx->getOption('si_pause_between_sending', '', 30);
        $maxSendingCount = $this->params['sendingPerSession'] ?? $this->modx->getOption('si_max_sending_per_session', '', 2);
        $now = time();
        $countSending = 0;
        $lastSendingTime = $now - $pause;
        if (isset($this->session['sendingLimits'][$this->formName])) {
            $countSending = (int)$this->session['sendingLimits'][$this->formName]['countSending'] ?? 0;
            $lastSendingTime = (int)$this->session['sendingLimits'][$this->formName]['lastSendingTime'] ?? $now - $pause;
        }

        $timePassed = $now - $lastSendingTime;
        if ($timePassed >= $pause && $countSending < $maxSendingCount) {
            return $this->success();
        }
        if ($countSending >= $maxSendingCount) {
            return $this->error('si_msg_count_sending_err', [], ['count' => $maxSendingCount]);
        }
        if ($timePassed < $pause) {
            return $this->error('si_msg_pause_err', [], ['left' => $pause - $timePassed]);
        }

        return $this->success();
    }

    /**
     * @return array
     */
    private function handleFormIt(): array
    {
        $plPrefix = $this->params['placeholderPrefix'] ?? 'fi.';
        $plPrefix = $plPrefix . 'error.';
        $data = [];
        foreach ($this->modx->placeholders as $pls => $v) {
            if (strpos($pls, $plPrefix) === false) {
                continue;
            }
            $v = strip_tags(trim($v));
            preg_match('/[^\s]/', $v, $matches);
            if (empty($matches)) {
                continue;
            }
            if ($k = str_replace($plPrefix, '', $pls)) {
                $data['errors'][$k] = $v;
            }
        }

        $this->params['aliases'] = [];
        if (!empty($this->params['fieldNames'])) {
            $fields = explode(',', $this->params['fieldNames']);
            foreach ($fields as $field) {
                $items = explode('==', $field);
                $this->params['aliases'][$items[0]] = $items[1];
            }
        }

        if (!empty($data['errors'])) {
            $message = $this->params['validationErrorMessage'] ?? '';
            $success = false;
        } else {
            $message = $this->params['successMessage'];
            $success = true;

            $data['redirectTimeout'] = $this->params['redirectTimeout'] ?? 2000;
            $data['redirectUrl'] = $this->params['redirectTo'] ?? '';
            if (!empty($this->params['redirectTo']) && (int)$this->params['redirectTo']) {
                $redirectUrl = $this->modx->makeUrl($this->params['redirectTo'], '', '', 'full');
                $data['redirectUrl'] = $redirectUrl;
            }
        }

        return ['success' => $success, 'message' => $message, 'data' => $data];
    }


    /**
     * @param array $filesData
     * @param int|null $totalCount
     * @return array
     */
    public function validateFiles(array $filesData, ?int $totalCount = 0): array
    {
        $this->modx->invokeEvent('OnBeforeFileValidate', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this,
            'filesData' => $filesData,
            'totalCount' => $totalCount
        ]);

        $totalCount = $this->modx->event->returnedValues['totalCount'] ?? $totalCount;

        $uploaddir = $this->uploaddir . session_id() . '/';
        $allowExt = !empty($this->params['allowExt']) ? explode(',', $this->params['allowExt']) : [];
        $maxSize = !empty($this->params['maxSize']) ? (float)$this->params['maxSize'] * 1024 * 1024 : 1024 * 1024;
        $maxCount = !empty($this->params['maxCount']) ? (int)$this->params['maxCount'] : 1;
        $status = 'success';
        $data['fileNames'] = [];
        $data['errors'] = [];
        $data['portion'] = !empty($this->params['portion']) ? $this->params['portion'] : 0.1;
        $data['threadsQuantity'] = !empty($this->params['threadsQuantity']) ? $this->params['threadsQuantity'] : 1;

        if ($maxCount < ($totalCount + count($filesData))) {
            $left = $maxCount - $totalCount;
            $declension = $this->getDeclension($left, 'файл', 'файла', 'файлов');
            if ($totalCount === 0) {
                $data['errors']['size'] = $this->modx->lexicon('si_msg_files_maxcount_err', ['left' => $left, 'declension' => $declension]);
            } elseif ($left === 0) {
                $data['errors']['size'] = $this->modx->lexicon('si_msg_files_loaded_err');
            } else {
                $data['errors']['size'] = $this->modx->lexicon('si_msg_files_count_err', ['left' => $left, 'declension' => $declension]);
            }
            return $this->error('', $data);
        }
        foreach ($filesData as $filename => $filesize) {
            $data['aliases'][$filename] = $filename;
            list($nameWithoutExt, $fileExt) = $this->getFileParts($filename);
            $dir = $uploaddir . $nameWithoutExt . '/';
            if ($status === 'error') {
                $data['fileNames'][] = $filename;
            }

            if (file_exists($uploaddir . $filename)) {
                $data['loaded'][$filename] = str_replace($this->basePath, '', $this->uploaddir) . session_id() . '/' . $filename;
                $status = 'success';
            }
            $uploadedSize = $this->session['uploadedSize'][$filename] ?? 0;
            if (file_exists($dir) && $uploadedSize) {
                $percent = $this->getPercent($uploadedSize, $filesize);
                if ($percent < 100 && $percent > 0) {
                    $chunks = scandir($dir);
                    unset($chunks[0], $chunks[1]);
                    $msg = $this->getLoadingMsg($percent, $uploadedSize, $filesize, $filename);
                    $data['start'][$filename] = [
                        'percent' => "{$percent}%",
                        'bytes' => $uploadedSize,
                        'chunks' => implode(',', $chunks),
                        'msg' => $msg
                    ];
                }
            }
            if ($maxSize <= $filesize) {
                $data['errors'][$filename] .= $this->modx->lexicon('si_msg_file_size_err');
                $data['fileNames'][] = $filename;
                $status = 'error';
            }
            if (!in_array($fileExt, $allowExt)) {
                $data['errors'][$filename] .= $this->modx->lexicon('si_msg_file_extention_err');
                $data['fileNames'][] = $filename;
                $status = 'error';
            }
        }
        $data['fileNames'] = array_unique($data['fileNames']);
        $data['queueMsg'] = $this->modx->lexicon('si_msg_queue');
        return $this->$status('', $data);
    }


    /**
     * @param int $number
     * @param string $form1
     * @param string $form2
     * @param string $form3
     * @return string
     */
    private function getDeclension(int $number, string $form1, string $form2, string $form3): string
    {
        $number = abs($number) % 100;
        $mod = $number % 10;
        if ($number > 10 && $number < 20) {
            return $form3;
        } elseif ($mod > 1 && $mod < 5) {
            return $form2;
        } elseif ($mod == 1) {
            return $form1;
        } else {
            return $form3;
        }
    }

    /**
     * @param string $content
     * @param array $headers
     * @return array
     */
    public function uploadChunk(string $content, array $headers): array
    {
        $uploaddir = $this->uploaddir . session_id() . '/';
        $filename = $uploaddir . $headers['x-content-name'];
        if (!is_dir($uploaddir)) {
            mkdir($uploaddir, 0777, true);
        }

        if (file_exists($uploaddir . $filename)) {
            return $this->success($this->modx->lexicon('si_msg_loading', [
                'filename' => $headers['x-content-name'],
                'percent' => 100
            ]), [
                'path' => str_replace($this->basePath, '', $this->uploaddir) . session_id() . '/' . $headers['x-content-name'],
                'percent' => "100%",
                'filename' => $headers['x-content-name'],
                'chunkId' => $headers['x-chunk-id'],
            ]);
        }

        list($nameWithoutExt, $fileExt) = $this->getFileParts($headers['x-content-name']);

        $dir = $uploaddir . $nameWithoutExt . '/';
        $chunkName = $headers['x-chunk-id'] . '.' . $fileExt;
        $chunkPath = $dir . $chunkName;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($chunkPath) || filesize($chunkPath) < $headers['content-length']) {
            file_put_contents($chunkPath, $content);
        }

        $portion = $this->params['portion'] * 1024 * 1024;
        $countChunks = count(scandir($dir)) - 2;
        $uploadedSize = $countChunks * $portion;
        if ($uploadedSize > $headers['x-total-length']) {
            $uploadedSize = $headers['x-total-length'];
        }
        $percent = $this->getPercent($uploadedSize, $headers['x-total-length']);
        $msg = $this->getLoadingMsg($percent, $uploadedSize, $headers['x-total-length'], $headers['x-content-name']);
        if ($uploadedSize < $headers['x-total-length']) {
            return $this->success($msg, [
                'percent' => "$percent%",
                'bytes' => $this->session['uploadedSize'][$headers['x-content-name']] ?? 0,
                'filename' => $headers['x-content-name'],
                'chunkId' => $headers['x-chunk-id'],
            ]);
        }

        $i = 0;
        while (file_exists($dir . $i . '.' . $fileExt)) {
            $name = $dir . $i . '.' . $fileExt;
            if (!file_exists($filename)) {
                $fout = fopen($filename, "wb");
            } else {
                $fout = fopen($filename, "ab");
            }
            $fin = fopen($name, "rb");
            if ($fin) {
                while (!feof($fin)) {
                    $data = fread($fin, 1024 * 1024);
                    fwrite($fout, $data);
                }
                fclose($fin);
            }
            fclose($fout);
            unlink($name);
            $i++;
        }

        SendIt::removeDir($dir, $this->modx);

        return $this->success($msg, [
            'path' => str_replace($this->basePath, '', $this->uploaddir) . session_id() . '/' . $headers['x-content-name'],
            'percent' => "$percent%",
            'filename' => $headers['x-content-name'],
            'chunkId' => $headers['x-chunk-id'],
        ]);
    }

    /**
     * @param string $filename
     * @return array
     */
    private function getFileParts(string $filename): array
    {
        $nameParts = explode('.', $filename);
        $lastIndex = count($nameParts) - 1;
        $fileExt = $nameParts[$lastIndex];
        unset($nameParts[$lastIndex]);
        $nameWithoutExt = implode('.', $nameParts);
        return [$nameWithoutExt, $fileExt];
    }

    /**
     * @param int $percent
     * @param int $uploadedSize
     * @param int $totalSize
     * @param string $filename
     * @return string
     */
    protected function getLoadingMsg(int $percent, int $uploadedSize, int $totalSize, string $filename): string
    {
        $unit = $this->params['loadedUnit'] ?? '%';
        $key = 'si_msg_loading_bytes';
        $data = [
            'filename' => $filename,
            'unit' => $unit
        ];
        switch (strtolower($unit)) {
            case 'b':
                $data['bytes'] = $uploadedSize;
                $data['total'] = $totalSize;
                break;
            case 'kb':
                $data['bytes'] = round($uploadedSize / 1024);
                $data['total'] = round($totalSize / 1024);
                break;
            case 'mb':
                $data['bytes'] = round($uploadedSize / (1024 * 1024), 1);
                $data['total'] = round($totalSize / (1024 * 1024), 1);
                break;
            case 'gb':
                $data['bytes'] = round($uploadedSize / (1024 * 1024 * 1024), 2);
                $data['total'] = round($totalSize / (1024 * 1024 * 1024), 2);
                break;
            default:
                $key = 'si_msg_loading';
                $data['percent'] = $percent;
                break;
        }
        return $this->modx->lexicon($key, $data);
    }

    /**
     * @param int $uploadedSize
     * @param int $totalSize
     * @return int
     */
    protected function getPercent(int $uploadedSize, int $totalSize): int
    {
        $percent = round($uploadedSize * 100 / $totalSize, $this->roundPrecision);
        if ($percent > 99) {
            $percent = 100;
        }
        return $percent;
    }

    /**
     * @param string $dir
     * @return void
     */
    public static function removeDir(string $dir, \Modx $modx): void
    {
        $allowDirs = $modx->getOption('si_allow_dirs', '', 'uploaded_files');
        $allowDirs = explode(',', $allowDirs);
        $dirParts = explode('/', $dir);
        if (empty(array_intersect($allowDirs, $dirParts))) {
            return;
        }
        if (substr($dir, -1) !== '/') {
            $dir .= '/';
        }

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . $object) && !is_link($dir . $object)) {
                        SendIt::removeDir($dir . $object);
                    } else {
                        if (file_exists($dir . $object)) {
                            unlink($dir . $object);
                        }
                    }
                }
            }
            if (file_exists($dir) && is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    /**
     * @param string $path
     * @param bool|null $nomsg
     * @return array
     */
    public function removeFile(string $path, ?bool $nomsg = false): array
    {
        $filename = basename($path);
        $dir = str_replace($filename, '', $path);

        $this->modx->invokeEvent('OnBeforeFileRemove', [
            'path' => $path,
            'SendIt' => $this
        ]);

        if (strpos($path, session_id()) === false && !$this->forceRemove) {
            return $this->error('si_msg_file_remove_session_err', [], ['filename' => $filename]);
        } else {
            unset($this->session['uploadedSize'][$filename]);
            SendIt::setSession($this->modx, ['uploadedSize' => $this->session['uploadedSize'] ?? []]);

            if (file_exists($path)) {
                unlink($path);
            } else {
                if (file_exists($dir)) {
                    $this->removeDir($dir, $this->modx);
                }
            }

            $msg = 'si_msg_file_remove_success';
            if ($nomsg) {
                $msg = '';
            }

            return $this->success($msg, [
                'filename' => $filename,
                'path' => str_replace($this->basePath, '', $path),
                'nomsg' => $nomsg
            ]);
        }
    }

    /**
     *
     * @param string|null $message
     * @param array|null $data
     * @param array|null $placeholders
     *
     * @return array
     */
    public function error(?string $message = '', ?array $data = [], ?array $placeholders = []): array
    {
        return $this->getResponse(false, $message ?? '', $data ?? [], $placeholders ?? []);
    }

    /**
     *
     * @param string|null $message
     * @param array|null $data
     * @param array|null $placeholders
     *
     * @return array
     */
    public function success(?string $message = '', ?array $data = [], ?array $placeholders = []): array
    {
        return $this->getResponse(true, $message ?? '', $data ?? [], $placeholders ?? []);
    }

    /**
     *
     * @param bool $status
     * @param string $message
     * @param array $data
     * @param array $placeholders
     *
     * @return array
     */
    private function getResponse(bool $status, string $message = '', array $data = [], array $placeholders = []): array
    {
        $this->response = array_merge($this->params, $data);

        $this->modx->invokeEvent('OnBeforeReturnResponse', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this
        ]);

        $this->response = $this->unsetParams($this->response);

        unset($this->response['SendIt']);

        return [
            'success' => $status,
            'message' => $this->modx->lexicon($message, $placeholders),
            'data' => $this->response,
        ];
    }

    /**
     * @param array $paramList
     * @return array
     */
    private function unsetParams(array $paramList)
    {
        if (!empty($this->unsetParamsList)) {
            foreach ($this->unsetParamsList as $param) {
                unset($paramList[$param]);
            }
        }
        return $paramList;
    }

    /**
     * @param modX $modx
     * @param array|null $values
     * @param string|null $sessionId
     * @param string|null $className
     * @return void
     */
    public static function setSession(\modX $modx, ?array $values = [], ?string $sessionId = '', ?string $className = 'SendIt'): void
    {
        if (!isset($modx->packages['sendit'])) {
            $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
            $modx->addPackage('sendit', $corePath . 'components/sendit/model/');
        }
        $sessionId = $sessionId ?: session_id();
        if (!$session = $modx->getObject('siSession', ['session_id' => $sessionId, 'class_name' => $className])) {
            $session = $modx->newObject('siSession');
        }
        if (!$session) {
            $modx->log(1, print_r('Table si_sessions not found', 1));
            return;
        }
        $item = $session->get('data') ? json_decode($session->get('data'), true) : [];
        $item = array_merge($item, $values);
        $session->fromArray([
            'session_id' => $sessionId,
            'data' => $item ? json_encode($item) : '',
            'class_name' => $className,
            'createdon' => time(),
        ]);
        $session->save();
    }

    /**
     * @param modX $modx
     * @param string|null $sessionId
     * @param string|null $className
     * @return array
     */
    public static function getSession(\modX $modx, ?string $sessionId = '', ?string $className = 'SendIt'): array
    {
        if (!isset($modx->packages['sendit'])) {
            $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
            $modx->addPackage('sendit', $corePath . 'components/sendit/model/');
        }
        $sessionId = $sessionId ?: session_id();
        if (!$session = $modx->getObject('siSession', ['session_id' => $sessionId, 'class_name' => $className])) {
            return [];
        }
        return $session->get('data') ? json_decode($session->get('data'), true) : [];
    }

    /**
     * @param modX $modx
     * @param string|null $className
     * @return void
     */
    public static function clearSession(\modX $modx, ?string $className = 'SendIt'): void
    {
        if (empty($modx->packages['sendit'])) {
            $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
            $modx->addPackage('sendit', $corePath . 'components/sendit/model/');
        }
        $assetsPath = $modx->getOption('core_path', null, MODX_ASSETS_PATH);
        $uploaddir = $modx->getOption('si_uploaddir', '', '[[+asseetsUrl]]components/sendit/uploaded_files/');
        $uploaddir = str_replace('[[+asseetsUrl]]', $assetsPath, $uploaddir);
        $storageTime = $modx->getOption('si_storage_time', '', 86400);
        $max = date('Y-m-d H:i:s', time() - $storageTime);
        $sessions = $modx->getIterator('siSession', ['class_name' => $className, 'createdon:<' => $max]);
        foreach ($sessions as $session) {
            if ($className === 'SendIt') {
                SendIt::removeDir($uploaddir . $session->get('session_id'), $modx);
            }
            $session->remove();
        }
    }
}
