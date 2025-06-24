const module = await import('./core/main.js' + window['siConfig']['version']);
module.Main.getInstance('si').then(() => window.SendIt = module.Main._instance);
