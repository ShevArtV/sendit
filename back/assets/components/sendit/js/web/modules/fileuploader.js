export default class FileUploaderFactory {
    constructor(config) {
        if (window.SendIt && window.SendIt.FileUploaderFactory) return window.SendIt.FileUploaderFactory;
        this.rootSelector = config['rootSelector'] || '[data-fu-wrap]';
        this.sendEvent = config['sendEvent'] || 'si:send:after';
        this.pathAttr = config['pathAttr'] || 'data-fu-path';
        this.deleteFromQueueAttr = config['deleteFromQueueAttr'] || 'data-fu-delete',
            this.config = config;
        this.instances = new Map();

        document.addEventListener('si:init', (e) => {
            this.initialize();
        });
    }

    initialize() {

        this.addInstances(document)

        document.addEventListener('change', (e) => {
            const root = e.target.closest(this.rootSelector);
            if (this.instances.has(root)) {
                const fileUploader = this.instances.get(root);
                fileUploader.changeEventHandler();
            }
        });

        document.addEventListener(this.sendEvent, async (e) => {
            const {target, result} = e.detail;
            if (target === document) return true;
            if (!result.data) return true;
            if (this.instances.has(target)) {
                const fileUploader = this.instances.get(target);
                await fileUploader.sendEventHandler(e.detail);
            } else {
                if (result.success && result.data.clearFieldsOnSuccess) {
                    const fileWraps = target.querySelectorAll(this.config.rootSelector);
                    fileWraps.forEach(fileWrap => {
                        if (fileWrap && this.instances.has(fileWrap)) {
                            const fileUploader = this.instances.get(fileWrap);
                            fileUploader.clearFields();
                        }
                    })
                }
            }
        });

        document.addEventListener('click', (e) => {
            const root = e.target.closest(this.rootSelector);
            if (this.instances.has(root)) {
                const fileUploader = this.instances.get(root);
                if (e.target.closest(`[${this.pathAttr}]`)) {
                    fileUploader.removeFile(e.target.closest(`[${this.pathAttr}]`))
                }
                if (e.target.closest(`[${this.deleteFromQueueAttr}]`)) {
                    fileUploader.deleteFileFromQueue(e.target.closest(`[${this.deleteFromQueueAttr}]`))
                }
            }
        })
    }

    addInstances(block) {
        const roots = block.querySelectorAll(this.rootSelector);
        if (roots.length) {
            roots.forEach(root => {
                if (!this.instances.has(root)) {
                    this.instances.set(root, new FileUploader(root, this.config));
                    const dropzone = root.querySelector(this.config.dropzoneSelector);
                    if (dropzone) {
                        dropzone.addEventListener('dragover', (e) => {
                            e.preventDefault();
                        });

                        dropzone.addEventListener('drop', (e) => {
                            e.preventDefault();
                            const fileInput = dropzone.querySelector('[type="file"]');
                            fileInput.files = e.dataTransfer.files;
                            fileInput.dispatchEvent(new Event('change', {bubbles: true}))
                        });
                    }
                }
            })
        }
    }
}

class FileUploader {
    constructor(root, config) {
        if (window.SendIt && window.SendIt.FileUploaderFactory && window.SendIt.FileUploaderFactory.instances.has(root)) {
            return window.SendIt.FileUploaderFactory.instances.get(root);
        }

        const defaults = {
            formSelector: '[data-si-form]',
            progressSelector: '[data-fu-progress]',
            rootSelector: '[data-fu-wrap]',
            tplSelector: '[data-fu-tpl]',
            dropzoneSelector: '[data-fu-dropzone]',
            fileListSelector: '[data-fu-list]',
            progressIdAttr: 'data-fu-id',
            progressIdKey: 'fuId',
            progressTextAttr: 'data-fu-text',
            hideBlockSelector: '[data-fu-hide]',
            presetSelector: '[data-si-preset]',
            presetKey: 'siPreset',
            sendEvent: 'si:send:after',
            pathKey: 'fuPath',
            pathAttr: 'data-fu-path',
            actionUrl: 'assets/components/sendit/action.php',
            hiddenClass: 'v_hidden',
            progressClass: 'progress__line',
            showTime: true
        }
        this.config = Object.assign(defaults, config);

        this.root = root;
        this.field = root.querySelector('[type="file"]');
        this.listField = root.querySelector(this.config.fileListSelector);
        this.preset = this.root.closest(this.config.presetSelector).dataset[this.config.presetKey];
        this.position = 0;
        this.multiplier = 1024 * 1024;
        this.activeConnections = 0;
        this.filePath = {};
        this.start = {};
        this.chunksQueue = {};
        this.filesQueue = [];
        this.filesInQueue = new Map();
        this.queueMsg = '';
        this.times = {};
        this.field.value = '';

        this.events = {
            uploadingStart: 'fu:uploading:start',
            uploadingEnd: 'fu:uploading:end',
            removePreview: 'fu:preview:remove',
        }
    }

