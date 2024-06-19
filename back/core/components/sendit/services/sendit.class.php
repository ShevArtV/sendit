<?php


class SendIt
{
    public modX $modx;
    public object $parser;
    public string $formName;
    public string $presetName;
    public string $basePath;
    public string $assetsPath;
    public string $jsConfigPath;
    public string $uploaddir;
    public string $pathToPresets;
    public string $corePath;
    public string $presetKey;
    public array $presets;
    public array $preset;
    public array $extendsPreset;
    public array $pluginParams;
    public array $params;
    public array $validates;
    public array $defaltValidators;
    public array $hooks;
    public array $session;
    public int $roundPrecision;


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
        $this->session = SendIt::getSession($this->modx) ?: [];
        $this->parser = $this->modx->getService('pdoTools') ?: $this->modx;
        $this->basePath = $this->modx->getOption('base_path');
        $this->corePath = $this->modx->getOption('core_path');
        $this->assetsPath = $this->modx->getOption('assets_path');
        $this->jsConfigPath = $this->modx->getOption('si_js_config_path', '', './sendit.inc.js');
        $this->roundPrecision = $this->modx->getOption('si_precision', '', 2);
        $uploaddir = $this->modx->getOption('si_uploaddir', '', '[[+asseetsUrl]]components/sendit/uploaded_files/');
        $this->uploaddir = str_replace('[[+asseetsUrl]]', $this->assetsPath, $uploaddir);
        $pathToPresets = $this->modx->getOption('si_path_to_presets', '', 'components/sendit/presets/sendit.inc.php');
        $this->presetKey = str_replace('.inc.php', '', basename($pathToPresets));
        $this->pathToPresets = dirname($this->corePath . $pathToPresets);
        $this->setPresets();
        $sessionPreset = $this->session['presets'][$this->presetName] ?: [];
        $this->preset = array_merge($sessionPreset, $this->presets[$this->presetKey][$this->presetName] ?: []);
        $this->pluginParams = [];
        $this->getFormParams();
        $this->params = [];
        $this->validates = [];
        $this->defaltValidators = [
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

        $this->extendsPreset = $this->getExtends($this->preset['extends'], []);

        $this->setParams();

        $this->params['sendGoal'] = $this->params['sendGoal'] ?: $this->modx->getOption('si_send_goal', '', false);
        $this->params['counterId'] = $this->params['counterId'] ?: $this->modx->getOption('si_counter_id', '', '');
        $this->params['formName'] = $this->params['formName'] !== $this->modx->lexicon('si_default_formname') ? $this->params['formName'] : $this->formName;
        $this->validates = $this->getValidate($this->params['validate']);
        foreach ($_POST as $k => $v) {
            $this->setValue($v, $k);
            if (is_array($v)) {
                $_POST[$k] = json_encode($v);
            }
        }
        $_POST['fields'] = json_encode($_POST);

        $this->removeUselessField();

        if ($this->params['fieldNames']) {
            $this->setFieldsAliases();
        }

        if ($this->params['attachFilesToEmail'] && $this->params['allowFiles']) {
            $this->attachFiles();
        }

        //$this->modx->log(1, print_r($this->validates, 1));
        //$this->modx->log(1, print_r($_POST, 1));
        //$this->modx->log(1, print_r($this->extendsPreset, 1));
        //$this->modx->log(1, print_r($this->params, 1));
        $this->setValidate();
    }

    private function setPresets()
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

    private function getExtends($preset, $extends)
    {
        $preset = explode('.', $preset);
        if (count($preset) < 2) {
            $preset[1] = $preset[0];
            $preset[0] = $this->presetKey;
        }
        $presetData = $this->presets[$preset[0]][$preset[1]];
        if ($presetData && is_array($presetData)) {
            $extends = array_merge($extends, $presetData);
            if ($presetData['extends']) {
                $extends = $this->getExtends($presetData['extends'], $extends);
            }
        }
        return $extends;
    }

    private function setParams()
    {
        $adminID = $this->modx->getOption('si_default_admin', '', 1);
        $http_host = $this->modx->getOption('http_host', '', 'domain.com');
        $useSMTP = $this->modx->getOption('mail_use_smtp', '', false);
        $emailFrom = $useSMTP ? $this->modx->getOption('emailsender') : "noreply@{$http_host}";
        $profile = $this->modx->getObject('modUserProfile', ['internalKey' => $adminID]);
        $email = $this->modx->getOption('si_default_email') ?: $profile->get('email');
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

        $this->hooks = $this->params['hooks'] ? explode(',', $this->params['hooks']) : [];
    }

