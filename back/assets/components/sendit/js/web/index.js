const module = await import('./core/main.js' + window['siConfig']['version']);
window.SendIt = module.Main.getInstance('si');
