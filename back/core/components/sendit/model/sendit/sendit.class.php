<?php


class SendIt
{
    /** @var modX $modx */
    public modX $modx;
    /** @var string $formName */
    public string $formName;
    /** @var string $basePath */
    public string $basePath;
    /** @var string $jsConfigPath */
    public string $jsConfigPath;
    /** @var string $uploaddir */
    public string $uploaddir;
    /** @var string $pathToPresets */
    public string $pathToPresets;
    /** @var array $presets */
    public array $presets;
    /** @var array $preset */
    public array $preset;
    /** @var array $extendsPreset */
    public array $extendsPreset;
    /** @var array $formParams */
    public array $formParams;
    /** @var array $params */
    public array $params;
    /** @var array $validates */
    public array $validates;
    /** @var array $defaltValidators */
    public array $defaltValidators;


    /**
     * @param modX $modx
     * @param string $presetName
     * @param string $formName
     */
    public function __construct(modX $modx, $presetName = '', $formName = '')
    {
        $this->modx = $modx;
        $this->formName = $formName;
        $this->basePath = $modx->getOption('base_path');
        $this->jsConfigPath = $modx->getOption('si_js_config_path', '', './sendit.inc.js');
        $this->uploaddir = $modx->getOption('si_uploaddir', '', '/assets/components/sendit/uploaded_files/');
        $pathToPresets = $modx->getOption('si_path_to_presets', '', '/core/components/sendit/presets/sendit.inc.php');
        $this->pathToPresets = $this->basePath . $pathToPresets;
        $this->presets = include $this->pathToPresets;
        $this->preset = $this->presets[$presetName] ?: [];
        $this->extendsPreset = $this->presets[$this->preset['extends']] ?: [];
        $this->formParams = $this->getFormParams();
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

        $this->initialize();
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        $this->modx->lexicon->load('sendit:default');

        $this->setParams();

        $this->params['sendGoal'] = $this->params['sendGoal'] ?: $this->modx->getOption('si_send_goal', '', false);
        $this->params['counterId'] = $this->params['counterId'] ?: $this->modx->getOption('si_counter_id', '', '');
        $this->params['formName'] = $this->params['formName'] ?: $this->formName;
        $this->validates = $this->getValidate($this->params['validate']);

        foreach ($_POST as $k => $v) {
            $this->setValue($v, $k);
            if(is_array($v)){
                $_POST[$k] = json_encode($v);
            }
            $_POST['fields'] = json_encode($_POST);
        }

        $this->removeUselessField();

        if ($this->params['fieldNames']) {
            $this->setFieldsAliases();
        }

        if($this->params['attachFilesToEmail'] && $this->params['allowFiles']){
            $this->attachFiles();
        }

        //$this->modx->log(1, print_r($this->validates, 1));
        //$this->modx->log(1, print_r($_POST, 1));
        //$this->modx->log(1, print_r($this->params, 1));
        $this->setValidate();
    }

    private function setParams(){
        $this->params = array_merge($this->extendsPreset, $this->preset, $this->formParams);
        if(empty($this->params)){
            $profile = $this->modx->getObject('modUserProfile', ['internalKey' => 1]);
            $email = $this->modx->getOption('si_default_email');
            $emailTpl = $this->modx->getOption('si_default_emailtpl', '', 'siDefaultEmail');
            $email = $email ? $this->modx->getOption('ms2_email_manager') : $profile->get('email');
            $hooks = $email ? 'FormItSaveForm,email' : 'FormItSaveForm';
            $this->params = [
                'successMessage' => $this->modx->lexicon('si_msg_success'),
                'hooks' => $hooks,
                'emailTpl' => $emailTpl,
                'emailTo' => $email,
                'formName' => $this->modx->lexicon('si_default_formname'),
                'emailSubject' => $this->modx->lexicon('si_default_subject', ['host' => $this->modx->getOption('http_host')]),
            ];
        }
    }

