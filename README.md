# SQLantern CMS extensions (Joomla, OpenCart)
Current version: v1.9.1β (public beta)\
License: [GNU General Public License v3.0](LICENSE)\
[Українською](README_uk.md)

This is the official repository for the SQLantern CMS extensions: SQLantern integrations with Joomla and OpenCart.\
It contains both ready-to-install ZIPs (installable in the website dashboard/administrator) and the source code (in [src](src)).\
SQLantern is open-source and free software, and so are the extensions in this repository.

These integrations are made by the authors of SQLantern and are the top recommended versions to install (if their limitations fit your needs): they are the most secure, easy to install, and easy to use.

SQLantern is a multi-panel and multi-screen database manager.\
Read more about it [in the official repository](https://github.com/nekto-kotik/sqlantern).\
Visit demo at [sqlantern.com](https://sqlantern.com/)

## This is the best version of SQLantern
### This version is the safest
SQLantern extensions are fully integrated with the CMS security and **do not have** a login/password form of their own (which could be brute-forced otherwise).\
Only website admins having rights to access the extension can use it (and only when the extension is enabled, of course).\
Use unexpected complex logins and passwords on your websites and change them regularly, enable two-factor authentication, and you'll be reasonably secure.\
(Do I even need to mention being extra careful on public internet access?)

### This version is the easiest to install
No need to upload any files over FTP, clone git, or unzip the archive on the server.\
Download the zip file and install it in the website back side just like any other extension.

### This version is the easiest to use
The extensions adds a new menu item for admins.\
No need to remember or write down the SQLantern URL - the link is right there, in the main menu.\
No need to keep the database login and password anywhere - the extension automatically uses credentials from the CMS configuration.\
No entering login and password, and no database choice from the list - only two clicks to open the database.

## Difference from standalone SQLantern
Unlike standalone SQLantern, where you type in login and password and are free to use any database available to that login, these extensions provide instant access to the website's database and to that database only.\
You are limited to the website's host and database and can't access any other host or database.\
You don't (and can't) enter login and password. Connection panels and databases panels are not available and they won't work even if you hack them in.

Each integration also has some individual limitations.

## How to prevent CMS session expiration
Enable the "Keep-alive" in the database panel (the one with the tables' list): ![](https://sqlantern.com/images/icon_keep_alive.png)\
This will automatically reconnect to the server periodically on the background and prolong the session when possible (in the same way both Joomla and OpenCart do that).\
Unfortunately, it only prolongs database-based sessions, but not "native" sessions.\
Also, this auto-prolongation can be interrupted and disabled by the browser itself if the SQLantern tab gets _discarded_ (if you don't know, very simply speaking, that's when the tab was "on the background" for a long enough time).

## SQLantern for Joomla
### Installation
Download `joomla-sqlantern-plugin.zip` right here on GitHub (from the files list or from the "Releases") or from [sqlantern.com](https://sqlantern.com/).\
**Joomla 3**: Install it in "Extensions > Manage > Install".\
**Joomla 4**: Install it in "System > Install > Extensions".\
The plugin is immediately enabled by default as soon as it is installed and is available to "Super Users" by default.\
You can now use the menu item ("System > SQLantern" in Joomla 3, just "SQLantern" in Joomla 4).

Joomla 3 menu item:\
![](https://sqlantern.com/images/en_cms_joomla3_menu_item.png)

Joomla 4 menu item:\
![](https://sqlantern.com/images/en_cms_joomla4_menu_item.png)

Find it in "Extensions > Plugins" (Joomla 3) or "System > Manage > Plugins" (Joomla 4) to change the user group or disable the plugin.

### Uninstallation
Uninstall it in "Extensions > Manage > Manage" (Joomla 3) or "System > Manage > Extensions" (Joomla 4), like any other plugin: find, check, click "Uninstall".

### Limitations of the Joomla integration
Only the "Database" session handler is supported.\
Only the `mysqli` database driver is supported (`public $dbtype = 'mysqli';` in "configuration.php", or "MySQLi"/"MySQL (PDO)" as `Database Type` in the Administrator).\
Only users from the _single user group_ can use the plugin - the one which is selected as having access to the plugin ("Super Users" by default).

### Joomla Compatibility
Joomla 3.5+\
Joomla 4.x

## SQLantern for OpenCart
### Installation
Download `sqlantern_opencart.ocmod.zip` right here on GitHub (from the files list or from the "Releases") or from [sqlantern.com](https://sqlantern.com/).\
Install it in "Extensions > Installer".\
Go to "System > Users > User Groups".\
Open your User Group (it's probably "Administrator"), check "extension/module/sqlantern_opencart" in **both** "Access Permission" and "Modify Permission", save changes.\
Go to "Extensions > Extensions", choose the "Modules" extension type.\
Find "SQLantern" in the list, click "Install" (the green "+" button).\
You can now use the "System > SQLantern" menu item.

![](https://sqlantern.com/images/en_cms_opencart_menu_item.png)

SQLantern module is _not a modification_, and cannot really conflict with anything (conflict with another module would be a phenomenon).\
There is no need to _refresh modifications_ to enable or disable the module.

### Uninstallation
**OpenCart 2**: There is no real uninstallation, unfortunately. You have to delete the files manually (refer to `src/sqlantern-opencart/upload` for the list of files to delete).\
**OpenCart 3**: Delete it in "Extensions > Installer" to uninstall, like most other extensions.

### Limitations of the OpenCart integration
Only the "mysqli" database driver is supported.\
Only "db" and native session engines are supported.

### OpenCart Compatibility
OpenCart/ocStore 2.3.x\
OpenCart/ocStore 3.x

## Copyright
SQLantern PHP code, CMS extensions PHP and JS code:\
(C) 2022, 2023 Misha Grafski aka nekto

SQLantern JS, HTML, CSS code:\
(C) 2022, 2023 Svitlana Militovska

Simplebar:\
Made by Adrien Denat from a fork by Jonathan Nicol\
https://github.com/Grsmto/simplebar