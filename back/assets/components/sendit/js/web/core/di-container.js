export class DIContainer {
  constructor(appName = '') {
    this.appName = appName;
    this.modules = {};
    this.libraries = {};
    this.externals = {};
  }

  registerModule(name, instance) {
    this.modules[name] = instance;
  }

  getModule(name) {
    if (!Object.prototype.hasOwnProperty.call(this.modules, name)) {
      throw new Error(`Module ${name} not registered`);
    }
    return this.modules[name];
  }

  registerLibrary(name, lib) {
    this.libraries[name] = lib;
  }

  getLibrary(name) {
    if (!this.libraries[name]) {
      throw new Error(`Library ${name} not loaded`);
    }
    return this.libraries[name];
  }

  registerExternal(name, ext) {
    this.externals[name] = ext;
  }

  getExternal(name) {
    if (!this.externals[name]) {
      throw new Error(`External ${name} not available`);
    }
    return this.externals[name];
  }
}