    public function loadCssJs(){
        $frontend_js = $this->modx->getOption('si_frontend_js', '', '[[+assetsUrl]]components/sendit/web/js/sendit.js');
        $frontend_css = $this->modx->getOption('si_frontend_css', '', '[[+assetsUrl]]components/sendit/web/css/index.min.css');
        $assetsUrl = str_replace($this->basePath, '', $this->modx->getOption('assets_path'));

        if($frontend_js){
            $scriptPath = str_replace('[[+assetsUrl]]', $assetsUrl, $frontend_js);
            $this->modx->regClientScript(
                '<script type="module" src="' . $scriptPath . '"></script>', true
            );
        }
        if($frontend_css){
            $stylePath = str_replace('[[+assetsUrl]]', $assetsUrl, $frontend_css);
            $this->modx->regClientCSS($stylePath);
        }
    }

    /**
     * @return void
     */
    private function attachFiles(): void
    {
        $fileList = $_POST[$this->params['allowFiles']];
        $fieldKey = $this->params['attachFilesToEmail'];

        if($fileList){
            $fileList = explode(',', $fileList);
            $_FILES[$fieldKey]['name'] = [];
            $_FILES[$fieldKey]['type'] = [];
            $_FILES[$fieldKey]['tmp_name'] = [];
            $_FILES[$fieldKey]['error'] = [];
            $_FILES[$fieldKey]['size'] = [];
            foreach($fileList as $path){
                $fullpath = $this->basePath . $path;
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
            foreach($allValidators as $validator){
                $items = explode('=', $validator);
                if(!in_array($items[0], $this->defaltValidators)){
                    $output[] = $items[0];
                }
            }

            if (!empty($output)) {
                $this->params['customValidators'] = implode(',', $output);
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
        foreach ($fields as $key => $field) {
            $f = explode('==', trim($field));
            $result[$key] = $f[1];
        }
        $_POST['fieldsAliases'] = $this->modx->toJSON($result);
    }

    /**
     * @return array
     */
    public function getPreset(): string
    {
        return $this->success('', $this->params);
    }


    /**
     * @param $value
     * @param $key
     * @return void
     */
    private function setValue($value, $key): void
    {
        if (!is_array($value)) {
            $_POST[$key] = $value;
            $k = preg_replace('/\[\d*?\]/', '[*]', $key);
            if ($this->validates[$k] && !$this->validates[$key]) {
                $this->validates[$key] = $this->validates[$k];
            }
        }else{
            $_POST[$key. '[]'] = implode(', ', $value);
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
        if (!$validate) return $output;
        $validates = explode(',', $validate);

        foreach ($validates as $v) {
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
        $this->modx->invokeEvent('OnGetFormParams',[
            'formName' => $this->formName,
        ]);

        return is_array($this->modx->event->returnedValues) ? $this->modx->event->returnedValues : [];
    }

    /**
     * @return array|string
     */
    public function process()
    {
        $snippet = $this->params['snippet'] ?: 'FormIt';

        if ($snippet !== 'FormIt') {
            if ($this->params['validate']) {
                $this->modx->runSnippet('FormIt', $this->params);
                $result = $this->handleFormIt();
                if (!$result['success']) {
                    return $this->error($result['message'], $result['data']);
                }
            }
            return $this->runSnippet($snippet);
        }else{
            $this->modx->runSnippet('FormIt', $this->params);
            $result = $this->handleFormIt();
            $status = $result['success'] ? 'success' : 'error';
            return $this->$status($result['message'], $result['data']);
        }
    }

    /**
     * @param string $snippet
     * @return mixed
     */
    private function runSnippet(string $snippet)
    {
        $this->params['SendIt'] = $this;
        $pdo = $this->modx->getService('pdoTools');
        if ($pdo) {
            return $this->pdo->runSnippet($snippet, $this->params);
        } else {
            return $this->modx->runSnippet($snippet, $this->params);
        }
    }

    /**
     * @return array
     */
    private function handleFormIt(): array
    {
        $plPrefix = $this->params['placeholderPrefix'] ?: 'fi.';
        $data = [];
        foreach ($_POST as $k => $v) {
            if (isset($this->modx->placeholders[$plPrefix . 'error.' . $k])) {
                $data['errors'][$k] = strip_tags($this->modx->placeholders[$plPrefix . 'error.' . $k]);
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

        return ['success' => $success, 'message' => $message, 'data' => array_merge($this->params, $data)];
    }

    /**
     * @param string $filename
     * @param int $filesize
     * @param float $portionSize
     * @param float $from
     * @param int $currentIndex
     * @return array|string
     */
    public function uploadFile(string $filename, int $filesize, float $portionSize, float $from, int $currentIndex)
    {
        $uploaddir = $this->basePath . $this->uploaddir . session_id() . '/';
        $filepath = $uploaddir . $filename;
        $path = $this->uploaddir . session_id() . '/' . $filename;
        $size = file_exists($filepath) ? filesize($filepath) : 0;
        $percent = round(($from + $portionSize) * 100 / $filesize);
        $loadingMsg = $this->modx->lexicon('si_msg_loading', ['filename' => $filename, 'percent' => ($percent < 100 ? $percent : 100)]);
        if (!is_dir($uploaddir)) {
            mkdir($uploaddir);
        }

        if ($size < $filesize) {
            if (intval($from) == 0) {
                $fout = fopen($filepath, "wb");
            } else {
                $fout = fopen($filepath, "ab");
            }

            if ($fout) {
                $fin = fopen("php://input", "rb");
                if ($fin) {
                    while (!feof($fin)) {
                        $data = fread($fin, 1024 * 1024);
                        fwrite($fout, $data);
                    }
                    fclose($fin);
                }
                fclose($fout);

            } else {
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                return $this->error('si_msg_file_open_err', ['position' => $from], ['filename' => $filename]);
            }
            return $this->success('', ['position' => $from + $portionSize, 'currentIndex' => $currentIndex, 'loadingMsg' => $loadingMsg], ['filename' => $filename]);
        } else {
            return $this->success('si_msg_loaded', ['position' => $filesize, 'path' => $path, 'filename' => $filename, 'nextIndex' => ++$currentIndex], ['filename' => $filename]);
        }
    }

    /**
     * @param string $filename
     * @param int $filesize
     * @param int $loadedCount
     * @return array
     */
    public function validateFile(string $filename, int $filesize, int $loadedCount): array
    {
        $data = [
            'filename' => $filename,
            'filesize' => $filesize,
            'loadedCount' => $loadedCount
        ];
        $status = true;
        $allowExt = explode(',', $this->params['allowExt']) ?: [];
        $maxSize = (float)$this->params['maxSize'] * 1024 * 1024;
        $maxCount = (int)$this->params['maxCount'];
        $maneParts = explode('.', $filename);
        if ($maxCount <= $loadedCount) {
            $data['errors'][] = $this->modx->lexicon('si_msg_files_count_err', ['filename' => $filename]);
            $status = false;
        }
        if ($maxSize <= $filesize) {
            $data['errors'][] = $this->modx->lexicon('si_msg_file_size_err', ['filename' => $filename]);
            $status = false;
        }
        if (!in_array($maneParts[count($maneParts) - 1], $allowExt)) {
            $data['errors'][] = $this->modx->lexicon('si_msg_file_extention_err', ['filename' => $filename]);
            $status = false;
        }
        return ['success' => $status, 'data' => $data];
    }

    /**
     * @param string $dir
     * @return void
     */
    public function removeDir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object))
                        $this->removeDir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            if (file_exists($dir) && is_dir($dir)) {
                rmdir($dir);
            }
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
        return $this->getResponse(false, $message, $data, $placeholders);
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
        return $this->getResponse(true, $message, $data, $placeholders);
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
    private function getResponse(bool $status, $message = '' , $data = [], $placeholders = []){
        $response = [
            'success' => $status,
            'message' => $this->modx->lexicon($message, $placeholders),
            'data' => $data,
        ];

        return $this->modx->toJSON($response);
    }
}