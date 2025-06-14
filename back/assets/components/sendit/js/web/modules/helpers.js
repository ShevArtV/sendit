export class Helpers {
  constructor(hub) {
    this.container = hub.container;
    this.appName = hub.container.appName;
    const serverConfig = window[`${this.appName}Config`] || {};
    this.config = {...hub.ModulesConfig[this.constructor.name], ...serverConfig};
  }

  dispatchEvent(name, options = {bubbles: true, cancelable: false}) {
    const event = new CustomEvent(name, options);
    return document.dispatchEvent(event);
  }

  getComponentCookie(name, className = this.config.cookieName) {
    let cookies = this.getCookie(className);
    cookies = cookies ? JSON.parse(cookies) : {};
    return cookies[name];
  }

  setComponentCookie(name, value, className = this.config.cookieName) {
    let cookies = this.getCookie(className);
    cookies = cookies ? JSON.parse(cookies) : {};
    cookies[name] = value;
    this.setCookie(className, JSON.stringify(cookies));

  }

  removeComponentCookie(name, className = this.config.cookieName) {
    let cookies = this.getCookie(className);
    cookies = cookies ? JSON.parse(cookies) : {};
    delete cookies[name];
    //console.log('rem', cookies);
    this.setCookie(className, JSON.stringify(cookies));
  }

  setCookie(name, value, options = {}) {
    options = {
      path: '/',
      ...options
    };

    if (options.expires instanceof Date) {
      options.expires = options.expires.toUTCString();
    }

    let updatedCookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);

    for (let optionKey in options) {
      updatedCookie += '; ' + optionKey;
      let optionValue = options[optionKey];
      if (optionValue !== true) {
        updatedCookie += '=' + optionValue;
      }
    }

    document.cookie = updatedCookie;
  }

  getCookie(name) {
    if (!name) {
      return undefined;
    }
    let matches = document.cookie.match(new RegExp(
      '(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
  }

  removeCookie(name) {
    this.setCookie(name, '', {
      'max-age': -1
    });
  }

  loadScript(path, callback, cssPath) {
    if (document.querySelector('script[src="' + path + '"]')) {
      callback(path, 'ok');
      return;
    }
    let done = false,
      scr = document.createElement('script');

    scr.onload = handleLoad;
    scr.onreadystatechange = handleReadyStateChange;
    scr.onerror = handleError;
    scr.src = path;
    document.body.appendChild(scr);

    function handleLoad() {
      if (!done) {
        if (cssPath) {
          let css = document.createElement('link');
          css.rel = 'stylesheet';
          css.href = cssPath;
          document.head.prepend(css);
        }
        done = true;
        callback(path, 'ok');
      }
    }

    function handleReadyStateChange() {
      let state;

      if (!done) {
        state = scr.readyState;
        if (state === 'complete') {
          handleLoad();
        }
      }
    }

    function handleError() {
      if (!done) {
        done = true;
        callback(path, 'error');
      }
    }
  }
}