    public static function loadCssJs($modx)
    {
        $frontend_js = $modx->getOption('si_frontend_js', '', '[[+assetsUrl]]components/sendit/js/web/sendit.js');
        $frontend_css = $modx->getOption('si_frontend_css', '', '[[+assetsUrl]]components/sendit/css/web/index.css');
        $basePath = $modx->getOption('base_path');
        $assetsUrl = str_replace($basePath, '', $modx->getOption('assets_path'));

        if ($frontend_js) {
            $scriptPath = str_replace('[[+assetsUrl]]', $assetsUrl, $frontend_js);
            $modx->regClientScript(
                '<script type="module" src="' . $scriptPath . '"></script>',
                true
            );
        }
        if ($frontend_css) {
            $stylePath = str_replace('[[+assetsUrl]]', $assetsUrl, $frontend_css);
            $modx->regClientCSS($stylePath);
        }
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
                if (!in_array($items[0], $this->defaltValidators)) {
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
            $_POST[$key] = $value;
            $k = preg_replace('/\[\d*?\]/', '[*]', $key);
            if ($this->validates[$k] && !$this->validates[$key]) {
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
     * @param string $validate
     * @return array
     */
    private function getValidate($validate = ''): array
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
     * @return array
     */
    private function getFormParams(): array
    {
        $this->modx->invokeEvent('OnGetFormParams', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this
        ]);

        return is_array($this->modx->event->returnedValues) ? $this->modx->event->returnedValues : [];
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

        $response = $this->modx->event->returnedValues['result'];
        if (!empty($response)) {
            $result = $response;
        }

        if ($result['success']) {
            if (in_array('FormItAutoResponder', $this->hooks) || in_array('FormItAutoResponder', $this->hooks) || $this->params['antispam']) {
                $this->session['sendingLimits'][$this->formName]['countSending'] = (int)$this->session['sendingLimits'][$this->formName]['countSending'] + 1;
                $this->session['sendingLimits'][$this->formName]['lastSendingTime'] = time();
                SendIt::setSession($this->modx, ['sendingLimits' => $this->session['sendingLimits']]);
            }

            $snippet = $this->params['snippet'] ?: 'FormIt';
            if ($snippet !== 'FormIt') {
                if ($this->params['validate']) {
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

    public function paginationHandler()
    {
        $snippetName = $this->params['render'] ?? '!pdoResources';
        $pageKey = $this->params['pagination'] . 'page';
        unset($this->params['SendIt']);
        $this->params['limit'] = (int)($_REQUEST['limit'] ?: $this->params['limit']) ?: 10;
        $currentPage = (int)$_REQUEST[$pageKey] ?: 1;
        $hashParams = [];
        $this->modx->invokeEvent('OnBeforePageRender', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this
        ]);

        $this->params['hashParams'] = $this->params['hashParams'] ? explode(',', $this->params['hashParams'] ): [];
        $this->params['hashParams'] = array_unique(array_merge(['pagination','limit','presetName'], $this->params['hashParams']));
        foreach($this->params as $key => $value){
            if(in_array($key, $this->params['hashParams'])){
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

        return $this->success('', [
            'html' => $html,
            'totalPages' => $totalPages ?: 1,
            'total' => $total ?: 0,
            'limit' => $this->params['limit'],
            'pagination' => $this->params['pagination'],
            'currentPage' => $currentPage,
            'resultShowMethod' => $resultShowMethod
        ]);
    }


    private function checkPossibilityWork()
    {
        if (!in_array('email', $this->hooks)
            && !in_array('FormItAutoResponder', $this->hooks)
            && !$this->params['pauseBetweenSending']
            && !$this->params['sendingPerSession']
        ) {
            return $this->success();
        }

        $pause = $this->params['pauseBetweenSending'] ?? $this->modx->getOption('si_pause_between_sending', '', 30);
        $maxSendingCount = $this->params['sendingPerSession'] ?? $this->modx->getOption('si_max_sending_per_session', '', 2);
        $now = time();
        $countSending = (int)$this->session['sendingLimits'][$this->formName]['countSending'];
        $lastSendingTime = (int)$this->session['sendingLimits'][$this->formName]['lastSendingTime'] ?: $now - $pause;
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
    }

    /**
     * @return array
     */
    private function handleFormIt(): array
    {
        $plPrefix = $this->params['placeholderPrefix'] ?: 'fi.';
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
        if ($this->params['fieldNames']) {
            $fields = explode(',', $this->params['fieldNames']);
            foreach ($fields as $field) {
                $items = explode('==', $field);
                $this->params['aliases'][$items[0]] = $items[1];
            }
        }

        if (!empty($data['errors'])) {
            $message = $this->params['validationErrorMessage'];
            $success = false;
        } else {
            $message = $this->params['successMessage'];
            $success = true;

            $data['redirectTimeout'] = $this->params['redirectTimeout'] ?: 2000;
            $data['redirectUrl'] = $this->params['redirectTo'];
            if ((int)$this->params['redirectTo']) {
                $redirectUrl = $this->modx->makeUrl($this->params['redirectTo'], '', '', 'full');
                $data['redirectUrl'] = $redirectUrl;
            }
        }

        return ['success' => $success, 'message' => $message, 'data' => $data];
    }


    /**
     * @param array $filesData
     * @param int $totalCount
     * @return array
     */
    public function validateFiles(array $filesData, int $totalCount = 0): array
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
        $allowExt = explode(',', $this->params['allowExt']) ?: [];
        $maxSize = (float)$this->params['maxSize'] * 1024 * 1024;
        $maxCount = (int)$this->params['maxCount'];
        $status = 'success';
        $data['fileNames'] = [];
        $data['errors'] = [];
        $data['portion'] = $this->params['portion'];
        $data['threadsQuantity'] = $this->params['threadsQuantity'];

        if ($maxCount < ($totalCount + count($filesData))) {
            $left = $maxCount - $totalCount;
            $declension = $this->getDeclension($left, 'файл', 'файла', 'файлов');
            if ($totalCount === 0) {
                $data['errors'][] = $this->modx->lexicon('si_msg_files_maxcount_err', ['left' => $left, 'declension' => $declension]);
            } elseif ($left === 0) {
                $data['errors'][] = $this->modx->lexicon('si_msg_files_loaded_err');
            } else {
                $data['errors'][] = $this->modx->lexicon('si_msg_files_count_err', ['left' => $left, 'declension' => $declension]);
            }
            $status = 'error';
        }
        foreach ($filesData as $filename => $filesize) {
            $data['aliases'][$filename] = $filename;
            $nameParts = explode('.', $filename);
            $dir = $uploaddir . $nameParts[0] . '/';
            if ($status === 'error') {
                $data['fileNames'][] = $filename;
            }

            if (file_exists($uploaddir . $filename)) {
                $data['loaded'][$filename] = str_replace($this->basePath, '', $this->uploaddir) . session_id() . '/' . $filename;
                $status = 'success';
            }
            if (file_exists($dir) && $this->session['uploadedSize'][$filename]) {
                $percent = $this->getPercent($this->session['uploadedSize'][$filename], $filesize);
                if ($percent < 100 && $percent > 0) {
                    $chunks = scandir($dir);
                    unset($chunks[0], $chunks[1]);
                    $msg = $this->getLoadingMsg($percent, $this->session['uploadedSize'][$filename], $filesize, $filename);
                    $data['start'][$filename] = [
                        'percent' => "{$percent}%",
                        'bytes' => $this->session['uploadedSize'][$filename],
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
            if (!in_array($nameParts[1], $allowExt)) {
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
    public function uploadChunk(string $content, array $headers)
    {
        $uploaddir = $this->uploaddir . session_id() . '/';
        $filename = $uploaddir . $headers['x-content-name'];
        if (!is_dir($uploaddir)) {
            mkdir($uploaddir);
        }

        if (file_exists($uploaddir . $filename)) {
            unset($this->session['uploadedSize'][$headers['x-content-name']]);
            SendIt::setSession($this->modx, ['uploadedSize' => $this->session['uploadedSize']]);
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

        $nameParts = explode('.', $headers['x-content-name']);
        $dir = $uploaddir . $nameParts[0] . '/';
        $chunkName = $headers['x-chunk-id'] . '.' . $nameParts[1];
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        if (!file_exists($dir . $chunkName) || filesize($dir . $chunkName) < $headers['content-length']) {
            file_put_contents($dir . $chunkName, $content);
        }

        $this->session['uploadedSize'][$headers['x-content-name']] += filesize($dir . $chunkName);

        $percent = $this->getPercent($this->session['uploadedSize'][$headers['x-content-name']], $headers['x-total-length']);
        $msg = $this->getLoadingMsg($percent, $this->session['uploadedSize'][$headers['x-content-name']], $headers['x-total-length'], $headers['x-content-name']);

        if ($this->session['uploadedSize'][$headers['x-content-name']] < $headers['x-total-length']) {
            SendIt::setSession($this->modx, ['uploadedSize' => $this->session['uploadedSize']]);
            return $this->success($msg, [
                'percent' => "$percent%",
                'bytes' => $this->session['uploadedSize'][$headers['x-content-name']],
                'filename' => $headers['x-content-name'],
                'chunkId' => $headers['x-chunk-id'],
            ]);
        }

        $i = 0;
        while (file_exists($dir . $i . '.' . $nameParts[1])) {
            $name = $dir . $i . '.' . $nameParts[1];
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

        unset($this->session['uploadedSize'][$headers['x-content-name']]);
        SendIt::setSession($this->modx, ['uploadedSize' => $this->session['uploadedSize']]);
        SendIt::removeDir($dir);
        return $this->success($msg, [
            'path' => str_replace($this->basePath, '', $this->uploaddir) . session_id() . '/' . $headers['x-content-name'],
            'percent' => "$percent%",
            'filename' => $headers['x-content-name'],
            'chunkId' => $headers['x-chunk-id'],
        ]);
    }

    protected function getLoadingMsg(int $percent, int $uploadedSize, int $totalSize, string $filename)
    {
        $unit = $this->params['loadedUnit'] ?: '%';
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

    protected function getPercent(int $uploadedSize, int $totalSize)
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
    public static function removeDir(string $dir): void
    {
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

    public function removeFile(string $path, ?bool $nomsg = false)
    {
        $filename = basename($path);
        $dir = str_replace($filename, '', $path);

        if (strpos($path, session_id()) === false) {
            return $this->error('si_msg_file_remove_session_err', [], ['filename' => $filename]);
        } else {
            unset($this->session['uploadedSize'][$filename]);
            SendIt::setSession($this->modx, ['uploadedSize' => $this->session['uploadedSize']]);

            if (file_exists($path)) {
                unlink($path);
            } else {
                if (file_exists($dir)) {
                    $this->removeDir($dir);
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
     * @param string $message
     * @param array $data
     * @param array $placeholders
     *
     * @return array|string
     */
    public function error($message = '', $data = [], $placeholders = [])
    {
        return $this->getResponse(false, $message ?? '', $data ?? [], $placeholders ?? []);
    }

    /**
     *
     * @param string $message
     * @param array $data
     * @param array $placeholders
     *
     * @return array|string
     */
    public function success($message = '', $data = [], $placeholders = [])
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
     * @return array|string
     */
    private function getResponse(bool $status, string $message = '', array $data = [], array $placeholders = [])
    {
        $this->modx->invokeEvent('OnBeforeReturnResponse', [
            'formName' => $this->formName,
            'presetName' => $this->presetName,
            'SendIt' => $this
        ]);

        $data = array_merge($this->params, $data);
        if ($unsetParams = $this->modx->getOption('si_unset_params', '', 'emailTo,extends')) {
            $unsetParams = explode(',', $unsetParams);
            foreach ($unsetParams as $param) {
                unset($data[$param]);
            }
        }
        unset($data['SendIt']);

        return [
            'success' => $status,
            'message' => $this->modx->lexicon($message, $placeholders),
            'data' => $data,
        ];
    }

    public static function setSession($modx, $values = [], ?string $sessionId = '', ?string $className = 'SendIt')
    {
        $sessionId = $sessionId ?: session_id();
        if (!$session = $modx->getObject('siSession', ['session_id' => $sessionId, 'class_name' => $className])) {
            $session = $modx->newObject('siSession');
        }
        if (!$session) {
            $modx->log(1, print_r('Table si_sessions not found', 1));
            return [];
        }
        $item = $session->get('data') ? json_decode($session->get('data'), true) : [];
        $item = array_merge($item, $values);
        $session->fromArray([
            'session_id' => $sessionId,
            'data' => $item ? json_encode($item): '',
            'class_name' => $className,
            'createdon' => time(),
        ]);
        $session->save();
    }

    public static function getSession($modx, ?string $sessionId = '', ?string $className = 'SendIt')
    {
        $sessionId = $sessionId ?: session_id();
        if (!$session = $modx->getObject('siSession', ['session_id' => $sessionId, 'class_name' => $className])) {
            return [];
        }
        return $session->get('data') ? json_decode($session->get('data'), true) : [];
    }

    public static function clearSession($modx, ?string $className = 'SendIt')
    {
        $assetsPath = $modx->getOption('core_path', null, MODX_ASSETS_PATH);
        $uploaddir = $modx->getOption('si_uploaddir', '', '[[+asseetsUrl]]components/sendit/uploaded_files/');
        $uploaddir = str_replace('[[+asseetsUrl]]', $assetsPath, $uploaddir);
        $storageTime = $modx->getOption('si_storage_time', '', 86400);
        $max = date('Y-m-d H:i:s', time() - $storageTime);
        $sessions = $modx->getIterator('siSession', ['class_name' => $className, 'createdon:<' => $max]);
        foreach ($sessions as $session) {
            if ($className === 'SendIt') {
                SendIt::removeDir($uploaddir . $session->get('session_id'));
            }
            $session->remove();
        }
    }
}