    changeEventHandler() {
        const fileNameList = this.listField.value ? this.listField.value.split(',') : [];
        const files = Array.from(this.field.files);
        const result = this.removeFromFileList(files, fileNameList);
        this.field.files = result.files;
        if (!result.filesData) return;
        this.validateFiles(result.filesData);
    }

    async sendEventHandler(detail) {
        const {result, action} = detail;
        const files = Array.from(this.field.files);
        const form = this.root.closest('form');
        switch (action) {
            case 'validate_files':
                this.queueMsg = result.data.queueMsg;
                if (result.success) {
                    if (result.data.loaded) {
                        this.addLoadFiles(result.data.loaded);
                    }
                    if (result.data.start) {
                        this.start = result.data.start;
                    }
                    this.prepareUpload(result.data, form)
                } else {
                    if (result.data.fileNames) {
                        const res = this.removeFromFileList(files, result.data.fileNames);
                        this.field.files = res.files;
                        if (!this.field.files) return false;
                        this.prepareUpload(result.data, form)
                    }
                }
                break;
            case 'removeFile':
                this.removeFromList(result.data.filename);
                this.removePreview(result.data.path, result.data.nomsg);
                break;
        }
    }

    addLoadFiles(loaded) {
        for (let filename in loaded) {
            const file = this.getFileByName(filename);
            this.filesInQueue.set(filename, file);
            this.filePath[filename] = loaded[filename];
            this.renderPreview(filename);
            this.addToList(filename);
        }
    }

    getFileByName(filename) {
        if (!this.field.files) return false;

        for (let i = 0; i < this.field.files.length; i++) {
            if (this.field.files[i].name === filename) {
                return this.field.files[i];
            }
        }

        return false;
    }

    clearFields() {
        const btns = this.root.querySelectorAll(`[${this.config.pathAttr}]`);
        if (btns.length) {
            btns.forEach(btn => this.removeFile(btn, true))
        }
    }

    validateFiles(filesData) {
        const fileList = [];
        const headers = {
            'X-SIACTION': 'validate_files',
            'X-SIPRESET': this.preset,
            'X-SITOKEN': SendIt?.getComponentCookie('sitoken')
        }
        const params = new FormData();
        for (let [key, value] of this.filesInQueue.entries()) {
            fileList.push(key)
        }
        params.append('filesData', JSON.stringify(filesData));
        params.append('fileList', fileList.join(','));

        SendIt?.setComponentCookie('sitrusted', '1');
        SendIt?.Sending?.send(this.root, this.config.actionUrl, headers, params);
    }

    prepareUpload(data, form) {
        this.portion = data.portion * this.multiplier;
        this.threadsQuantity = data.threadsQuantity || 6;
        form && form.querySelectorAll('[type="submit"]').forEach(btn => btn.disabled = true);
        this.field.disabled = true;
        this.progressWrap = this.root.querySelector(this.config.progressSelector);
        this.addFileToQueue();
        if (!document.dispatchEvent(new CustomEvent(this.events.uploadingStart, {
            bubbles: true,
            cancelable: true,
            detail: {
                form: form,
                root: this.root,
                field: this.field,
                files: this.field.files,
                FileUploader: this
            }
        }))) {
            return;
        }

        this.startUpload(this.filesQueue[0]);
    }

    addFileToQueue() {
        for (let i = 0; i < this.field.files.length; i++) {
            const filename = this.translitName(this.field.files[i].name);
            if (this.filesInQueue.has(filename)) {
                continue;
            }
            this.filesQueue.push(this.field.files[i]);
            this.filesInQueue.set(filename, this.field.files[i]);
            this.setProgress(filename, 0, filename + this.queueMsg);
        }
    }

    deleteFileFromQueue(el = false) {
        let index = 0;
        if (el) {
            for (let i = 0; i < this.filesQueue.length; i++) {
                if (el.dataset[this.config.progressIdKey] === this.filesQueue[i].name) {
                    index = i;
                    if (this.filesQueue[index]) {
                        const filename = this.translitName(this.filesQueue[index].name);
                        this.removeProgressBar(filename, false);
                        this.filesInQueue.delete(filename);
                    }
                    break;
                }
            }
        }

        this.filesQueue.splice(index, 1);
    }

