import {Base} from './base.js';

export class Notify extends Base{
  constructor(hub) {
    super(hub);
    this.events = {
      before: 'si:notify:before',
    };
  }

  async initialize(){
    this.hub.loadScript(this.config.jsPath, () => {},this.config.cssPath);
    await this.checkPropertyLoad(window, this.config.handlerClassName);
  }

  show(type, message, options = {}) {
    message = message ? message.trim() : '';
    if (window[this.config.handlerClassName] && Boolean(message)) {
      options = Object.assign(this.config.handlerOptions, {title: message}, options);
      options['type'] = type;

      this.hub.dispatchEvent(this.events.before, {
        bubbles: true,
        cancelable: false,
        detail: {
          options: options,
          Notify: this
        }
      });

      try {
        const toast = document.querySelector(this.config.typeSelectors[type]);
        if (toast && options.upd) {
          this.updateText(this.config.titleSelector, options['title']);
        } else {
          window[this.config.handlerClassName][options['type']](options);
        }
      } catch (e) {
        console.error(e, `Не найден метод ${options['type']} в классе ${this.config.handlerClassName}`);
      }
    }
  }

  success(message) {
    this.show('success', message, {upd: 0});
  }

  error(message) {
    this.show('error', message, {upd: 0});
  }

  info(message) {
    this.show('info', message, {upd: 0});
  }

  warning(message) {
    this.show('warning', message, {upd: 0});
  }

  close() {
    this.hub.loadScript(this.config.jsPath, () => {
      const toast = document.querySelector(this.config.toastSelector);
      if (!toast) return;
      window[this.config.handlerClassName].hide({}, toast);
    }, this.config.cssPath);
  }

  closeAll() {
    this.hub.loadScript(this.config.jsPath, () => {
      window[this.config.handlerClassName].destroy();
    }, this.config.cssPath);
  }

  updateText(selector, text) {
    const toastMsg = document.querySelector(selector);
    if (toastMsg) {
      toastMsg.textContent = text;
    }
  }

  setOptions(options) {
    window[this.config.handlerClassName].settings(options);
  }

  progressControl(action, options = {}) {
    const toast = document.querySelector(this.config.toastSelector);
    if (!toast) return;
    window[this.config.handlerClassName].progress(options, toast)[action]();
  }
}
