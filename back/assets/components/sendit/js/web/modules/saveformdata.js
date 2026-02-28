import {Base} from './base.js';

export class SaveFormData extends Base {
  initialize() {
    this.events = {
      save: 'sf:save',
      setBefore: 'sf:set:before',
      setAfter: 'sf:set:after',
      change: 'sf:change',
      remove: 'sf:remove',
    };

    const roots = Array.from(document.querySelectorAll(this.config.rootSelector));
    if (roots.length) {
      for (let i in roots) {
        if (typeof roots[i] !== 'object') continue;
        this.setValues(roots[i]);
      }
    }

    document.addEventListener('change', (e) => {
      if (e.target.closest(this.config.rootSelector) && ['select', 'input', 'textarea'].includes(e.target.tagName.toLowerCase())) {
        if (e.target.closest(this.config.noSaveSelector)) return;
        this.saveData(e.target);
      }
    });

    document.addEventListener(this.config.resetEvent, (e) => {
      this.removeValues(e.detail.target);
    });
  }

  saveData(field) {
    const root = field.closest(this.config.rootSelector);
    if (!root || !root.dataset[this.config.rootKey]) return;
    const savedData = localStorage.getItem(root.dataset[this.config.rootKey]) ? JSON.parse(localStorage.getItem(root.dataset[this.config.rootKey])) : {};
    let type = field.type;
    switch (field.tagName) {
    case 'TEXTAREA':
      type = 'text';
      break;
    case 'SELECT':
      type = 'select';
      break;
    }

    switch (type) {
    case 'password':
    case 'file':
      break;
    case 'radio':
    case 'checkbox':
      savedData[field.name] = this.saveFieldGroupValues(field.name, root);
      break;
    case 'select':
      savedData[field.name] = [];
      field.querySelectorAll('option').forEach(option => {
        savedData[field.name].push({value: option.value, selected: option.selected});
      });
      break;
    default:
      savedData[field.name] = field.value;
      break;
    }

    if (!this.hub.dispatchEvent(this.events.save, {
      bubbles: true,
      cancelable: true,
      detail: {
        field: field,
        savedData: savedData,
        root: root,
        SaveFormData: this
      }
    })) {
      return;
    }

    localStorage.setItem(root.dataset[this.config.rootKey], JSON.stringify(savedData));
  }

  saveFieldGroupValues(name, root){
    const fields = root.querySelectorAll(`input[name="${name}"]`);
    const output = [];
    fields.forEach(field => {
      output.push({value: field.value, checked: field.checked});
    });
    return output;
  }

  setValues(root) {
    const savedData = JSON.parse(localStorage.getItem(root.dataset[this.config.rootKey]));
    const formFields = root.querySelectorAll('input,select,textarea');
    if (!savedData || !formFields || root.closest(this.config.noSaveSelector)) return;

    if (!this.hub.dispatchEvent(this.events.setBefore, {
      bubbles: true,
      cancelable: true,
      detail: {
        root: root,
        formFields: formFields,
        savedData: savedData,
        SaveFormData: this
      }
    })) {
      return;
    }

    formFields.forEach(field => {
      let type = field.type;
      switch (field.tagName) {
      case 'TEXTAREA':
        type = 'text';
        break;
      case 'SELECT':
        type = 'select';
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
          if (option[0]) {
            option[0].selected = savedData[field.name][i].selected;
          }
        }
        break;
      default:
        if (!field.value && typeof savedData[field.name] !== 'undefined') {
          field.value = typeof savedData[field.name] !== 'object' ? savedData[field.name] : '';
        }
        break;
      }

      field.dispatchEvent(new CustomEvent(this.events.change, {
        bubbles: true,
        composed: true,
        cancelable: false,
        detail: {
          root: root,
          formFields: formFields,
          savedData: savedData,
          SaveFormData: this
        }
      }));
    });

    this.hub.dispatchEvent(this.events.setAfter, {
      bubbles: true,
      cancelable: false,
      detail: {
        root: root,
        formFields: formFields,
        savedData: savedData,
        SaveFormData: this
      }
    });
  }

  removeValues(root) {
    if (!root.closest(this.config.rootSelector)) return;
    const formName = root.dataset[this.config.rootKey] || root.closest(this.config.rootSelector).dataset[this.config.rootKey];
    if (!formName) return;

    if (!this.hub.dispatchEvent(this.events.remove, {
      bubbles: true,
      cancelable: true,
      detail: {
        formName: formName,
        root: root,
        SaveFormData: this
      }
    })) {
      return;
    }

    localStorage.removeItem(formName);
  }
}