    startUpload(file) {
        if (!file) {
            this.finishUpload();
            return;
        }
        const filename = this.translitName(file.name);
        const parts = filename.split('.');
        const chunksQuantity = Math.ceil(file.size / this.portion);
        const totalChunks = new Array(chunksQuantity).fill().map((_, index) => index);
        const chunksQueue = [];
        this.times[filename] = new Date();
        this.chunksQueue[filename] = this.chunksQueue[filename] || totalChunks;
        if (this.start[filename]) {
            const chunks = this.start[filename]['chunks'].split(',');
            if(chunks.length < totalChunks.length){
                for (let i = 0; i < totalChunks.length; i++) {
                    const key = `${totalChunks[i]}.${parts[1]}`;
                    if (!chunks.includes(key)) {
                        chunksQueue.push(totalChunks[i]);
                    }
                }
                this.chunksQueue[filename] = chunksQueue;
                this.setProgress(filename, this.start[filename]['percent'], this.start[filename]['msg']);
                delete this.start[filename];
            }
        }

        this.chunksQueue[filename] && this.sendNext(file, filename);
    }

    finishUpload() {
        const form = this.root.closest('form');
        this.field.disabled = false;
        this.field.value = '';
        form && form.querySelectorAll('[type="submit"]').forEach(btn => btn.disabled = false);

        document.dispatchEvent(new CustomEvent(this.events.uploadingEnd, {
            bubbles: true,
            cancelable: true,
            detail: {
                root: this.root,
                field: this.field,
                files: this.field.files,
                FileUploader: this
            }
        }))
    }

    async sendNext(file, filename) {
        if (this.chunksQueue[filename] && !this.chunksQueue[filename].length) {
            delete this.chunksQueue[filename];
            return;
        }
        if (this.activeConnections >= this.threadsQuantity) {
            return;
        }
        this.activeConnections++;
        const chunkId = this.chunksQueue[filename].pop();
        const begin = chunkId * this.portion;
        const chunk = file.slice(begin, begin + this.portion);
        this.uploadChunk(chunk, chunkId, file, filename);
        this.chunksQueue[filename] && this.sendNext(file, filename);
    }

    uploadChunk(chunk, chunkId, file, filename) {
        return fetch(SendIt?.Sending?.config.actionUrl, {
            method: 'POST',
            headers: {
                'CONTENT-TYPE': 'application/octet-stream',
                'X-CHUNK-ID': chunkId,
                'CONTENT-LENGTH': chunk.size,
                'X-TOTAL-LENGTH': file.size,
                'X-CONTENT-NAME': filename,
                'X-SIACTION': 'uploadChunk',
                'X-SIPRESET': this.preset,
                'X-SITOKEN': SendIt?.getComponentCookie('sitoken')
            },
            body: chunk
        }).then(async (response) => {
            this.fileUploadEnd(response, file, filename);
        });
    }

