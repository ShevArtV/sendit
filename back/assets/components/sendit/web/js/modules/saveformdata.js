export default class SaveFormData {
    constructor(config) {
        const defaults = {
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            resetEvent: 'si:send:reset'
        }
        this.config = Object.assign(defaults, config);
        this.events = {
            save: 'sf:save',
            set: 'sf:set',
            remove: 'sf:remove',
        }
        document.addEventListener('si:init', (e) => {
            this.initialize();
        });
    }

    initialize() {
        const roots = Array.from(document.querySelectorAll(this.config.rootSelector));
        if (roots.length) {
            for (let i in roots) {
                const root = roots[i];
                this.setValues(root);
            }
        }

        document.addEventListener('change', (e) => {
            if (e.target.closest(this.config.rootSelector) && ['select', 'input', 'textarea'].includes(e.target.tagName.toLowerCase())) {
                this.saveData(e.target);
            }
        });

        document.addEventListener(this.config.resetEvent, (e) => {
            this.removeValues(e.detail.target);
        })
    }

    saveData(field) {
        const root = field.closest(this.config.rootSelector);
        const savedData = localStorage.getItem(root.dataset[this.config.rootKey]) ? JSON.parse(localStorage.getItem(root.dataset[this.config.rootKey])) : {};
        let type = field.type;
        switch (field.tagName) {
            case 'TEXTAREA':
                type = 'text'
                break;
            case 'SELECT':
                type = 'select'
                break;
        }

        switch (type) {
            case 'password':
            case 'file':
                break;
            case 'radio':
            case 'checkbox':
                savedData[field.name] = savedData[field.name] || [];
                savedData[field.name].push({value: field.value, checked: field.checked});
                break;
            case 'select':
                savedData[field.name] = [];
                const options = field.querySelectorAll('option');
                options.forEach(option => {
                    savedData[field.name].push({value: option.value, selected: option.selected});
                });
                break;
            default:
                savedData[field.name] = field.value;
                break;
        }

        if (!document.dispatchEvent(new CustomEvent(this.events.save, {
            bubbles: true,
            cancelable: true,
            detail: {
                field: field,
                savedData: savedData,
                root: root,
                SaveFormData: this
            }
        }))) {
            return;
        }

        localStorage.setItem(root.dataset[this.config.rootKey], JSON.stringify(savedData));
    }

    setValues(root) {
        const savedData = JSON.parse(localStorage.getItem(root.dataset[this.config.rootKey]));
        const formFields = root.querySelectorAll('input,select,textarea');
        if (!savedData || !formFields) return;

        if (!document.dispatchEvent(new CustomEvent(this.events.set, {
            bubbles: true,
            cancelable: true,
            detail: {
                formFields: formFields,
                savedData: savedData,
                SaveFormData: this
            }
        }))) {
            return;
        }

        formFields.forEach(field => {
            let type = field.type;
            switch (field.tagName) {
                case 'TEXTAREA':
                    type = 'text'
                    break;
                case 'SELECT':
                    type = 'select'
                    break;
            }

            switch (type) {
                case 'password':
                case 'file':
                    break;
                case 'radio':
                case 'checkbox':
                    savedData[field.name] = savedData[field.name] || [];
                    for (let i = 0; i < savedData[field.name].length; i++) {
                        if (savedData[field.name][i].value === field.value) {
                            field.checked = savedData[field.name][i].checked;
                        }
                    }
                    break;
                case 'select':
                    savedData[field.name] = savedData[field.name] || [];
                    for (let i = 0; i < savedData[field.name].length; i++) {
                        const option = Array.from(field.options).filter(el => el.value === savedData[field.name][i].value);
                        option[0].selected = savedData[field.name][i].selected;
                    }
                    break;
                default:
                    if(!field.value){
                        field.value = savedData[field.name] || '';
                    }
                    break;
            }

            field.dispatchEvent(new Event('change', {bubbles: true, composed: true}))
        })
    }

    removeValues(root) {
        const formName = root.dataset[this.config.rootKey] || root.closest(this.config.rootSelector).dataset[this.config.rootKey];

        if (!document.dispatchEvent(new CustomEvent(this.events.remove, {
            bubbles: true,
            cancelable: true,
            detail: {
                formName: formName,
                root: root,
                SaveFormData: this
            }
        }))) {
            return;
        }

        localStorage.removeItem(formName);
    }
}