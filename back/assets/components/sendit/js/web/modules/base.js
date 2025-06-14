export class Base {
  constructor(hub) {
    this.hub = hub;
    this.container = hub.container;
    this.appName = hub.container.appName;
    const serverConfig = window[`${this.appName}Config`] || {};
    this.config = {...hub.ModulesConfig[this.constructor.name], ...serverConfig};

    this.initialize();
  }

  initialize(){}
}
