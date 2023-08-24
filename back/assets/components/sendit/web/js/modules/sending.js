export default class Sending {
    constructor(config) {
        const defaults = {
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            presetKey: 'siPreset',
            actionUrl: 'assets/components/sendit/web/action.php',
            antiSpamEvent: 'click',
            errorBlockSelector: '[data-si-error="${fieldName}"]',
            eventSelector: '[data-si-event="${eventName}"]',
            errorClass: 'si-error'
        }
        this.events = {
            before: 'si:send:before',
            reset: 'si:send:reset',
            after: 'si:send:after',
            success: 'si:send:success',
            error: 'si:send:error',
            finish: 'si:send:finish',
        };
        this.config = Object.assign(defaults, config);

        document.addEventListener('si:init', (e) => {
            this.initialize();
        });
    }

    initialize() {
        document.addEventListener(this.config.antiSpamEvent, (e) => {
            if (e.isTrusted) {
                SendIt?.setSenditCookie('sitrusted', '1');
            }
        });

        document.addEventListener('submit', (e) => {
            if(!e.isTrusted) return;
            const root = e.target.closest(this.config.rootSelector);

            if (root) {
                e.preventDefault();
                this.prepareSendParams(root, root.dataset[this.config.presetKey]);
            }
        });

        document.addEventListener('change', this.sendField.bind(this));
        document.addEventListener('input', this.sendField.bind(this));
    }

    sendField(e) {
        if(!e.isTrusted) return;
        const field = e.target.closest(this.config.eventSelector.replace('${eventName}', e.type));
        const root = e.target.closest(this.config.rootSelector);
        if (root) {
            this.resetError(e.target.name, root);
        }
        if (field) {
            if(!field.value) return;
            this.prepareSendParams(field, field.dataset[this.config.presetKey]);
        }
    }

    prepareSendParams(root, preset = '', action = 'send') {
        const params = root.tagName === 'FORM' ? new FormData(root) : new FormData();
        if (root.name && root.tagName !== 'FORM') {
            params.append(root.name, root.value);
        }

        const headers = {
            'X-SIFORM': root.dataset[this.config.rootKey],
            'X-SIACTION': action,
            'X-SIPRESET': preset,
            'X-SITOKEN': SendIt?.getSenditCookie('sitoken')
        }

        this.send(root, this.config.actionUrl, headers, params);
    }

    async send(target, url, headers, params, method = 'POST') {
        if (SendIt?.getSenditCookie('sitrusted') === '0') return;
        console.log(headers);
        if (!document.dispatchEvent(new CustomEvent(this.events.before, {
            bubbles: true,
            cancelable: true,
            detail: {
                url: url,
                target: target,
                params: params,
                headers: headers,
                method: method,
                Sending: this
            }
        }))) {
            return;
        }

        this.resetAllErrors(target);

        const response = await fetch(this.config.actionUrl, {
            method: method,
            body: params,
            headers: headers
        });

        const result = await response.json();

        console.log(result);

        if (!document.dispatchEvent(new CustomEvent(this.events.after, {
            bubbles: true,
            cancelable: true,
            detail: {
                action: headers['X-SIACTION'],
                target: target,
                result: result,
                Sending: this
            }
        }))) {
            return;
        }

        if (result.success) {
            if (!document.dispatchEvent(new CustomEvent(this.events.success, {
                bubbles: true,
                cancelable: true,
                detail: {
                    action: headers['X-SIACTION'],
                    target: target,
                    result: result,
                    Sending: this
                }
            }))) {
                return;
            }

            this.success(result, target)
        }
        else {
            if (!document.dispatchEvent(new CustomEvent(this.events.error, {
                bubbles: true,
                cancelable: true,
                detail: {
                    action: headers['X-SIACTION'],
                    target: target,
                    result: result,
                    Sending: this
                }
            }))) {
                return;
            }

            this.error(result, target)
        }

        document.dispatchEvent(new CustomEvent(this.events.finish, {
            bubbles: true,
            cancelable: false,
            detail: {
                action: headers['X-SIACTION'],
                target: target,
                result: result,
                Sending: this
            }
        }))
    }

    success(result, root) {
        const redirectUrl = result.data.redirectUrl;
        const redirectTimeout = Number(result.data.redirectTimeout) || 0;

        SendIt?.Notify?.success(result.message);

        if (result.data.goalName && result.data.counterId && result.data.sendGoal && typeof window.ym !== 'undefined') {
            ym(result.data.counterId, 'reachGoal', result.data.goalName);
        }

        if (redirectUrl) {
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, redirectTimeout);
        }


        if (result.data.clearFieldsOnSuccess) {
            this.resetForm(root);
        }
    }

    error(result, root) {
        if (!result.data.errors) {
            if (SendIt?.getSenditCookie('sitrusted') === '0'){
                SendIt?.Notify?.info(SendIt?.getSenditCookie('simsgantispam'));
            }else{
                SendIt?.Notify?.error(result.message);
            }
        } else {
            for (let k in result.data.errors) {
                const errorBlock = root.querySelector(this.config.errorBlockSelector.replace('${fieldName}', k));
                const fields = root.querySelectorAll(`[name="${k}"]`);
                if(fields.length){
                    fields.forEach(field => field.classList.add(this.config.errorClass));
                }
                if (errorBlock) {
                    errorBlock.textContent = result.data.errors[k];
                } else {
                    if(result.data.aliases && result.data.aliases[k]){
                        SendIt?.Notify?.error(`${result.data.aliases[k]}: ${result.data.errors[k]}`);
                    }else{
                        SendIt?.Notify?.error(`${result.data.errors[k]}`);
                    }
                }
            }
        }
    }

    resetForm(root) {
        if (!document.dispatchEvent(new CustomEvent(this.events.reset, {
            bubbles: true,
            cancelable: true,
            detail: {
                target: root,
                Sending: this
            }
        }))) {
            return
        }

        if (root.tagName === 'FORM') {
            root.reset();
        } else {
            root.value = '';
        }
    }

    resetAllErrors(target){
        const root = target.closest(this.config.rootSelector);
        const errorBlocks = root?.querySelector(this.config.errorBlockSelector.replace('="${fieldName}"', ''));
        const fields = root?.querySelectorAll(`.${this.config.errorClass}`);
        if(fields && fields.length){
            fields.forEach(field => field.classList.remove(this.config.errorClass));
        }
        if(errorBlocks && errorBlocks.length){
            errorBlocks.forEach(errorBlock => errorBlock.textContent = '');
        }
    }

    resetError(fieldName, root) {
        const errorBlock = root?.querySelector(this.config.errorBlockSelector.replace('${fieldName}', fieldName));
        const fields = root?.querySelectorAll(`[name="${fieldName}"]`);
        if(fields.length){
            fields.forEach(field => field.classList.remove(this.config.errorClass));
        }
        if (errorBlock) errorBlock.textContent = '';
    }
}
