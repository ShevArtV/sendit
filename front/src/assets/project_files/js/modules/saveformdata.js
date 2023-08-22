export default class SaveFormData {
    constructor(config) {
        const defaults = {
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            sendEvent: 'si:complete'
        }
        this.config = Object.assign(defaults, config);

        this.initialize();
    }

    initialize(){
        document.addEventListener('change', (e) => {
            if(e.target.closest(this.config.rootSelector) && ['select','input','textarea'].includes(e.target.tagName.toLowerCase())){
                this.saveData(e.target);
            }
        })
    }

    saveData(field){
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
            case 'text':
            case 'tel':
            case 'email':
                savedData[field.name] = field.value;
                break;
            case 'radio':
            case 'checkbox':
                savedData[field.name] = savedData[field.name] || [];
                savedData[field.name].push({value: field.value, checked: field.checked});
                break;
        }
        localStorage.setItem(root.dataset[this.config.rootKey], JSON.stringify(savedData));
    }

    setValues(root){
        const savedData = JSON.parse(localStorage.getItem(root.dataset[this.config.rootKey]));
        const formFields = root.querySelectorAll('input,select,textarea');

        if(!savedData || !formFields) return;

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
                case 'text':
                case 'tel':
                case 'email':
                    field.value = savedData[field.name];
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
            }
        })
    }

    removeValues(root){
        localStorage.removeItem(root.dataset[this.config.rootKey]);
    }
}