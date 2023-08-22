(function () {
    'use strict';


    class SendIt {

        constructor(pathToConfigs) {
            this.pathToConfigs = this.getCookie('sijsconfigpath');
            this.events = {
                init: 'si:init',
            }
            this.config = {};
            this.loadConfigs()
        }

        async loadConfigs() {
            await this.importModule(this.pathToConfigs, 'config');
            await this.initialize();
        }

        async importModule(pathToModule, property) {
            try {
                const {default: moduleName} = await import(pathToModule);
                if (property === "config") {
                    this[property] = moduleName();
                } else {
                    this[property] = new moduleName(this.config[property])
                }
            } catch (e) {
                throw new Error(e);
            }
        }

        async initialize() {
            for (let k in this.config) {
                await this.importModule(this.config[k]['pathToScripts'], k);
            }
            window.SendIt = this;
            document.dispatchEvent(new CustomEvent(this.events.init, {}));
        }

        getCookie(name) {
            let cookies = this.getSenditCookie('SendIt');
            cookies = cookies ? JSON.parse(cookies) : {};
            return cookies[name];
        }

        setCookie(name, value) {
            let cookies = this.getSenditCookie('SendIt');
            cookies = cookies ? JSON.parse(cookies) : {};
            cookies[name] = value;
            //console.log(cookies)
            this.setSenditCookie('SendIt', JSON.stringify(cookies));

        }

        setSenditCookie(name, value, options = {}) {
            options = {
                path: '/',
                ...options
            };

            if (options.expires instanceof Date) {
                options.expires = options.expires.toUTCString();
            }

            let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

            for (let optionKey in options) {
                updatedCookie += "; " + optionKey;
                let optionValue = options[optionKey];
                if (optionValue !== true) {
                    updatedCookie += "=" + optionValue;
                }
            }

            document.cookie = updatedCookie;
        }

        getSenditCookie(name) {
            let matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }

        removeCookie(name) {
            let cookies = this.getSenditCookie('SendIt');
            cookies = cookies ? JSON.parse(cookies) : {};
            delete cookies[name];
            this.setSenditCookie('SendIt', JSON.stringify(cookies));
        }

        static create() {
            new this();
        }
    }

    new SendIt();

})();