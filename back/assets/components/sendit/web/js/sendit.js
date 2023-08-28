(function () {
    'use strict';


    class SendIt {
        constructor() {
            if(window.SendIt) return window.SendIt;
            this.pathToConfigs = this.getSenditCookie('sijsconfigpath');
            this.events = {
                init: 'si:init',
            }

            this.config = {};
            this.loadConfigs().then(() => {
                window.SendIt = this;
                document.dispatchEvent(new CustomEvent(this.events.init, {}));
            });
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
        }

        getSenditCookie(name) {
            let cookies = this.getCookie('SendIt');
            cookies = cookies ? JSON.parse(cookies) : {};
            //console.log('get',cookies)
            return cookies[name];
        }

        setSenditCookie(name, value) {
            let cookies = this.getCookie('SendIt');
            cookies = cookies ? JSON.parse(cookies) : {};
            cookies[name] = value;
            //console.log('set',cookies)
            this.setCookie('SendIt', JSON.stringify(cookies));

        }

        removeSenditCookie(name) {
            let cookies = this.getCookie('SendIt');
            cookies = cookies ? JSON.parse(cookies) : {};
            delete cookies[name];
            //console.log('rem', cookies);
            this.setCookie('SendIt', JSON.stringify(cookies));
        }

        setCookie(name, value, options = {}) {
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

        getCookie(name) {
            let matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }

        removeCookie(name) {
            setCookie(name, "", {
                'max-age': -1
            })
        }
    }

    new SendIt();
})();