    async fileUploadEnd(response, file, filename) {
        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                this.activeConnections--;
                this.setProgress(result.data.filename, result.data.percent, result.message);
                if (!result.data.path) {
                    this.chunksQueue[filename] && this.sendNext(file, result.data.filename);
                    return;
                }
                this.filePath[result.data.filename] = result.data.path;
                this.removeProgressBar(result.data.filename);
                this.renderPreview(result.data.filename);
                this.addToList(result.data.filename);
                if (this.config.showTime) {
                    console.log('Время загрузки файла ' + filename + ': ' + (new Date - this.times[filename]) / 1000);
                }
                this.deleteFileFromQueue();
                delete this.chunksQueue[result.data.filename];
                this.startUpload(this.filesQueue[0]);
            } else {
                SendIt?.Notify?.error(result.message);
            }
        }
    }

    removeFromFileList(files, fileNameList) {
        const newFileList = new DataTransfer();
        const filesData = {};
        files.map(file => {
            const filename = this.translitName(file.name);
            if (!fileNameList.includes(filename)) {
                newFileList.items.add(file);
                filesData[filename] = file.size;
            }
        })
        return {filesData, files: newFileList.files};
    }

    removePreview(path, nomsg = false) {
        const preview = this.root.querySelector(`[${this.config.pathAttr}="${path}"]`);
        preview && preview.remove();

        document.dispatchEvent(new CustomEvent(this.events.removePreview, {
            bubbles: true,
            cancelable: true,
            detail: {
                path: path,
                root: this.root,
                preview: preview,
                nomsg: Number(nomsg),
                FileUploader: this
            }
        }))
    }

    removeFromList(filename) {
        const hide = this.root.querySelector(this.config.hideBlockSelector);
        let fileList = this.listField.value ? this.listField.value.split(',') : [];
        fileList = fileList.filter(name => name !== filename);
        !fileList.length && hide && hide.classList.remove(this.config.hiddenClass);
        this.listField.value = fileList.join(',');
    }

    removeFile(btn, nomsg = 0) {
        const filename = btn.dataset[this.config.pathKey].split('/')
        this.filesInQueue.delete(filename[filename.length - 1]);

        const params = new FormData();
        params.append('path', btn.dataset[this.config.pathKey]);
        params.append('nomsg', nomsg);
        const headers = {
            'X-SIACTION': 'removeFile',
            'X-SIPRESET': 'removeFile',
            'X-SITOKEN': SendIt?.getComponentCookie('sitoken')
        }

        SendIt?.setComponentCookie('sitrusted', '1');
        SendIt?.Sending?.send(this.root, this.config.actionUrl, headers, params);
    }

    addProgressBar(filename) {
        if (!this.progressWrap) return false;
        const div = document.createElement('div');
        const span = document.createElement('span');
        span.classList.add(this.config.progressClass);
        div.setAttribute(this.config.progressIdAttr, filename);
        div.appendChild(span);
        this.progressWrap.appendChild(div);
    }

    setProgress(filename, percent, msg) {
        if (!this.progressWrap) return false;
        if (!this.progressWrap.querySelector(`[${this.config.progressIdAttr}="${filename}"]`)) {
            this.addProgressBar(filename);
        }
        const progressLine = this.progressWrap.querySelector(`[${this.config.progressIdAttr}="${filename}"] span`);
        const progressLineWrap = this.progressWrap.querySelector(`[${this.config.progressIdAttr}="${filename}"]`);
        if (percent) {
            progressLineWrap.removeAttribute('data-fu-delete')
        } else {
            progressLineWrap.setAttribute('data-fu-delete', 1)
        }
        progressLine && percent && (progressLine.style.width = percent)
        progressLineWrap && msg && (progressLineWrap.setAttribute(this.config.progressTextAttr, msg))
    }

    removeProgressBar(filename, delay = true) {
        if (!this.progressWrap) return false;

        const progressLineWrap = this.progressWrap.querySelector(`[${this.config.progressIdAttr}="${filename}"]`);
        if (delay) {
            setTimeout(() => {
                progressLineWrap.remove()
            }, 2000)
        } else {
            progressLineWrap.remove()
        }

    }

    renderPreview(filename) {
        if (!this.filePath[filename]) return;
        const dropzone = this.root.querySelector(this.config.dropzoneSelector);
        const tpl = this.root.querySelector(this.config.tplSelector)?.cloneNode(true);
        if (!tpl) return;
        tpl.innerHTML = tpl.innerHTML.replaceAll('$path', this.filePath[filename]).replaceAll('$filename', filename);
        dropzone ? dropzone.appendChild(tpl.content) : this.root.appendChild(tpl.content);
    }

    addToList(filename) {
        const hide = this.root.querySelector(this.config.hideBlockSelector);
        const fileList = this.listField.value ? this.listField.value.split(',') : [];
        if (!fileList.includes(filename)) {
            fileList.push(filename);
        }
        fileList.length && hide && hide.classList.add(this.config.hiddenClass);
        this.listField.value = fileList.join(',');
    }

    translitName(filename) {
        const parts = filename.split('.');
        const ext = parts.pop();
        filename = parts.join('.');
        var converter = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
            'е': 'e', 'ё': 'e', 'ж': 'zh', 'з': 'z', 'и': 'i',
            'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
            'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
            'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch',
            'ш': 'sh', 'щ': 'sch', 'ь': '', 'ы': 'y', 'ъ': '',
            'э': 'e', 'ю': 'yu', 'я': 'ya'
        };

        let word = filename.toLowerCase();

        var answer = '';
        for (var i = 0; i < word.length; ++i) {
            if (converter[word[i]] === undefined) {
                answer += word[i];
            } else {
                answer += converter[word[i]];
            }
        }

        answer = answer.replace(/[^-0-9a-z\.]/g, '-');
        answer = answer.replace(/[-]+/g, '-');
        answer = answer.replace(/^\-|-$/g, '');
        return `${answer}.${ext}`;
    }
}
