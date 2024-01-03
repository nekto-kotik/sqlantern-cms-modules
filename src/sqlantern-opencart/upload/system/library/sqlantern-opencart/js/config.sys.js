/*
This file is part of SQLantern CMS integration
Copyright (C) 2023 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

//import language from 'php/?language';	// `Cannot use import statement outside a module`
//console.log(language);

/*
I've had my day trying to load JSON synchronously, forcing the DOMContentLoaded not to fire until JSON was loaded, and everything failed.

JavaScript with type `module` is loaded asyncronously, even if it uses `await fetch()` inside. And it doesn't even really matter, because the module is executed after DOMContentLoaded.
Even inline `<script type='module'>` with text right in HTML is executed after DOMContentLoaded, which is absurd, but that's what I witnessed.

So, I ended up removing standard SQLantern's DOMContentLoaded and firing `panel.init()` after JSON is loaded and the Promise is resolved.
*/

Tab.prototype.listDB = () => {};

panel.listEvents = (tmp, drag) => {
	tmp.addEventListener('scroll', function(e) {
		const idx = app.curScreen;
		if (!app.scroll[idx]) return;
		if (app.scroll[idx][0] == true) {
			app.scroll[idx][1] = tmp.scrollLeft;
			document.body.classList.remove('scroll');
		}
	});
	tmp.querySelector('.add-new').addEventListener('click', () => {
		//new Tab(drag, {newConnect: true});
		
		const obj = {
			parent: tmp.querySelector('.tabs-list'),
			dragSelector: '.one-tab',
			scrollParent: tmp,
			connection: 'opencart',
			database: config.singleDatabaseName,
			newDB: true,
			quote: '`',
		};
		let newTab = new Tab(drag, obj);
		newTab.tab.querySelector('.db-name').textContent = obj.database;
		const obj2 = {
			body: newTab.requestListTables(),
			callback: (res) => newTab.listTables(res),
			forError: newTab.tab.querySelector('.content'),
		};
		newTab.request(obj2);
		
		app.scroll.push([]);
	});
};


window.removeEventListener('load', panel.init);


config.token = '';

let hash = location.hash.substr(1);	// value without the starting `#`
if (hash) {
	try {	// hash also happens to be the built-in discarded tab auto-save/auto-restore, ignore it in this case
		let jsonStr = atob(hash);	// `atob` is base64 decode ("ASCII to binary"), `btoa` is base64 encode ("binary to ASCII")
		config.token = JSON.parse(jsonStr).token;
		//location.hash = '';	// this leaves `#` in the URL, but I don't care about it for now
		history.replaceState('', '', location.pathname);
		//console.log(`token: '${config.token}'`);
	}
	catch (e) {
		// don't do anything, just don't fall into fatal error
	}
}


config.settingsLoaded = false;
config.windowLoaded = false;

app.default_full_texts = false;	// `listConnections` is never run on this version, ever

window.addEventListener(
	'load',
	() => {
		if (config.settingsLoaded) {	// who knows, there might be a stupid delay somewhere...
			//console.log('settingsLoaded first');
			panel.init();
		}
		else {
			config.windowLoaded = true;
		}
	}
);

let errorText;
fetch(`php/?cms_settings&opencart_token=${config.token}`)
	.then(res => res.text())
	.then(text => {
		errorText = text;
		return JSON.parse(text);
	})
	.then(
		(json) => {
			//console.log(json);
			config.language = json.language;
			config.singleDatabaseName = json.database;
			
			const title = document.querySelector('title').textContent.split('(');
			document.querySelector('title').textContent = `${title[0]} (${json.version})`;
			app.version = json.version;
			
			if (config.windowLoaded) {	// who knows, there might be a stupid delay somewhere...
				//console.log('windowLoaded first');
				panel.init();
			}
			else {
				config.settingsLoaded = true;
			}
		}
	)
	.catch(err => {
		// `panel.init` runs additional network request, but it'll get the very same access denied error, and the error will be displayed very correctly
		panel.init();
	})
;