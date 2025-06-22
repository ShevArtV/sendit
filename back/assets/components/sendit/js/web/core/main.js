import {DIContainer} from './di-container.js';

class Main {
  constructor(appName) {
    if (Main._instance) {
      Main._instance.initialize().then();
      return Main._instance;
    }
    this.appName = appName;
    this.container = new DIContainer(this.appName);
    this.initialized = false;
    this._servicesCache = new Map();
    Main._instance = this;
    return this.getProxy();
  }

  static async getInstance(appName) {
    if (!Main._instance) {
      Main._instance = new Main(appName);
      await Main._instance.initialize();
    } else if (!Main._instance.initialized) {
      await Main._instance.initialize();
    }
    return Main._instance;
  }

  getProxy() {
    return new Proxy(this, {
      get(target, prop) {
        if (prop in target) {
          return target[prop];
        }

        if (target.Helpers && prop in target.Helpers) {
          return target.Helpers[prop];
        }

        if (!target.Helpers) {
          try {
            target.Helpers = target.container.getModule('Helpers');
            if (target.Helpers && prop in target.Helpers) {
              return target.Helpers[prop];
            }
          } catch {
            console.warn('Helpers not found in container');
          }
        }

        if (!target._servicesCache.has(prop)) {
          try {
            const service = target.container.getModule(prop);
            target._servicesCache.set(prop, service);
            return service;
          } catch {
            return undefined;
          }
        }

        return target._servicesCache.get(prop);
      }
    });
  }


  async initialize() {
    if (this.initialized) return;

    const result = await import(`${window[`${this.appName}Config`]['modulesConfigPath']}${window[`${this.appName}Config`]['version']}`);
    this.ModulesConfig = result.ModulesConfig;

    for (const [moduleName, moduleConfig] of Object.entries(this.ModulesConfig)) {
      if (moduleConfig.forceLoad || document.querySelector(moduleConfig.rootSelector)) {
        await this._initModule(moduleName, moduleConfig);
      }
    }

    this.initialized = true;

    this.dispatchEvent('si:init', {
      bubbles: true,
      cancelable: true,
      detail: {
        object: this
      }
    });
  }

  async _initModule(name, {pathToScripts, className}) {
    try {
      const module = await import(`${pathToScripts}${window[`${this.appName}Config`]['version']}`);
      const ModuleClass = module[className];

      if (!ModuleClass) {
        throw new Error(`Class ${className} not found in ${pathToScripts}`);
      }

      const instance = new ModuleClass(this);
      this.container.registerModule(name, instance);
    } catch (error) {
      console.error(`Failed to initialize module ${name}:`, error);
      throw error;
    }
  }
}

Main._instance = null;

export {Main};
