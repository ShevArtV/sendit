const module = await import('./core/main.js' + window['siConfig']['version']);
module.Main.getInstance('si').then(() => {
  window.SendIt = module.Main._instance;

  module.Main._instance.dispatchEvent('si:init', {
    bubbles: true,
    cancelable: true,
    detail: {
      object: module.Main._instance
    }
  });
});
