import {Base} from './base.js';

export class OrderFields extends Base {
  initialize() {
    document.addEventListener(this.config.successEvent, (e) => {
      const fields = e.detail.result?.data?.orderFields;
      if (fields && typeof fields === 'object') {
        this.fill(fields);
      }
    });
  }

  fill(fields) {
    const form = document.querySelector(this.config.rootSelector);
    if (!form) return;
    for (const [key, value] of Object.entries(fields)) {
      form.querySelectorAll(`[name="${CSS.escape(key)}"]`).forEach(field => {
        if (['radio', 'checkbox'].includes(field.type)) {
          if (field.value !== String(value) || field.checked) return;
          field.checked = true;
        } else {
          if (field.value === String(value)) return;
          field.value = value;
        }
        field.dispatchEvent(new Event('change', {bubbles: true}));
      });
    }
  }
}
