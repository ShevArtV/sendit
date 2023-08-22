import returnConfigs from "./sendit.inc.js";

(function () {
    'use strict';


    class SendIt {

        constructor(pathToConfigs) {
            this.pathToConfigs = pathToConfigs;
            this.events = {
                init: 'si:init',
            }
            this.loadConfigs()
        }

        async loadConfigs() {
            await this.importModule(this.pathToConfigs, 'config');
            await this.initialize();
        }

        async importModule(pathToModule, property) {
            try {
                const {default: moduleName} = await import(pathToModule);
                if (property === "moduleConfigs") {
                    this[property] = moduleName();
                } else {
                    this[property] = new moduleName({})
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

        static create(pathToConfigs) {
          /*  const defaults = {
                pathToConfigs: './sendit.inc.js',
                modules: {QuizForm: './quizform.js'}
            }
            config = Object.assign(defaults, config);*/
            new this(pathToConfigs);
        }
    }

    SendIt.create('./sendit.inc.js');

})();