<?php
/*
This file is part of SQLantern Database Manager
Copyright (C) 2022, 2023, 2024, 2025 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern
https://sqlantern.com/

SQLantern is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
*/

define("SQLANTERN_VERSION", "1.9.14 beta");	// 25-03-20
/*
Beware that DB modules have their own separate versions!
*/

$configName = __DIR__ . "/config.sys.php";
if (file_exists($configName)) {
	require_once $configName;
}

$defaults = [
	/*
	DON'T CHANGE THESE VALUES IN THIS FILE
	
	If you need to change any of the defaults below, don't change them here, but use one of the following options:
	- Set an environment variable for each value you want to change (with the same name as the setting name).
	- Create a file `config.sys.php` in the same directory as this file, and define different values there.
	
	Setting environment variables is recommended, because more often than not it doesn't require creating a new file (`.htaccess` is usually already present on some level), and it's also the only practical way to configure the single-file version and Dockerized version.
	
	An example of setting values in environment variables via `.htaccess`:
	```
	SetEnv SQLANTERN_DEFAULT_HOST "127.0.0.1"
	SetEnv SQLANTERN_MULTIHOST "true"
	```
	
	An example of setting values in `config.sys.php`:
	```
	define("SQLANTERN_DEFAULT_HOST", "127.0.0.1");
	define("SQLANTERN_MULTIHOST", true);
	```
	
	`config.sys.php` is not shipped with SQLantern, which means updating SQLantern DOESN'T erase/change your configuration.
	*/
	
	"SQLANTERN_DEFAULT_HOST" => "localhost",
	/*
	Be aware that it's "localhost" by default and not "127.0.0.1".
	The host can be local or remote, there are no limitations.
	*/
	
	"SQLANTERN_DEFAULT_PORT" => 3306,
	/*
	Which port to use when port is not used in `login`.
	Use `5432` to connect to PostgreSQL by default.
	Or set a non-standard value here if needed (which also needs a custom `SQLANTERN_PORT_{port}` value).
	*/
	
	"SQLANTERN_PORT_3306" => "mysqli",
	"SQLANTERN_PORT_5432" => "pgsql",
	/*
	Standard ports and drivers to use when connecting via them.
	The project is initially shipped with `mysqli` and `pgsql` drivers.
	*/
	
	// drivers in development:
	"SQLANTERN_PORT_1433" => "sqlsrv",
	"SQLANTERN_PORT_SQLITE" => "sqlite3",
	// I think I'll move them exclusively to `$defaultsV2` sooner than they are finished...
	
	"SQLANTERN_EXPORT_DB_DATE_SUFFIX" => "_ymd_Hi",
	/*
	A format for the date and time of export, which is added to the name of the file when exporting a database. (Extension is always ".sql".)
	The default value of "_ymd_Hi" will add "_YYMMDD_HHMM": e.g. "Chinook_241106_2059.sql", "sqlantern_241231_2359.sql".
	See formatting options @ https://www.php.net/manual/en/datetime.format.php
	An empty value will not add anything, only the database name will be used.
	*/
	
	"SQLANTERN_MYSQLI_CHARSET" => "UTF8MB4",
	"SQLANTERN_POSTGRES_CHARSET" => "UTF8",
	// ??? . . . do I even need to change the charset to anything else any time at all, ever ???
	// I don't have any good ideas how to make it convenient in a per-table or at least in a per-database way, if I have to (I mean, in the configuration)
	
	"SQLANTERN_SHOW_CONNECTION_ERROR" => false,
	/*
	* * *  Only works with `mysqli` driver currently * * *
	SQLantern masks all connection errors behind a generic "CONNECTION FAILED", for safety reasons.
	However, it is sometimes desireable to see the real error.
	This setting enables real connection errors when set to `true`.
	*/
	
	"SQLANTERN_USE_SSL" => false,
	/*
	* * *  Only works with `mysqli` driver currently * * *
	Enable/disable SSL encryption.
	*/
	
	"SQLANTERN_TRUST_SSL" => false,
	/*
	* * *  Only works with `mysqli` driver currently * * *
	Set this setting to `true` to trust the SSL blindly (disable SSL validation).
	Reason: Quite often we simply don't have enough rights and cannot install the SSL on a server with SQLantern and/or we know that the connection is secure enough for our needs.
	*/
	
	"SQLANTERN_RUN_AFTER_CONNECT" => json_encode([
		"mysqli" => [
			"SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))",
			// removing `ONLY_FULL_GROUP_BY` is REQUIRED for the built-in table indexes request to work at all
			// "MySQL anyway removes unwanted commas from the record."
			//"SET workload=olap",	// PlanetScale: allow `SELECT`s with over 100,000+ rows (export dump, usually), otherwise an error happens: "rpc error: code = Aborted desc = Row count exceeded 100000"; other quirks must be taken into account to work with PlanetScale, e.g. it requires SSL (and `real_connect` as a result), but this one is also very important
		],
		"pgsql" => [
		],
	]),
	/*
	Queries to run immediately after connection (for e.g. desired session variables, like `group_concat_max_len`).
	Every database module has it's own set of queries, as the typical queries here are very database-system-specific.
	`json_encode` is used for PHP 5.6 compatibility, see detailed comment about `SQLANTERN_INCOMING_DATA` below.
	
	!!! "SQLANTERN_RUN_AFTER_CONNECT" WILL BE DEPRECATED AND OBSOLETE IN 1.9.15 !!!
	!!! ($defaultsV2 and $config will be used instead) !!!
	
	*/
	
	"SQLANTERN_NUMBER_FORMAT" => "builtInNumberFormat",
	/*
	The name of the function, which you can redefine to use your own (which can be written in `config.sys.php`).
	Used only for number of rows, number of pages, and number of unique values (in MariaDB/MySQL indexes list)!
	The function itself cannot be written right here as an anonymous function, only a function name, because constants can only store "simple values", unfortunately...
	NOTE . . . `SQLANTERN_NUMBER_FORMAT` will be removed in Version 3, because the number format will be customizable in config and in visual front side settings !!!
	*/
	
	"SQLANTERN_BYTES_FORMAT" => "builtInBytesFormat",
	/*
	The same as above, but for bytes.
	Only used for databases' sizes and tables' sizes!
	*/
	
	"SQLANTERN_FAST_TABLE_ROWS" => true,
	/*
	* * *  Only works with `mysqli` driver * * *
	Defines which logic to use to get the number of rows in each table (for the lists of tables of a database, the "tables panel").
	When `false`, a slow logic is used: `SELECT COUNT(*)` is run for each table, giving the accurate number of rows, but it's almost always VERY slow.
	When `true`, the fast logic is used: the number of rows is taken from "information_schema.tables" (which is exact for MyISAM, but often extremely wrong for InnoDB), and an additional check is run for the small tables to get rid of false-zero and false-non-zero situations. `SELECT COUNT(*)` on small tables is fast, so it's a mix of sources ("information_schema.tables" and "COUNT(*)").
	The default `true` is really fast and usually precise _enough_.
	Read comments in `function sqlListTables` in `php-mysqli.php` for very detailed info and rationale.
	*/
	
	"SQLANTERN_KEYS_LABELS" => json_encode([	// JSON for PHP 5.6 compabitility!
		"primary" => "PRI",
		"unique" => "UNI",
		"single" => "KEY",
		"multi" => "MUL",
	]),
	/*
	Labels for the keys in the columns list (in structure).
	- `primary` and `unique` are self-descriptive
	- `single` is applied if the column is only used as a single-column index
	- `multi` is applied if a column is used in a multi-column index OR in multiple indexes
	If an index is both primary and unique, only "primary" label is displayed.
	
	I'm almost following MySQL logic here, because after thinking about it I decided this info is not only useful to see in structure, but the idea of keeping those labels short and take less space is also good. My only extension of MySQL here is `single`.
	The labels are configurable for those who are annoyed to see MySQL-like key labels in other database systems.
	*/
	
	"SQLANTERN_INDEX_COLUMNS_CONCATENATOR" => " + ",
	/*
	Columns of an index are combined with this separator.
	E.g. if the concatenator is " + " and the index "idx_term_weight" combines data from columns "weight" and "term_id", the "columns" will be displayed as "weight + term_id".
	If the concatenator is ", ", columns of the same index will be displayed as "weight, term_id".
	I believe it is important enough to be configurable.
	
	" + " is SQLantern style.
	", " is psql and Adminer style.
	"," (no space) is pgAdmin style.
	Using "\n" is possible (phpMyAdmin style), but requires additional CSS tuning to look even remotely acceptable.
	
	<del>Not all PostgreSQL indexes are displayed correctly as of now (indexes with `INCLUDE`), and that might never be solved. No promises for now. I'm sorry.</del> It WILL be solved, hopefully in 1.9.15.
	*/
	
	"SQLANTERN_MULTIHOST" => false,
	/*
	If `false`: will only connect to one (default) host (as set by `SQLANTERN_DEFAULT_HOST`; beware that it can be _any_ host, including remote).
	If `true`: will try to connect to any host.
	Default is `false`, because otherwise every copy would be easily used as a proxy for brute force and/or DDOS attacks on other servers out of the box, which is undesired.
	Note that is has nothing to do with default host being local or remote, the default host can be remote well and fine with `SQLANTERN_MULTIHOST` staying `false`.
	It only limits connections to one default host, or allows it to any host (local or remote).
	*/
	
	"SQLANTERN_ALLOW_EMPTY_PASSWORDS" => false,	// please, be responsible and enable empty passwords only if you're absolutely secure on an offline or IP/password-protected location
	
	"SQLANTERN_DEFAULT_SHORTEN" => true,
	/*
	Shorten long values by default or not (there is a toggle for that above each query anyway, and also a visual front side setting, but this sets the default behaviour).
	Note that values are shortened on the server, this is why it is a server-side option.
	
	This setting will be fully moved to the front side at some point (in Version 3, most probably).
	*/
	
	"SQLANTERN_SHORTENED_LENGTH" => 200,
	/*
	The length which long values are shortened to (number of characters).
	
	This setting will be fully moved to the front side at some point (in Version 3, most probably).
	*/
	
	"SQLANTERN_SESSION_NAME" => "SQLANTERN_SESS_ID",
	/*
	It may sound far stretched, but configuring different `SQLANTERN_SESSION_NAME` allows using multiple instances of SQLantern in subdirectories on the same domain (with e.g. different default drivers, host limitations, etc), with possibility to separate access to them by IP, for example (on the web server level).
	<del>Official README contains some examples.</del> (no, it doesn't; maybe it will one day)
	*/
	
	"SQLANTERN_COOKIE_NAME" => "sqlantern_client",
	/*
	Cookie name to store logins and passwords.
	This is security-related: server-side SESSION contains cryptographic keys, while client-side COOKIE contains encrypted logins and passwords, thus leaking any one side doesn't compromise your logins or passwords.
	Encryption keys are separate and random for every login and every password (with new keys generated each time a connection is added).
	*/
	
	"SQLANTERN_DEDUPLICATE_COLUMNS" => true,
	/*
	Deduplicate columns which have the same name, see function `deduplicateColumnNames` further below in this file for details.
	*/
	
	"SQLANTERN_CIPHER_METHOD" => "aes-256-cbc",	// encryption method to use for logins and passwords protection
	"SQLANTERN_CIPHER_KEY_LENGTH" => 32,	// encryption key length, in bytes (32 bytes = 256 bits)
	
	"SQLANTERN_POSTGRES_CONNECTION_DATABASE" => "postgres",
	/*
	PostgreSQL-specific: the initial connection database immediately after login, when database is not selected yet (a required field!)
	
	<del>"SQLANTERN_POSTGRES_CONNECTION_DATABASE" WILL BE DEPRECATED AND OBSOLETE IN 1.9.15 beta</del>
	<del>($defaultsV2 and $config will be used instead)</del>
	It probably won't be deprecated to make life easier for a simple one-server use. `$config` will expand it and allow maximum multi-server flexibility, but `SQLANTERN_POSTGRES_CONNECTION_DATABASE` will still probably set the default value. `$defaultsV2` should also be used somehow, I'll think about it.
	*/
	
	"SQLANTERN_INCOMING_DATA" =>
		$_POST ?	// POST priority
		json_encode($_POST) :
		(
			$_GET ?	// GET (only for EventSource progress monitor, because EventSource is only GET...)
			json_encode($_GET) :
			file_get_contents("php://input")	// standard fetch requests
		)
	,	// a workaround-override for integrations with enforced connection/database limitation; this won't be documented, but you can see how it's used in the official OpenCart and Joomla integrations
	// FIXME . . . I'm a fool, I can and should just manipulate `$_POST` in my integrations
	/*
	I initially had `json_decode` right here in the array, reading `php://input`, but had to move it lower in the code, because:
	Although PHP 5.6 allowed defining array constants (http://php.net/migration56.new-features), they could only be defined with `const`, not with `define` (which is used here, below), and only PHP 7.0 allowed `define` constants with array values (see http://php.net/manual/en/migration70.new-features.php).
	And I want to keep PHP 5.6 compatibility for as long as I can, it's a really important feature to me, hence the change.
	*/
	
	"SQLANTERN_FALLBACK_LANGUAGE" => "en",
	/*
	There is only a handful of scenarios when this option comes into play, basically when front-end didn't send any language (not even a real scenario, only possible if that's a hack or a human error), and at the same time there is no fitting browser-sent default language (which is absolutely real, of course).
	Even so, I still think the fallback language must be a configurable server-side parameter for flexibility sake, so here it is.
	*/
	
	"SQLANTERN_DATA_TOO_BIG" => 4.5 * 1048576,
	/*
	Maximum data to return, in bytes.
	THIS IS A TEMPORARY OPTION!!!
	4.5 MiB by default, which I hope is more or less proper.
	
	Essentially, there are two thresholds to care about:
	1. When the amount of data will break SQLantern session save and auto-save.
	2. When the amound of data will cause browser tab to crash with the "Out of memory" error.
	
	Number 1 might become irrelevant when we implement storing SQLantern sessions in multiple SessionStorage keys (depending on browsers' behaviour upon trying to save more than 5MB in total, which we haven't tested yet).
	
	Number 2 must be found by trial and error.
	
	Stage 1 of the fix only has the temporary `SQLANTERN_DATA_TOO_BIG` option.
	Stage 2 will have a dialog to continue anyway if the user chooses to and possibly two internal memory options, but I don't really know yet.
	*/
	
	"SQLANTERN_SERVER_SIDE_BACKUPS_ENABLED" => false,
	/*
	Enable/disable server-side backup and restore of the browser's LocalStorage (Sessions, Saved queries, Notepad and browser-side settings). Set to `true` to enable.
	If disabled, only client-side backup/restore is available (download/upload a `json` file).
	It's a potentially dangerous feature, thus it's disabled by default.
	Even when it's enabled, the user MUST also have a valid database connection to use it anyway, so it's only critically dangerous on instances with enabled remote connections.
	
	The user-side backups are plain text and are _completely unsecure_.
	But the server-side data is password-encrypted and is reasonably safe.
	If you forgot the password, you'll have to brute-force it, there is no other way to decrypt the data.
	
	Know that the database passwords are never saved anywhere, they are even encrypted in the $_SESSION and are only decrypted to the RAM for very short periods of time (they are even erased from RAM after connecting to the database).
	However, you should expect the LocalStorage backups to contain your login and host, and leaking them is also a security issue (not critical, but still).
	*/
	
	"SQLANTERN_SERVER_SIDE_BACKUPS_FILE" => __DIR__ . "/.sqlantern-backup.php",
	/*
	The full name (with full path) of the file to store the backup of the browser's LocalStorage on the server.
	The file is a PHP file (containing an array, no real program code) and should have `.php` extension to not accidentally make the content publicly visible.
	Multiple LocalStorage backups can be saved in the same file under different passwords (passwords must be different to save different LocalStorages).
	Absolute path should be used. Relative path should also work, but it was not tested.
	The backups are password-encrypted, making the default path reasonably secure even if the file is maliciously downloaded, but an unreachable path (outside of any "public html") is highly recommended for better security.
	*/
	
	"SQLANTERN_SERVER_SIDE_BACKUPS_RESTORE_WRONG_PASSWORD_TIMEOUT" => 5,
	/*
	Timeout in seconds when an attempt to restore a server-side LocalStorage backup _with a wrong password_ is made (if the server-side backups are enabled).
	It is a measure of primitive brute-force mitigation.
	
	IF THE VALUE IS BELOW 5, THE TIMEOUT IS 5 SECONDS ANYWAY.
	The only way to disable the timeout or make it less than 5 seconds is to change the PHP code in this file further below.
	
	The timeout locks the entire session completely and thus doesn't allow an easy multi-thread brute-force.
	It can be very annoying if you legitimately forgot your password, but it's an important safety measure.
	If you've lost/forgotten your password, you can work with the data in the backup file manually to brute-force it.
	*/
	
	"SQLANTERN_DEVMODE" => false,
	/*
	Development mode for internal tests.
	*/
];

foreach ($defaults as $name => $value) {
	/*
	- The global `$config` variable is top priority and can override everything, but it is designed to be per-host and is handled very differently further below. It is also limited to SOME settings, not all of them.
	- `config.sys.php` overrides environment variables.
	- Environment variables override the default values above.
	*/
	
	$envValue = getenv($name);
	if ($envValue !== false) {
		//precho(["genenv", "name" => $name, "value" => $envValue, ]);
		if (gettype($value) == "boolean") {
			// Allow 1/true/yes/y or 0/false/no/n for boolean settings.
			if (in_array(strtolower($envValue), ["1", "true", "yes", "y"])) {
				$value = true;
			}
			elseif (in_array(strtolower($envValue), ["0", "false", "no", "n"])) {
				$value = false;
			}
			// Other values for boolean settings are silently ignored without any warning or error!
		}
		else {
			$value = $envValue;
		}
	}
	
	/*
	Backwards compatibility: accept both `SQL_` and `SQLANTERN_` variables prefixes.
	They were changed from `SQL_` to `SQLANTERN_` in 1.9.13, but I want to keep the older existing configured copies working and not force anyone to change anything.
	*/
	$parts = explode("_", $name);
	$parts[0] = "SQLANTERN";	// no matter what the prefix was, it'll become `SQLANTERN_`
	$name = implode("_", $parts);
	
	/*
	Everything defined with `SQL_` must be copied to `SQLANTERN_` as well.
	*/
	$parts[0] = "SQL";
	$oldPrefixName = implode("_", $parts);	// starts with `SQL_`
	if (defined($oldPrefixName)) {
		define($name, constant($oldPrefixName));
	}
	
	if (!defined($name)) {
		define($name, $value);
	}
}


/*

	WORK IN PROGRESS >>

*/

/*
Some thoughts on the future of per-host and potentially per-host-and-port values here.

SQL_DEFAULT_PORT per host would be nice, actually
SQL_MYSQLI_CHARSET and SQL_POSTGRES_CHARSET must be per host, but I should dive into it to the end one day to understand if I need this variable at all -- don't do it for now
SQL_RUN_AFTER_CONNECT must be per host, and maybe the pair of host/port would be the best (per driver?)
SQL_FAST_TABLE_ROWS must be per host/port, because SQLite has no fast option at all, and slow option in mysqli = piece of shhh
SQL_ALLOW_EMPTY_PASSWORDS should be per host/port or driver, because SQLite
SQL_POSTGRES_CONNECTION_DATABASE must definitely be per host/port/driver

I'm thinking of a structure with wildcards, like:
"*" => [
	"SQL_POSTGRES_CONNECTION_DATABASE" => "postgres",
],
"localhost" => [
	"SQL_DEFAULT_PORT" => 3306,
],
"192.168.1.112" => [
	"SQL_DEFAULT_PORT" => 5432,
	"SQL_POSTGRES_CONNECTION_DATABASE" => "template0",
],
"*:sqlite" => [
	"SQL_ALLOW_EMPTY_PASSWORDS" => true,
	"SQL_FAST_TABLE_ROWS" => false,
]

Multiple wildcards would be nice, too:
"*:5432,*:55432" => [
	"SQL_POSTGRES_CONNECTION_DATABASE" => "template0",
]

I actually want:
- global settings: `*`
- global per-driver settings: `*:mysqli`, `*:pgsql`
- global per-port settings: `*:3306`, `*:5432`
- per-host global settings: `hostname` or `hostname:*`
- per-host per-driver setting
- per-host per-port settings: `hostname:3306`, `hostname:5432`

What would be a priority?
I think (descending) global, then global-driver, then global-port, then host, then host-driver, then host-port
So, host-port will override everything.
And global-port will override global-driver.
That'll make possible e.g. one `SQL_RUN_AFTER_CONNECT` for `*:mysqli`, but another for an alternative port like `*:33306`, without specifying `33306` anywhere else. I hope I'll understand this thought later.

Hell knows how to make it without overcomplicating the syntax :-(

> Later thought:
I'm thinking about one-line configurations now. E.g.:
"*" => "driver=mysqli; charset=utf8mb4;",
	or even "*" => "port=3306;"
	"*:mysqli" => "charset=utf8mb4;",
	"*:3306" => "driver=mysqli",
"sqlite" => "driver=sqlite3; allow_empty_passwords=yes; fast_table_rows=no;",
	`sqlite` should actually have `fast_table_rows` as "yes", because there is NO fast option, only the slow version.
"*:5432" => "driver=pgsql; display_database_sizes=yes; connection_database=pg;"
(Also, allow 1/true/yes/y or 0/false/no/n for boolean.)
And only make it an array and add a second argument if need to run non-standard post-connections queries.
Default values are applied to all non-used options.
Default values can be overriden in "*".

> Documentation
The configuration is an array. The keys of this array define _which destination must have custom settings_ and the values define _the settings_.
Possible _keys_ for the configuration array are:
"*" - global settings - useful to globally override standard out-of-the-box SQLantern settings (like "multihost")
"{IP}" or "{hostname}" - setting for a IP or hostname, e.g. "192.168.1.99", "sqlantern.com" - it must be used in the login exactly as written in the configuration (i.e. hostnames are not looked up and compared to IP addresses) - useful to .
"*:{driver}" - global settings for a driver, e.g. "*:mysqli", "*:pgsql", "*:sqlite3"
"*:{port}" - global setting for a port, e.g. "*:3306", "*:5432", "*:sqlite" - useful when the same driver is used on multiple ports
"{IP}:{driver}" or "{hostname}:{driver}" - settings for a driver on a specific IP or hostname, e.g. "192.168.1.99:mysqli", "docker:pgsql" - useful for different configuration on different servers - configures the driver on an IP or host no matter the ports (i.e. if "mysqli" is used on ports 3306 and 33306, both ports will be affected)
"{IP}:{port}" or "{hostname}:{port}" - settings for a port on a specific IP or hostname, e.g. "192.168.1.99:3306", "192.168.199:33306" - useful to configure different values for the same driver on different ports (otherwise setting the values on driver-level is more reasonable)

Partial wildcards _are not recognized_ - values like "192.168.1.*", "*.sqlantern.com", "192.168.1.99:33*" do not work.
It must be either "*" or exact value.

> Examples
You need to allow "multihost" globally:
...
You need to allow empty passwords only for SQLite driver:
...
You need to use MariaDB/MySQL driver on ports 3306 and 33306 globally, and PostgreSQL driver on ports 5432 and 55432 only when connecting to 192.168.1.99:
...

*/

$defaultsV2 = [
	//"*" => "",
	"*:mysqli" => [		// no matter the port!
		"run_after_connect" => [
			"SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))",
			// removing `ONLY_FULL_GROUP_BY` is REQUIRED for the built-in table indexes request to work at all
			// Also: "MySQL anyway removes unwanted commas from the record."
		],
	],
	"*:1433" => "driver=sqlsrv; ",
	"*:3306" => "driver=mysqli; ",
	"*:5432" => "driver=pgsql; postgres_connection_database=postgres; ",
	"*:sqlite" => "driver=sqlite3; ",
];

/*
I believe the function finally works as intended already, but it's a niche commodity and not an urgent importance, so I'm not using it everywhere yet and not documenting it yet (as of version 1.9.13).
I think it will be documented with the SQLite driver release, because it'll start making more sense then (to allow empty password per-driver or per-port, disable `fast_table_rows` when practical; maybe I'll also add forced fake password for SQLite).
Although mixing connections with and without SSL, and requiring custom additional queries-after-connect is absolutely real and practical. Still a bit niche though.
*/
function getSetting( $setting, $host = "" ) {
	global $config, $defaultsV2, $sys;
	
	// `$defaultsV2` will contain MariaDB/MySQL starting queries from now on (among other things).
	
	/*
	The original initial constants-only configuration is split into 4 branches now:
	- constants-only values (the lowest level) - they are still working and are going to work in the future anyway,
	- all constants can be overriden by environment variables,
	- some of them can be overriden by the user in the front side (and stored in SESSION),
	- some others can be overriden on the server side per-host/per-driver/per-port in a new different way (way more flexible)
	*/
	
	// SESSION holds some safe front-side settings, like "display databases' sizes"
	// also, they MUST always exist in the SESSION, there are no additional checks about it
	if (in_array($setting, ["display_databases_sizes", "sizes_flexible_units", ])) {
		//precho($_SESSION["config"]);
		return $_SESSION["config"][$setting];
	}
	
	
	
	
	
	// IS THIS CORRECT ???
	
	if (array_key_exists("compiledConfig", $sys)) {	// cannot store those in SESSION, because I want to update it on every change, not on SESSION restart, and the "change" can happen in a `config.sys.php` OR in an environment variable
		return $sys["compiledConfig"][$setting];	// compile once per network request
	}
	
	// IS THIS CORRECT ???
	
	
	
	
	
	
	/*
	So, the code I initially wrote was extremely complicated and still didn't get me what I want.
	I want to try the reverse logic - known which _parameters_ exist in which "keys".
	Say, "allow_empty_passwords" exists in "localhost" and "*:sqlite".
	And when the parameter is requested, I check all the priorities regarding this parameter, from bottom to top.
	That should be both a bit simpler code and also solve my problem.
	*/
	
	if (!array_key_exists("compiledConfig", $sys)) {
		
		$sys["compiledConfig"] = [
			"default_host" => ["default" => SQLANTERN_DEFAULT_HOST],
			"multihost" => ["default" => SQLANTERN_MULTIHOST],
			"default_port" => ["default" => SQLANTERN_DEFAULT_PORT],
			"fast_table_rows" => ["default" => SQLANTERN_FAST_TABLE_ROWS],
			"allow_empty_passwords" => ["default" => SQLANTERN_ALLOW_EMPTY_PASSWORDS],
			"postgres_connection_database" => ["default" => SQLANTERN_POSTGRES_CONNECTION_DATABASE],
			"show_connection_error" => ["default" => SQLANTERN_SHOW_CONNECTION_ERROR],
			"use_ssl" => ["default" => SQLANTERN_USE_SSL],
			"trust_ssl" => ["default" => SQLANTERN_TRUST_SSL],
			"sqlite_db_directory" => ["default" => ""],	// would "/var/db/sqlite/" be a good option? is there a standard?
			"run_after_connect" => ["default" => []],
		];
		
		// create params-to-hosts/ports relations from the system setting (`$defaultsV2`) and the user's settings (`$config`)
		
		$booleanSettings = [
			"multihost",
			"fast_table_rows",
			"allow_empty_passwords",
		];
		
		$readConfig = function($arr) use ($booleanSettings) {
			$values = [];
			//precho(["arr" => $arr, ]);
			if (!is_array($arr)) {
				$arr = [$arr];
			}
			// read and remove expected associative keys (only "run_after_connect" for now, but there might be more in the future)
			if (array_key_exists("run_after_connect", $arr)) {
				// NOTE . . . `run_after_connect` fully REPLACES the list, it doesn't append to it
				// The user MUST specify the mandatory `sql_mode` query in their custom list for the MariaDB/MySQL driver to work.
				$values["run_after_connect"] = $arr["run_after_connect"];
				unset($arr["run_after_connect"]);
			}
			if ($arr) {	// there might be nothing left actually
				$textValues = array_shift($arr);	// whatever is left, must be the text one-line settings
				$parts = explode(";", $textValues);
				foreach ($parts as $p) {
					if (!trim($p)) {	// ignore empty settings
						continue;
					}
					// everything is added, including invented unreal settings, there is no filtering (and no consequences)
					$setting = explode("=", trim($p));
					$settingName = array_shift($setting);
					$settingValue = array_shift($setting);
					if (in_array($settingName, $booleanSettings)) {
						// Allow 1/true/yes/y or 0/false/no/n for boolean settings.
						if (in_array(strtolower($settingValue), ["1", "true", "yes", "y"])) {
							$values[$settingName] = true;
						}
						elseif (in_array(strtolower($settingValue), ["0", "false", "no", "n"])) {
							$values[$settingName] = false;
						}
						// Other values for boolean settings are silently ignored without any warning or error!
					}
					else {
						$values[$settingName] = $settingValue;
					}
				}
			}
			//precho(["values" => $values, ]);
			return $values;
		};
		
		foreach (["system" => $defaultsV2, "user" => $config ?: [], ] as $configName => $oneConfig) {
			$references = [];
			foreach ($oneConfig as $refs => $rawStr) {
				$values = $readConfig($rawStr);
				$parts = explode(",", $refs);
				foreach ($parts as $p) {
					//$references[trim($p)] = $rawStr;
					//$sys["compiledConfig"]["{$configName}:{$p}"] = $values;
					$trimP = trim($p);
					foreach ($values as $valueName => $v) {
						$sys["compiledConfig"][$valueName]["{$configName}:{$trimP}"] = $v;
					}
				}
			}
		}
	}
	
	//precho($sys["compiledConfig"]); die();
	
	$useHost = $host ?: $sys["db"]["host"];	// most values are defined by the current connection, but a couple is usable before the connection is established, which requires passing the host
	$port = array_key_exists("port", $sys["db"]) ? $sys["db"]["port"] : 0;	// `default_port` can actually be requested and discovered in this function
	$driver = "";
	
	// when port is set, add its value from `SQLANTERN_PORT_{port}` to `system`, if it's not there yet - so I don't overwrite ports set in `$config`, I only add a port rule if it's not there
	if ($port && !array_key_exists("system:*:{$port}", $sys["compiledConfig"])) {
		$portSetting = "SQLANTERN_PORT_{$port}";
		// environment variable takes priority, if any
		if (getenv($portSetting) !== false) {
			$sys["compiledConfig"]["driver"]["system:*:{$port}"] = getenv($portSetting);
		}
		// constant is next, if any
		elseif (defined($portSetting)) {
			$sys["compiledConfig"]["driver"]["system:*:{$port}"] = constant($portSetting);
		}
		// also use an obsolete setting, if any
		elseif (defined("SQL_PORTS_TO_DRIVERS")) {
			$tmp = json_decode(SQL_PORTS_TO_DRIVERS, true);
			if (array_key_exists($port, $tmp)) {
				$sys["compiledConfig"]["driver"]["system:*:{$port}"] = str_replace(["php-", ".php"], "", $tmp[$port]);
			}
		}
	}
	
	$priorities = [	// the first which is met is delivered
		/*
		The question of the century is: How do I put the driver here in priorities?
		The port sets the driver, I always have the port and I assume that the driver is unknown initially.
		Should I loop the priorities twice - the first loop to only find out the driver by port? That's ugly, but that's the only idea I have. Also, despite being an ugly piece of code, I think the solution is elegant in the end, given the limited conditions.
		*/
		"user:{$useHost}:{$port}",	// port is more important
		"user:{$useHost}:{driver}",	// driver is less important
		"user:{$useHost}:*",	// both `hostname:*` and `hostname` are accepted in the userland
		"user:{$useHost}",
		"user:*:{$port}",	// `port` is less important than `host + port`
		"user:*:{driver}",	// `driver` is less important than `port`
		"user:*",
		// system doesn't have values for hosts
		"system:*:{$port}",
		"system:*:{driver}",	// `driver` is less important than port
		"system:*",
		"default",
	];
	
	/*
	Example:
	system's `*` has `default_port` as `3306`
	user's `sqlantern.com` has `default_port` as `5432`
	...request for `default_port` finds `user:sqlantern.com` first and delivers `5432`
	*/
	
	$result = "";
	
	for ($pass = 0; $pass < 2; $pass++) {
		foreach ($priorities as $prio) {
			if ($pass == 0) {	// only finding out the driver on the first pass
				if (array_key_exists($prio, $sys["compiledConfig"]["driver"])) {
					$driver = $sys["compiledConfig"]["driver"][$prio];
					//precho("`{$prio}` says: driver = `{$driver}`");
					break;
				}
			}
			else {
				if (array_key_exists($prio, $sys["compiledConfig"][$setting])) {
					//return $sys["compiledConfig"][$setting][$prio];	// I hate `return`s in the middle of the code
					$result = $sys["compiledConfig"][$setting][$prio];
					//precho("`{$prio}` says: {$setting} = `{$result}`");
					break;
				}
			}
		}
		if (($pass == 0) && $driver) {	// put the driver into places after the first pass
			foreach ($priorities as &$p) {
				if (strpos($p, "{driver}")) {
					$p = str_replace("{driver}", $driver, $p);
				}
			}
			unset($p);
		}
		//precho(["priorities_with_driver" => $priorities, ]);
	}
	
	
	return $result;
}


if (SQLANTERN_DEVMODE && array_key_exists("config", $_GET)) {
	echo "Testing config...<br>";
	
	//precho(["SERVER" => $_SERVER, ]);	// contains my added value, but is it reliable, doesn't it depend on a PHP setting?
	//precho(["ENV" => $_ENV, ]);	// empty
	//precho(["getenv_HTTP_SQLANTERN_MULTIHOST" => getenv("HTTP_SQLANTERN_MULTIHOST"), ]);	// returns the value!
	//precho(["getenv_SQLANTERN_MULTIHOST" => getenv("SQLANTERN_MULTIHOST"), ]);	// returns the value!
	/*
	IMPORTANT:
	
	Mention in the documentation that `AllowOverride` must allow it to work if used in `.htaccess`
	I think it requires enabling `mod_env` and maybe `mod_setenvif`.
	Does not work with CGI PHP handler (FastCGI?).
	In short, there is a number of conditions for this to work, but I basically expect everybody to use PHP-FPM novadays.
	
	There is also `PassEnv` to pass the existing system-level environment variables. (And I suspect Apache must be restarted to re-read them if they are changed.)
	"Variables may also be passed from the environment of the shell which started the server using the PassEnv directive."
	https://httpd.apache.org/docs/2.4/env.html
	
	Can I also `SetEnv` based on IP? Yes!
	```
	SetEnvIf Remote_Addr 192.168.1.207 SQLANTERN_MULTIHOST=testingEnvIf2
	```
	https://httpd.apache.org/docs/2.4/mod/mod_setenvif.html
	It's important to note that:
	- the second argument is a regex (the value after `Remote_Addr`)
	- multiple variables can be set in one rule ("the rest of the arguments give the names of variables to set")
	```
	SetEnvIf Remote_Addr 192.168.1.(.*) SQLANTERN_MULTIHOST=testingEnvIf2 HTTP_SQLANTERN_MULTIHOST=true
	```
	
	And ".env" compatibility is not needed anymore, which is good news (less complications).
	*/
	
	$config = [
		"sqlantern.com" => "multihost=true; ",
		"*:pgsql" => "postgres_connection_database=unpg; ",
		"*:3306,*:33306,*:33333" => "driver=mysqli; fast_table_rows=no; ",
		"*:5432, *:55432" => "driver=pgsql; postgres_connection_database=pg; ",
	];
	
	$sys["db"] = [
		"host" => "localhost",
		"port" => "5432",
		//"port" => "33308",
		//"port" => "33333",
		"port" => 50000,
	];
	
	$forceHost = "";
	/*
	default_host, multihost
	default_port, fast_table_rows, allow_empty_passwords, postgres_connection_database
	*/
	$testSetting = "postgres_connection_database";
	$testSetting = "driver";
	$testSetting = "run_after_connect";
	precho([$testSetting => getSetting($testSetting, $forceHost), ]);
	
	precho(["compiledConfig" => $sys["compiledConfig"], ]);
	
	die();
}


/*

function queryMatches( $query, $compareTo ) {
	// match words in a string to a template, at the start or at the end
	// query must already have comments removed, because comments are different in different database systems
	// this is basically to find if a query has LIMIT or not
	It can be written as regex, but those would be very complicated to read and debug, I don't want to deal with them.
	
	The idea is to understand if the query ends with "LIMIT *", "LIMIT * OFFSET *", "LIMIT * *", etc
	
	"*" defines if comparing the start or end of a query, and "?" means "any word".
	
	// convert everything to lowercase, replace line breaks and commas with spaces, and break into words
	$query = mb_strtolower($query, "UTF-8");	// reusing `$query`!
	$words = preg_split("/\\s/", $str);
	$queryWords = array_values(array_filter($words, function($w) { return $w != ""; }));	// array_values to reset keys, because array_filter keeps keys and breaks trying to address words by `count minus {n}` below, derp, derp, derp...
	
	$fits = false;
	
	HOW do I traverse two arrays with potentially different offsets in the least stupid way?
	
	The two arrays of words must be compared in parallel, until a "*" is met, which must fast-forward the `query` words.
	
	foreach ($compareTo as $cmp) {
		// convert it to lowercase as well, and break into words, too
		$cmpLower = mb_strtolower($cmp, "UTF-8");
		$cmpWordsLeft = preg_split("/\\s/", $cmpLower);
		$queryWordsLeft = $queryWords;
		$wordNumber = 0;
		while ($cmpWordsLeft || $queryWordsLeft) {
			if ($cmpWordsLeft[$wordNumber] == "*") {
				...
			}
			$wordNumber++;
		}
		
		if ($fits) {
			break;
		}
	}
	
	return $fits;
	
}

*/

/*
Constants, which are safe to configure (override) in the front-end:
<del>SQL_ROWS_PER_PAGE</del>
SQL_DEFAULT_PORT (limited to the ports, defined in SQL_PORTS_TO_DRIVERS, and only after a successful connection; makes very little sense _after_ a successful connection though)
	^ don't do it, makes no real sense
SQL_SET_CHARSET (shouldn't it be per-driver, though??? and I don't really know if it should be configurable, I doubt it)
	^ cannot do it concerning near future changes
SQL_RUN_AFTER_CONNECT (only after a successful connection)
	^ cannot do it concerning near future changes
SQL_DISPLAY_DATABASE_SIZES
<del>SQL_NUMBER_FORMAT</del> << redo to thousands separator, decimals separator, and maybe number of decimals (sizes and profiler, but maybe not...)
SQL_FAST_TABLE_ROWS
SQL_SIZES_FLEXIBLE_UNITS
SQL_KEYS_LABELS			// Is it "too advanced" to change visually (more like hard to explain)?
SQL_DEFAULT_SHORTEN		// it is safe to be configured, but there is no real sense in making it one
SQL_SHORTENED_LENGTH
SQL_POSTGRES_CONNECTION_DATABASE		// I have no idea how to make it per-server
	^ cannot do it concerning near future changes
<del>SQL_MYSQLI_COUNT_SUBQUERY_METHOD</del> << it is deprecated already
SQL_DEDUPLICATE_COLUMNS
	^ it makes so little sense to make it configurable...
SQL_INDEX_COLUMNS_CONCATENATOR

...I think I can actually allow enabling `SQL_MULTIHOST` for properly logged-in users, can't I?
(Just like `SQL_DEFAULT_PORT` is intended. Although changing default port makes so little sense AFTER the connection.)

My initial thoughts about this:
Introduce `$sys["config"]`, fill it with the values from constants initially (which are defaults or taken from `config.sys.php`), with possible change after starting session.
Front side sends options immediately after changing them and on `list_connections`, because this is the place when session might not exist anymore, surprisingly for the front side (expired session).

"Rows per page" should probably be sent with every request for data, and not even saved in config/session.
`SQL_ROWS_PER_PAGE` will only be used as a fallback if "rows per page" are not sent for any reason (improper manual request, basically, because there's no other reason).

Also, the original constant values should probably be a fallback if an inadequate value is provided by the user.
E.g. `SQL_DEFAULT_PORT` should be used if `$sys["config"]["SQL_DEFAULT_PORT"]` has a bad value.
Or should it be checked sooner, on setting the `$sys["config"]`? I really don't want to create a mess there, and it'll pollute that one simple little place with multiple check-ups...
Also, do I even really care for non-valid values?
Hack an invalid port and get a strange error, do I care?
I actually think I _don't_ care, so no, no fallbacks to original constants.

*/

$configurables = [	// configurable from the FRONT-SIDE
	"default_port" => "SQLANTERN_DEFAULT_PORT",
	"queries_after_connect" => "SQLANTERN_RUN_AFTER_CONNECT",	// for the future
	"database_sizes" => "SQLANTERN_DISPLAY_DATABASE_SIZES",
	"fast_rows" => "SQLANTERN_FAST_TABLE_ROWS",
	"size_flex_units" => "SQLANTERN_SIZES_FLEXIBLE_UNITS",
	"shortened_length" => "SQLANTERN_SHORTENED_LENGTH",
	"postgres_connection_database" => "SQLANTERN_POSTGRES_CONNECTION_DATABASE",	// it should be per-server, though...
	"deduplicate_columns" => "SQLANTERN_DEDUPLICATE_COLUMNS",
];

/*
First version of mixed-front-back settings will only include:
- database_sizes
- size_flex_units

And then, in my version of priorities:
- shortened_length
- postgres_connection_database
- default_port
- fast_rows
*/


/*
This is kind of neat - to move everything from constants to $sys["config"] everywhere - but I'm not sure at all I need it to be so universal.
A lot of settings don't need be that flexible.
So, I'm leaving the idea here as a reminder, but in the foreseeable future I'm going to be using a mix of flexible settings and constants.

$sys["config"] = [];
foreach ($configurables as $publicName => $constantName) {
	$sys["config"][$constantName] = constant($constantName);
}

if (array_key_exists("config", $_SESSION)) {
	foreach ($_SESSION["config"] as $optionName => $option) {
		$sys["config"][$optionName] = $option;
	}
}
*/

/*

	WORK IN PROGRESS <<

*/


// some attempts to force longer sessions...
ini_set("session.gc_maxlifetime", 86400);	// some servers have is as low as 10-15 minutes and session gets killed while the browser tab is still open, in the middle of working with the data
session_set_cookie_params(86400);
// s. https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
// (The above doesn't help. Maybe on some servers, but it's definitely not a universal solution.)

// XXX  

function precho( $a ) {	// for science!
	echo "<pre>" . print_r($a, true) . "</pre>";
}

// XXX  

function postProcess( $p = "", $kind = "" ) {
	global $sys;
	// converts all POST values to several arrays:
	// "int", "float", "sql" (escaped), "input" (string, ready to use as a value attribute of an input), "raw" (string)
	//$p = $p ? $p : $_POST;
	
	if (!$p) {
		$p = $_POST;
	}
	
	$root = $kind ? false : true;	// if "kind" not given, this is the first (highest level) run
	
	$availKinds = ["int", "float", "sql", "input", "raw", ];
	
	$res = [];
	if ($p)
		foreach ($p as $key => $v)
			if (is_array($v)) {
				if ($root) {	// only root branches out, we don't need $post["val"]["int"]["q"]["int"] or similar
					// multiple recursive processing isn't a problem, because POST will not be THAT big
					foreach ($availKinds as $k) {
						$res[$k][$key] = $v ? postProcess($v, $k) : [];
					}
				}
				else {
					$res[$key] = $v ? postProcess($v, $kind) : [];
				}
			}
			else {	// primitive values
				$sqlValue = isset($sys["db"]) && isset($sys["db"]["link"]) ? sqlEscape($v) : "";	// only if connection is established
				$inputValue = is_null($v) ? "" : str_replace(["\"", "'", "<", ">"], ["&quot;", "&#39;", "&lt;", "&gt;"], $v);
				if ($root) {
					$res["int"][$key] = (int) $v;
					$res["float"][$key] = (float) $v;
					$res["sql"][$key] = $sqlValue;
					$res["input"][$key] = $inputValue;
					$res["raw"][$key] = $v;
				}
				else {
					if ($kind == "int") {
						$res[$key] = (int) $v;
					}
					if ($kind == "float") {
						$res[$key] = (float) $v;
					}
					if ($kind == "sql") {
						$res[$key] = $sqlValue;
					}
					if ($kind == "input") {
						$res[$key] = $inputValue;
					}
					if ($kind == "raw") {
						$res[$key] = $v;
					}
				}
			}
	
	return $res;
}

// XXX  

function respond() {
	global $response;
	
	// send version with non-empty `connections`, but not with empty connections
	// this way, only users with working credentials are allowed to know the version
	// although, it might also be obvious from the front-end... but I don't want to make life easier for hackers, every small step filters some of them and is worth taking
	if (isset($response["connections"]) && $response["connections"]) {
		$response["version"] = SQLANTERN_VERSION;
	}
	
	// debug:
	//usleep(4 * 1000000);	// 1 second = 1000000
	
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($response);
	/*
	Here's an interesting edge case:
	- `json_encode` requires roughly the same amount of RAM as the data it encodes (naturally, if you think about it)
	- if PHP is given some absurdly low amount of RAM (e.g. less than 16M), the data below that amount is safe to display in the browser and will try to be returned
	- but `json_encode` will cause `Allowed memory size of ___ bytes exhausted`, because it needs a lot of RAM for itself
	I don't think it'll happen in the wild, I write it just to explain what happens if this edge case is tested.
	
	It can also happen when returning unsafe amount data after confirmation (the amount of data multiplied by two might be more than PHP max memory), which is less of an edge case, but is still expected to happen very rarely (I believe browser tab will sooner crash with "Out of memory" for the users who do this).
	*/
	//sqlDisconnect();
	die();
}

// XXX  

function fatalError( $msg, $pause = false ) {
	if (function_exists("sqlDisconnect")) {	// fatal errors sometimes happen without a driver even loaded
		sqlDisconnect();	// would it be faster to not disconnect and let die by itself? does it really matter? :-D
	}
	if ($pause) {
		sleep(2);	// assume bad credentials and hacks, limit bruteforce speed
	}
	die($msg ? "<h2>{$msg}</h2>" : "");
}

// XXX  

function builtInNumberFormat( $n ) {
	return number_format($n, 0, ".", ",");
}

// XXX  

function deduplicateColumnNames( $columns, $tables ) {
	/*
	It's typical to use associative arrays with SQL data in PHP, and lose some columns if multiple columns with the same name/alias are returned.
	E.g. if a query `SELECT * FROM chats_chatters LEFT JOIN chats ON chats.id = chats_chatters.chat_id` returns multiple `id` columns, only the last `id` column is left, when using `mysqli_fetch_assoc`.
	This is fine for pure PHP usage (you cannot have more than one array column or object property with the same name anyway), but it's different from the native SQL console results, which shows multiple columns with the same name all right.
	And it's sometimes confusing, becase I'm not always sure what is the source table of a column, and often not even aware that there are clashes (which I'd fix by selecting only what I need and maybe aliasing).
	
	On the other hand, displaying multiple columns with the same name (like multiple `id`s) is also not clear enough, IMHO.
	So, I decided to add a table name in parenthesis after the column name, I find it very transparent this way.
	The behaviour can be disabled by setting `SQLANTERN_DEDUPLICATE_COLUMNS` to `false`.
	
	By the way, while phpMyAdmin loses such duplicate fields, adminer and pgAdmin don't, kudos to them!
	*/
	if (!SQLANTERN_DEDUPLICATE_COLUMNS) {	// don't deduplicate, return as is
		return $columns;
	}
	// I feel like this code is overcomplicated, but I don't have a better idea right now
	$sameNames = [];
	foreach ($columns as $name) {
		$same = array_filter(
			$columns,
			function ($v) use ($name) {
				return $v == $name;
			}
		);
		$sameNames[] = [
			"column" => $name,
			"quantity" => count($same),
		];
	}
	$sameNames = array_unique(	// leave only unique values
		array_column(	// only leave "column"
			array_filter(	// filter names which happen more than once
				$sameNames,
				function ($s) {
					return $s["quantity"] > 1;
				}
			),
			"column"
		)
	);
	foreach ($columns as $colIdx => &$c) {
		if (!$tables[$colIdx]) {	// I'm not going to add/invent anything for THESE queries (e.g. `SELECT 1, 1, 2`)
			continue;
		}
		if (in_array($c, $sameNames)) {
			$c = "{$c} ({$tables[$colIdx]})";
		}
	}
	unset($c);
	
	// note that incoming `$columns` are changed and returned, not a new array
	return $columns;
}

// XXX  

// thanks to `rommel at rommelsantor dot com` and `evgenij at kostanay dot kz` for the smart code below
// see https://www.php.net/manual/de/function.filesize.php#120250
function builtInBytesFormat( $sizeBytes, $maxSize = 0 ) {
	// returns bytes converted to human size
	// can be flexible (size determines unit) or use the unit which fits the MAX size in a list
	// can be user-defined completely (via "config.sys.php") to use a different logic
	
	if (getSetting("sizes_flexible_units")) {	// the SIZEBYTES determines the factor/multiple
		$factor = floor((strlen($sizeBytes) - 1) / 3);
	}
	else {	// maxSize defines the factor
		$factor = floor((strlen($maxSize) - 1) / 3);
	}
	
	if ($factor) {
		$sz = "KMGTP";
	}
	// 2 decimals are hardcoded!
	$str = sprintf("%.2f", $sizeBytes / pow(1024, $factor));
	/*
	I don't think the result is universal, it's a string, isn't it?..
		f	The argument is treated as a float and presented as a floating-point number (locale aware).
		F	The argument is treated as a float and presented as a floating-point number (non-locale aware).
	
	Also, "393.43MB" becomes "0.38GB", why not "0.39GB"?
	"581.72MB" becomes "0.57GB", why not "0.58GB"?
	"481.15MB" becomes "0.47GB", why not "0.48Gb"?
	Ahhhh... it must be because of 1024, not 1000... all right, it makes sense.
	
	FIXME . . . Why does this function return "0.93Gb", but also "951.11MB" _in the same list_? :-D
	*/
	// now, remove ONE trailing zero if any, to leave values like "176.0", but not "176.00"
	$str = (substr($str, -1) == "0") ? substr($str, 0, -1) : $str;
	$str = ($str == "0.0") ? "0" : $str;	// but don't leave "0.0B"... a lof of conditions I have here, hence the option to replace it with your own logic!
	return $str . @$sz[$factor - 1] . "B";

}

// XXX  

function arrayRowBytes( &$row ) {	// pass by reference to use less RAM, those rows can actually be huge
	/*
	https://www.php.net/manual/en/function.strlen.php says:
	"strlen() returns the number of bytes rather than the number of characters in a string."
	*/
	
	/*
	Stage 1: I'm only worried about saving sessions. It also takes care of "Out of memory" crashes, as a side effect.
	Stage 2: Sessions saving and "Out of memory" crashes will be taken care of separately. (Most likely. We're not there yet, it might change.)
	
	The users can still get _hundreds of thousands_ rows when the rows are tiny (e.g. 90,000 rows from "usda.datsrcln" are returned just fine), which degrades the browser tab performance significantly (the whole tab becomes very laggy with a table like that), but this is expected and desired behaviour: displaying the requested data is more important than browser tab performance.
	I will introduce an additional internal "max rows to return" option later if users call it a problem.
	*/
	$l = 0;
	foreach ($row as $columnName => $v) {
		// Colons, quotes and other structure syntax is not accounted for, and I'm absolutely fine with having an estimation and not an exact count for now.
		$l += strlen($columnName);	// column names take memory when saving a session
		if (is_null($v)) {
			// NULL takes 4 bytes in JSON, it is `null`
			$l += 4;
			continue;
		}
		if (is_array($v)) {	// this is the only place that accounts for JSON syntax
			$l += strlen(json_encode($v));
			continue;
		}
		$l += strlen($v);
	}
	return $l;
}

// XXX  

function translation( $key = "???" ) {
	global $sys;
	/*
	Load translation if not yet loaded.
	Detect browser-side language if language is not set (which is broken interaction with the browser/user actually).
	If browser language is not set or is bad, fallback to default language.
	*/
	
	//$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "kr-GB; ...";	// debug
	
	$translationsDir = __DIR__ . "/../translations";
	
	if (!$sys["language"]) {	// there is only one situation when that's possible: there are no parameters, which means manual request (tinkering)
		// later note: don't be so sure, as there is one more scenario - when we manage to create a buggy incorrect request LOL
		
		// try default browser language first
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) && $_SERVER["HTTP_ACCEPT_LANGUAGE"]) {
			// an example of the `Accept-Language` header: `en-GB,en;q=0.9,en-US;q=0.8,ru;q=0.7,uk;q=0.6`
			
			// testing:
			//$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5";	// must pick `en`
			//$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "uk, en;q=0.8, de;q=0.7, *;q=0.5";	// must pick `uk`
			//$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "fr-CH, fr;q=0.9, uk=0.85, en;q=0.8, de;q=0.7, *;q=0.5";	// must pick `uk`
			
			// check all languages
			$acceptLanguages = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			// the languages are comma-separated, possibly with a space (thus `trim` below)
			// additional `weight` value might be placed after the language, separated with a `;` (like `;q=0.7`), but it doesn't matter here, as we're only using the first 2 letters anyway
			foreach ($acceptLanguages as $lng) {
				$test = mb_strtolower(mb_substr(trim($lng), 0, 2));
				// translation must also exist, thus `file_exists`
				if (preg_match("/[a-z]{2}/", $test) && file_exists("{$translationsDir}/{$test}.json")) {
					$sys["language"] = $test;
					break;
				}
			}
		}
		// if browser language not set or sent, or is not valid, fallback to the configurable server-side parameter
		if (!$sys["language"]) {
			$sys["language"] = SQLANTERN_FALLBACK_LANGUAGE;
		}
	}
	
	if (!isset($sys["translation"])) {	// translations not yet loaded
		// `$sys["language"]` initially comes directly from the user and MUST NOT be trusted
		// the language MUST be two letters and the file with the translations must exist
		if (!preg_match("/[a-z]{2}/", $sys["language"]) || !file_exists("{$translationsDir}/{$sys["language"]}.json")) {
			// fallback to the default language
			$sys["language"] = SQLANTERN_FALLBACK_LANGUAGE;
		}
		
		$translation = json_decode(file_get_contents("{$translationsDir}/{$sys["language"]}.json"), true);
		//var_dump(file_get_contents(__DIR__ . "/../translations/{$sys["language"]}.json"));
		//var_dump([$sys["language"], $translation, ]);
		$sys["translation"] = $translation["back-end"];
	}
	
	return isset($sys["translation"][$key]) ? $sys["translation"][$key] : "Translation not found: \"{$key}\" (`{$sys["language"]}`)";	// returning the `key` of a missing translation, to find and fix it easily; there is NO adequate way to make THIS line multi-lingual... I mean, I could make a configurable constant for that, but seriously... it's only for developers/tranlators to signal about a missing text...
}

// XXX  

function encryptString( $encryptWhat, $encryptWith ) {
	$ivLength = openssl_cipher_iv_length(SQLANTERN_CIPHER_METHOD);
	$iv = substr($encryptWith, 0, $ivLength);
	$key = substr($encryptWith, $ivLength);
	return openssl_encrypt($encryptWhat, SQLANTERN_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
}

// XXX  

function decryptString( $decryptWhat, $decryptWith ) {
	$ivLength = openssl_cipher_iv_length(SQLANTERN_CIPHER_METHOD);
	$iv = substr($decryptWith, 0, $ivLength);
	$key = substr($decryptWith, $ivLength);
	return openssl_decrypt($decryptWhat, SQLANTERN_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
}

// XXX  

function saveConnections() {
	global $connections;
	// saves encrypted connections to the browser-side COOKIE
	
	$con = [];
	
	// passwords are encrypted already, but logins aren't, do it here
	// SESSION is expected to have the same number of keys for connections, as COOKIE has connections (and they are expected to be in the same order)
	// if SESSION is overdue, both must be empty, which is taken care by a different piece of code
	foreach ($connections as $connectionK => $c) {
		$loginJson = json_encode([
			"name" => $c["name"],
			"host" => $c["host"],
			"port" => $c["port"],
			"login" => $c["login"],
		]);
		$con[] = [
			"login" => encryptString($loginJson, $_SESSION["connections"][$connectionK]["login"]),
			"password" => $c["password"],
		];
	}
	
	//var_dump(["name" => SQLANTERN_COOKIE_NAME, "value" => base64_encode(serialize($con)), ]);
	
	// JSON turned out to be text-only, completely unable to handle binary data, you live and learn...
	// so, `serialize` it is...
	// (I expected it to handle anything with some prefix like it handles non-latin UTF symbols.)
	setcookie(SQLANTERN_COOKIE_NAME, base64_encode(serialize($con)), 0, "/");
	// ??? . . . do I care that it's a mix of `JSON`, `serialize` and `base64` formats?
}

// XXX  

function getSessionUniqueId() {
	// returns a current internal guaranteed unique ID and increments it, so it's always collision-free
	// new session = new IDs (which MIGHT cause collisions, but it's not handled currently)
	
	session_start();
	if (!isset($_SESSION["id"])) {	// internal guaranteed unique ID
		$_SESSION["id"] = 1;
	}
	$returnId = $_SESSION["id"];
	$_SESSION["id"]++;
	session_write_close();
	
	return $returnId;
}

// XXX  

function loadDriverByPort( $port ) {
	global $sys;
	/*
	A `port` is ALWAYS set internally for every connection, even if it is not used in the login string (the default port value is used in this case). It is never empty/unset.
	This way `port` can ALWAYS be reliably used to select the database driver.
	*/
	
	/*
	FIXME . . . What holds me from rewriting it to `getSetting`?
	It needs to be `getSetting("driver", "{host}:{port}")`
	Hm... but also `getSetting("default_port", "{host}")` in `add_connection` _sometimes_
	OK, it needs some testing then, all right.
	*/
	
	$driverName = "";
	$portUpper = strtoupper($port);	// for `SQLITE` in constants, basically
	$valueName = "SQLANTERN_PORT_{$portUpper}";
	$envValue = getenv($valueName);
	if ($envValue !== false) {
		$driverName = "php-{$envValue}.php";	// short names like `mysqli` are expected
	}
	elseif (defined($valueName)) {	// newer settings have higher priority
		$driverName = "php-" . constant($valueName) . ".php";	// short names like `mysqli` are expected
	}
	elseif (defined("SQL_PORTS_TO_DRIVERS")) {
		/*
		`SQL_PORTS_TO_DRIVERS` setting is obsolete, but supported for backwards compatibility.
		So, if the `SQLANTERN_PORT_{port}` is not set - try reading `SQL_PORTS_TO_DRIVERS`.
		*/
		$drivers = json_decode(SQL_PORTS_TO_DRIVERS, true);
		$driverName = isset($drivers[$port]) ? $drivers[$port] : "";	// full names already, like `php-mysqli.php`
	}
	
	if (!$driverName) {
		// unknown port is treated as port scanning, thus a vague delayed "CONNECTION FAILED" message
		// (and it is a real connection failure indeed, if you ask me)
		fatalError(
			SQLANTERN_SHOW_CONNECTION_ERROR ?	// show the real reason if `true`
			sprintf(translation("driver-not-found"), $port) :
			sprintf(
				translation("connection-failed-real"),
				"{$sys["db"]["user"]}@{$sys["db"]["host"]}:{$sys["db"]["port"]}"
			),
			true
		);
	}
	
	require_once __DIR__ . "/{$driverName}";
	
	// leave only short "mysqli" or "pgsql" to send to the front side
	$sys["driver"] = str_replace(
		["php-", ".php", ],
		["", "", ],
		$driverName
	);
}


$sys = [];
$response = [];


error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
//error_reporting(E_ALL);	// uncomment to debug Notices and Warnings
ini_set("display_errors", "1");

// one way of isolating sessions, but a bad one for multiple reasons; left as an illustration:
//session_save_path(realpath(__DIR__ . "/../.tmp"));

// running on a dedicated subdomain with the default PHP session cookie name is fine (usually `PHPSESSID`), but running in a directory of an existing website is terrible because of shared session data: SQLantern can destroy website's sessions, and website can destroy SQLantern's session
// so, using a different cookie name to store an isolated session is REQUIRED for uninterrupted work without surprises
session_name(SQLANTERN_SESSION_NAME);

$ok = session_start();

/*
> SESSION DURATION

$check = session_get_cookie_params();
$response["sessionLifetime"] = $check["lifetime"];

Crap...
Does `session_regenerate_id` start a new timer?
Does `session_destroy` create a new session immediately with the old timer, with it expiring sooner?
Or with a new timer, so it is enough to prolong the session?
If I can't prolong the session this way, then how?!

https://www.php.net/manual/en/function.session-regenerate-id.php

> SOLUTION
I can delete SESSION cookie completely, just like I delete another cookie. Not just call `session_regenerate_id`. Or better both.
This way a new session will be created cleanly on the next code run (and not when the `session_regenerate_id` is called or even have inherited life), and the new timer will be correct.
*/

if (!array_key_exists("connections", $_SESSION)) {	// this is a new session
	$_SESSION["started"] = time();	// write down when it started
	$_SESSION["connections"] = [];
	// the front side MUST always send the safe config values after adding a connection or saving settings, but they SHOULD be initially set to something just in case anyway
	$_SESSION["config"] = [
		"display_databases_sizes" => false,
		"sizes_flexible_units" => true,
	];
}


// keep decrypted connections at hand in memory for multiple operations below (except for passwords, only the one for the chosen connection is decrypted at a time)
$connections = [];

if (isset($_COOKIE[SQLANTERN_COOKIE_NAME])) {
	$connections = unserialize(base64_decode($_COOKIE[SQLANTERN_COOKIE_NAME]));
	if (count($connections) == count($_SESSION["connections"])) {
		// decrypting in place...
		foreach ($connections as $connectionK => &$c) {
			$json = decryptString($c["login"], $_SESSION["connections"][$connectionK]["login"]);
			$tmp = json_decode($json, true);
			$c["name"] = $tmp["name"];
			$c["host"] = $tmp["host"];
			$c["port"] = $tmp["port"];
			$c["login"] = $tmp["login"];
		}
		unset($c);
	}
	else {	// some desync happened, a cookie got deleted probably, consider it "no connections"
		$_SESSION["connections"] = [];
		setcookie(SQLANTERN_COOKIE_NAME, "", 0, "/");	// remove cookie completely
	}
}
else {	// no cookie, reset SESSION connections
	$_SESSION["connections"] = [];
}

//precho(["connections" => $connections, "_SESSION_connections" => $_SESSION["connections"], ]);


//var_dump(["ok" => $ok, "_COOKIE" => $_COOKIE, "php_input" => file_get_contents("php://input"), "session_name" => session_name(), "session_save_path" => session_save_path(), "_POST" => $_POST, ]); die();
//var_dump(["_SESSION" => $_SESSION, ]);


$_POST = json_decode(SQLANTERN_INCOMING_DATA, true);

// debug:
//precho(["_POST" => $_POST, ]); die();
//precho(json_encode(["_POST" => $_POST, "_POST_too" => $_POST, ])); die();
//echo "... ??? " . json_encode(["_POST" => $_POST, "_POST_too" => $_POST, ]); die();

$post = postProcess();

if (SQLANTERN_DEVMODE && array_key_exists("devmode", $_GET)) {
	/*
	$post["raw"] = [
		"add_connection" => true,
		"login" => "demo",
		"password" => "demo",
		"connection_name" => "demo@localhost",
	];
	*/
	//precho(["post" => $post, ]);
}

if (!$post["raw"]) {
	header("{$_SERVER["SERVER_PROTOCOL"]} 404 Not Found", true, 404);	// don't accidentally reveal SQLantern to search engines
	header("Content-Type: text/html; charset=utf-8");
	echo translation("request-without-parameters");
	die();
}

$sys["language"] = isset($post["raw"]["language"]) ? $post["raw"]["language"] : "";

// NOTE _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _

if (array_key_exists("add_connection", $post["raw"])) {	// NOTE . . . add_connection
	/*
	>>> THINKING HAT ON
	Now, about protecting db passwords.
	If the connections are fully stored at client and server session only has a key, you'll need both parts.
	But having client's part will allow to try bruteforce it, because you might guess/know the connection names.
	So, storing only passwords at client is also an option.
	But then the server session will have connection names, which have logins, which is bad as well.
	Might make cross-encryption: client has encrypted passwords, server has encrypted logins, and they store keys to each other.
	But that's kind of overwhelming, isn't it?
	
	CONS to saving login AND password in one single string (like JSON):
	- the structure tells when to stop the bruteforce (JSON or even a property name inside it),
	- while if only the password is stored, you never know when to stop, because adequate passwords aren't telling anything.
	
	Hm... all right, I'm thinking two different encryptions for logins and passwords now, that'll make things much, much harder to crack.
	Even better: a pair of random keys for every connection, one for connection name and another one for the password.
	Yes, that'll work.
	<<< THINKING HAT OFF
	
	So...
	- PHP session at server contains keys, a bunch of them, but all of them are useless without client's cookies
	- cookie storage at client contains encrypted logins + host and passwords.
	
	Encrypted values are stored in browser, because browser data is more likely to contain stored passwords anyway, and server-side SESSION stealing is the more critical thing I'm really fighting here.
	Storing only keys on the server makes session completely useless, because it doesn't even contain the data to decrypt, you only have keys.
	And if the browser is compromised, it's a big problem anyway.
	Also imagine the following situation: one server, multiple logins and databases; stealing passwords from multiple SESSIONS compromises many logins/password, while stealing data from browser compromises only one connection (usually/expected).
	*/
	
	if (!$post["raw"]["password"] && !SQLANTERN_ALLOW_EMPTY_PASSWORDS) {	// empty password sent with empty password disbled
		die(translation("empty-passwords-not-allowed"));
	}
	
	// login string format is "login@host:port", with "host" and "port" being optional
	$parts = explode(":", $post["raw"]["login"]);	// "rootik@192.168.1.1:3000" to ["rootik@192.168.1.1", "3000"]
	$port = (count($parts) == 1) ? SQLANTERN_DEFAULT_PORT : array_pop($parts);	// use default port if no port provided
	
	$parts = explode("@", array_pop($parts));	// "rootik@192.168.1.1" to ["rootik", "192.168.1.1"]
	$host = (count($parts) == 1) ? SQLANTERN_DEFAULT_HOST : array_pop($parts);	// use last part of default value
	
	$login = array_pop($parts);
	
	if (!SQLANTERN_MULTIHOST && ($host != SQLANTERN_DEFAULT_HOST)) {	// multi-host is forbidden, and a non-default host is given
		// non-JSON response is the error catching logic, as of now
		die(translation("only-one-host-allowed"));
	}
	
	session_write_close();	// if I don't close session here, one hung connection (which happens when e.g. a server is offline) blocks ALL requests on the same domain until that hung connection times out, because of PHP sessions logic
	// this is also the only place, I believe, where this needs to be done, because other requests go after the global `session_write_close()` below and don't lock the PHP session
	
	$sys["db"] = [
		"host" => $host,
		"port" => $port,
		"user" => $login,
		"password" => $post["raw"]["password"],
		"dbName" => null,
	];
	loadDriverByPort($port);
	sqlConnect();	// only add to connections if connection successful; this line will cause fatal error and the code will not proceed if it is not
	
	session_start();	// reopen the session
	
	$portInNameStr = ($port == SQLANTERN_DEFAULT_PORT) ? "" : ":{$port}";	// only list non-default ports in connection names, as it's a bit annoying otherwise
	$connectionName = "{$login}@{$host}{$portInNameStr}";
	
	// don't duplicate connections, remove one here if the same connection already exists, that takes care of a connection currently in use after a password change
	$k = array_search($connectionName, array_column($connections, "name"));	// `array_column` is PHP 5.5.0+
	if ($k !== false) {
		unset($connections[$k]);
		unset($_SESSION["connections"][$k]);
		// and reset indexes just to keep it neat
		$connections = array_values($connections);
		$_SESSION["connections"] = array_values($_SESSION["connections"]);
	}
	
	
	$ivLength = openssl_cipher_iv_length(SQLANTERN_CIPHER_METHOD);
	// not using `random_bytes` to keep the code down to PHP 5.6: `random_bytes` is PHP 7+
	$iv = openssl_random_pseudo_bytes($ivLength);	// `iv` is "initialization vector", just for my information
	// keys actually combine IV and key, to store them in one string
	$loginKey = $iv . openssl_random_pseudo_bytes(SQLANTERN_CIPHER_KEY_LENGTH);
	$passwordKey = $iv . openssl_random_pseudo_bytes(SQLANTERN_CIPHER_KEY_LENGTH);
	
	// keys go to the server SESSION, encrypted values go to the browser COOKIE
	$_SESSION["connections"][] = [
		"login" => $loginKey,
		"password" => $passwordKey,
	];
	
	// "login" in COOKIE actually stores "name", "host" and "port"
	// and "password" stores only password
	// but that's taken care of in `saveConnections`, and `$connections` in memory have all the information raw (except for the password, which is decrypted in RAM for the ONE used connection)
	// that looks pretty safe to me...
	
	$connections[] = [
		"name" => $connectionName,
		"host" => $host,
		"port" => $port,
		"login" => $login,
		"password" => encryptString($post["raw"]["password"], $passwordKey),
	];
	
	saveConnections();
	
	$response["latest_connection"] = $connectionName;
	
	//precho(["_SESSION_connections" => $_SESSION["connections"], "connections" => $connections, ]);
}

if (array_key_exists("forget_connection", $post["raw"])) {	// NOTE . . . forget_connection
	$k = array_search($post["raw"]["forget_connection"], array_column($connections, "name"));
	if ($k !== false) {
		unset($_SESSION["connections"][$k]);
		unset($connections[$k]);
		
		if (!$_SESSION["connections"]) {	// all connections removed, end the session here
			setcookie(SQLANTERN_COOKIE_NAME, "", 0, "/");	// remove client-side storage cookie completely
			session_destroy();
			setcookie(session_name(), "");	// FIXME . . . is session cookie always `/`? I doubt it...
			//precho(["session_status" => session_status(), ]);
			// looks like `session_destroy` closes session or something... `session_status` is "1", which is "_NONE"
			session_start();
			session_regenerate_id(true);
			$_SESSION["connections"] = [];	// don't trigger "it's a new session" logic near `session_start()`, leave the timer as is
		}
		else {
			$_SESSION["connections"] = array_values($_SESSION["connections"]);
			$connections = array_values($connections);
			saveConnections();
		}
	}
	$response["result"] = "success";
	respond();
}

if (isset($post["raw"]["list_connections"])) {	// NOTE . . . list_connections
	$connections = array_column($connections, "name");
	natsort($connections);
	$response["connections"] = array_values($connections);	// `array_values`, because `natsort` preserves keys, and it becomes an object in JSON (peculiarly to me)
	$response["default_full_texts"] = !SQLANTERN_DEFAULT_SHORTEN;	// if shorten by default, `full texts` must be `off`, and vice versa
	respond();
}

/*

	WORK IN PROGRESS >>

*/
if (isset($post["raw"]["list_config"])) {	// NOTE . . . list_config
	// list of languages on server, <del>ports-to-drivers</del> (don't reveal ports!!!), number format, <del>default rows on page</del> (obsolete), available styles, <del>postgre connection database</del> (don't reveal it!), <del>queries after connect</del> (their logic has changed)
	$response["languages"] = [];
	$files = glob(__DIR__ . "/../translations/*.json");
	foreach ($files as $f) {
		//$info = pathinfo($f);
		//$response["languages"][] = $info["filename"];
		$response["languages"][] = basename($f, ".json");
	}
	respond();
}

if (isset($post["raw"]["save_config"])) {	// NOTE . . . save_config
	//...
}
/*

	WORK IN PROGRESS <<

*/


/*
I'm leaving the possibility to define different costs for the back-up password encryption (`bcrypt` cost or work factor) and encryption key generation (`openssl_pbkdf2` iterations), but I'm not going to document it and even mention it in the configurable parameters at the start of the file.
I'm setting both values higher than recommended (in 2025).
It's also only configurable in `config.sys.php` and _not_ in environment variables.
Setting your own custom value will BREAK all the already saved server-side back-ups (if any)!!!

Server-side backups are encrypted to make a stolen server-side backup file not immediately readable, leaving brute-force the only option to decode it. (Which should help against accidental/lazy hackers, hopefully.)
It's a little bit of help if the server is compomised, nothing more, there are no security wonders here.
Password is the weakest link (by design).

Here are some measures and good commentary regarding `bcrypt` cost:
https://wiki.php.net/rfc/bcrypt_cost_2023

Durations I've measured on some of my systems:
Our main dev Atom:
- `cost`: 12 - 550ms, 13 - 1.1s, 14 - 2.2s
- `iterations`: 600,000 - 1.7s, 1,000,000 - 2.8s
Raspberry Pi 3B:
- `cost`: 12 - 1.7s, 13 - 3.3s, 14 - 6.3s
- `iterations`: 600,000 - 8.0s, 1,000,000 - 13.5s
Xeon Gold 6248R @ 3.00GHz:
- `cost`: 12 - 220ms, 13 - 440ms, 14 - 860ms
- `iterations`: 600,000 - 0.4s, 1,000,000 - 0.65s

I wanted to have left and right side of the stored backups on par for brute-force (the time it takes to iterate), but here are some observations:
`cost` 14 and `iterations` 1,000,000 are close enough on Atom
`cost` 15 and `iterations` 1,000,000 are on par on Pi 3B
`cost` 13 and `iterations` 600,000 are on par on Xeon
I can't conclude or choose anything here, different CPUs have very different results.
I'm making the values 13 and 1,000,000 just to have them higher than recommended, but I can't reliably have them equally strong against brute-force.

It can become unusable on super-low-powered computers like Atom or Raspberry Pi if multiple backups are made (multiple passwords), but I'm going with it anyway. I believe a very small minority of users would be affected and I also provide the tweaking constants anyway.
*/
if (!defined("SERVER_SIDE_BACKUPS_BCRYPT_COST")) {
	define("SERVER_SIDE_BACKUPS_BCRYPT_COST", 13);
}
if (!defined("SERVER_SIDE_BACKUPS_KEY_ITERATIONS")) {
	define("SERVER_SIDE_BACKUPS_KEY_ITERATIONS", 1000000);
}

if (SQLANTERN_DEVMODE && array_key_exists("test_server_backup", $_GET)) {
	$post["raw"] = [
		"save_storage" => true,
		"storage_password" => "11",
		"storage" => ["this", "is", "a", "test", "one", ],
	];
}

if (isset($post["raw"]["save_storage"])) {	// NOTE . . . save_storage
	/*
	Browser storage is impersistent, and I don't want to open the can of worms of storing it in the database.
	I prefer another, smaller can of worms :-) I'll store it to server storage (disk) instead.
	This solution is far from ideal, but is more universal, portable, and safe enough (in my opinion).
	
	Saving and restoring storage is only available for users with at least one valid database connection (so, having at least one correct database password + using additional password = protection).
	That's one more reason NOT to allow remote hosts on an unprotected copy (IP or password-protected directory/domain): someone could just connect to their own remote database and then brute force the backups of storage.
	*/
	
	// TODO . . . For the future: don't save permanent logins onto server, they contain passwords !!!
	
	if (!$connections) {
		die(translation("server-backups-valid-connection-required"));
	}
	
	if (!SQLANTERN_SERVER_SIDE_BACKUPS_ENABLED) {
		die(translation("server-backups-not-enabled"));
	}
	
	if (!SQLANTERN_SERVER_SIDE_BACKUPS_FILE) {	// just in case
		die(translation("server-backups-file-access-denied"));
	}
	
	if (!isset($post["raw"]["storage_password"]) || !$post["raw"]["storage_password"]) {
		die(translation("server-backups-password-required"));
	}
	
	// Does the file exist? Is it readable? Is it writeable?
	if (file_exists(SQLANTERN_SERVER_SIDE_BACKUPS_FILE) && is_dir(SQLANTERN_SERVER_SIDE_BACKUPS_FILE)) {
		die(translation("server-backups-file-access-denied"));	// same error for several reasons
	}
	
	if (file_exists(SQLANTERN_SERVER_SIDE_BACKUPS_FILE)) {
		if (!is_readable(SQLANTERN_SERVER_SIDE_BACKUPS_FILE)) {
			die(translation("server-backups-file-access-denied"));	// could not read the file
		}
		$backups = [];
		require_once SQLANTERN_SERVER_SIDE_BACKUPS_FILE;	// is expected to fill `$backups`
	}
	
	$fp = fopen(SQLANTERN_SERVER_SIDE_BACKUPS_FILE, "w+");
	/*
	https://www.php.net/manual/en/function.fopen.php
	`r+` - Open for reading and writing; place the file pointer at the beginning of the file.
	`w` - Open for writing only; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
	`w+` - Open for reading and writing; otherwise it has the same behavior as 'w'.
	DO I WANT TO USE `c+`??? `w+` works for me, so I won't bother, but I didn't understand the difference.
	*/
	
	if ($fp === false) {	// could not write the file, right?
		die(translation("server-backups-file-access-denied"));
	}
	
	// Lock the file, read the data
	
	flock($fp, LOCK_EX);
	// "Locking will wait for the lock for as long as it takes. It is almose guaranteed that the file will be locked, unless the script times out or something."
	// as said @ https://www.php.net/manual/en/function.flock.php and my tests confirmed it (at least on PHP 8.2)
	
	// test if lock waits for another thread to unlock the file (test passed!)
	if (SQLANTERN_DEVMODE && array_key_exists("test_server_delay", $_GET)) {
		sleep(20);
	}
	
	// Add the new data to the array now
	
	// generate random salt and random IV, both 16 bytes for the algorithm of my choice:
	$salt = openssl_random_pseudo_bytes(16);	// "The US National Institute of Standards and Technology recommends a salt length of at least 128 bits."
	$iv = openssl_random_pseudo_bytes(16);	// no need for `openssl_cipher_iv_length` - length is 16 for "aes-256-cbc"
	// generate a key based on the random salt:
	$encryptWith = openssl_pbkdf2($post["raw"]["storage_password"], $salt, 32, SERVER_SIDE_BACKUPS_KEY_ITERATIONS, "SHA256");
	
	// add time of saving to the back-up
	$post["raw"]["storage"]["type"] = "SQLantern backup";
	$post["raw"]["storage"]["version"] = "1.9.13";
	$post["raw"]["storage"]["backup_date"] = time();	// Unix time is expected here, although CMS integrations can frack it up a bit
	
	/*
	Unlike the global encrypt and decrypt functions, I'm using a hardcoded cipher method here, otherwise changing `SQLANTERN_CIPHER_METHOD` would make the backups saved with a different method unreadable.
	*/
	$encryptedStorage = openssl_encrypt(json_encode($post["raw"]["storage"]), "aes-256-cbc", $encryptWith, OPENSSL_RAW_DATA, $iv);
	
	/*
	`password_hash` (or rather `bcrypt`) truncates the passwords to 72 chars and all the passwords with the same first 72 chars will behave like collisions, but `password_hash` is the only good way to make `backups` keys unique and change on every save - and I really want them to behave like that.
	
	Password hash is the array key, which means multiple storages can be saved in the same file (this is by design).
	As a result, simple passwords = potentitally share your storage with strangers if multiple users save backups to the server in the same SQLantern instance (which can be allowed and will work).
	
	Password hashes lack the starting `$2y${cost}$` prefix to hide the cost, it's added back here on check.
	*/
	$hashBase = "\$2y\$" . SERVER_SIDE_BACKUPS_BCRYPT_COST . "\$";
	$unsetKeys = [];	// just in case there is some stupid mistake in my code and I duplicate the backups accidentally
	foreach ($backups as $hash => $v) {
		if (password_verify($post["raw"]["storage_password"], $hashBase . $hash)) {
			$unsetKeys[] = $hash;
		}
	}
	foreach ($unsetKeys as $u) {
		unset($backups[$u]);
	}
	
	// be aware of the customizable cost
	$passwordHash = password_hash(
		$post["raw"]["storage_password"], PASSWORD_BCRYPT, ["cost" => SERVER_SIDE_BACKUPS_BCRYPT_COST]
	);
	// remove the `$2y${cost}$` prefix to hide the cost
	$passwordHashNoCost = substr($passwordHash, strlen($hashBase));
	
	/*
	I had initially written my own key derivation function (never released) because I though I had to, but then I had a very educating discussion on Reddit where my generous colleagues helped me understand that it is safe to add salt and IV to my saved data, even if they are publicly seen and known (which I didn't know before):
	https://www.reddit.com/r/PHPhelp/comments/1g563gn/criticize_my_key_derivation_function_please/
	I am very grateful to u/HolyGonzo, u/eurosat7, u/identicalBadger and u/MateusAzevedo for helping me understand how to make password-based encryption properly.
	
	So, I'm injecting a random salt and IV into the encrypted code, and they can end up in up to 50 different places, depending on the password length. This is an extemely primitive measure, but as far as I understand, it's better to place them in different locations if I have to store them publicly, and not in the same place (like just appending or prepending them without any offset or with the same offset every time).
	I think the difference is marginal, but it still matters a bit.
	*/
	
	$position = strlen($post["raw"]["storage_password"]) % 50;
	$injectAt = floor((strlen($encryptedStorage) / 50) * $position);
	$storedEncryptedStorage =
		substr($encryptedStorage, 0, $injectAt)
		. $salt
		. $iv
		. substr($encryptedStorage, $injectAt)
	;
	
	$backups[$passwordHashNoCost] = base64_encode($storedEncryptedStorage);	// BASE64 contains encrypted JSON
	
	// Write the file and unlock it.
	
	ftruncate($fp, 0);
	
	$content = "<?php \n";
	foreach ($backups as $k => $v) {
		$content .= "\$backups[\"{$k}\"] = \"{$v}\";\n";
	}
	
	$written = fwrite($fp, $content);
	if ($written === false) {	// could not write (storage full???)
		// gives the same error as not readable and not writeable
		die(translation("server-backups-file-access-denied"));
	}
	
	fflush($fp);	// force write everything to the file no matter some interntal buffer, right?
	
	flock($fp, LOCK_UN);
	fclose($fp);
	
	$response["save_storage"] = "ok";
	
	respond();	// respond here, because "connection_name" is not expected and required, which will trigger an error below
}


if (SQLANTERN_DEVMODE && array_key_exists("test_server_restore", $_GET)) {
	$post["raw"] = [
		"restore_storage" => true,
		"storage_password" => "11",
	];
}

if (isset($post["raw"]["restore_storage"])) {	// NOTE . . . restore_storage 
	/*
	This is a potential brute-force entrance (only for the LocalStorage data, but it is important: everything can contain sensitive data - Notepad, Saved queries, and of course most of all Sessions), but:
	- it is disabled by default (must be enabled on the server side)
	- this code only runs if at least one connection to the database was successful (don't enable multi-host on publicly accessible copies of SQLantern!)
	- there is an additional pause against brute-force
	- an instruction to configure it only for a specific IP will be provided
	*/
	
	if (!$connections) {
		die(translation("server-backups-valid-connection-required"));
	}
	
	if (!SQLANTERN_SERVER_SIDE_BACKUPS_ENABLED) {
		die(translation("server-backups-not-enabled"));
	}
	
	if (!SQLANTERN_SERVER_SIDE_BACKUPS_FILE) {	// just in case
		die(translation("server-backups-file-access-denied"));
	}
	
	if (!isset($post["raw"]["storage_password"]) || !$post["raw"]["storage_password"]) {
		die(translation("server-backups-password-required"));
	}
	
	// Does the file exist? Is it readable?
	if (file_exists(SQLANTERN_SERVER_SIDE_BACKUPS_FILE) && is_dir(SQLANTERN_SERVER_SIDE_BACKUPS_FILE)) {
		die(translation("server-backups-file-access-denied"));	// same error for several reasons
	}
	
	if (file_exists(SQLANTERN_SERVER_SIDE_BACKUPS_FILE)) {
		if (!is_readable(SQLANTERN_SERVER_SIDE_BACKUPS_FILE)) {
			die(translation("server-backups-file-access-denied"));	// could not read the file
		}
		$backups = [];
		require_once SQLANTERN_SERVER_SIDE_BACKUPS_FILE;	// is expected to fill `$backups`
	}
	else {
		die(translation("server-backups-file-access-denied"));	// the same error message, I know...
	}
	
	// Does `$backups` exist? Does it have the key corresponding to the password?
	// Should I tell the user if the `$backups` are empty? That should never happen if the file exists.
	$response["storage"] = "";
	if ($backups) {
		
		$backupIndex = "";
		// Passwords don't have the `$2y${cost}$` prefixes to hide the cost.
		$hashBase = "\$2y\$" . SERVER_SIDE_BACKUPS_BCRYPT_COST . "\$";
		foreach ($backups as $hash => $v) {
			if (password_verify($post["raw"]["storage_password"], $hashBase . $hash)) {
				$backupIndex = $hash;
				break;
			}
		}
		
		if ($backupIndex) {
			
			$storedEncryptedStorage = base64_decode($backups[$backupIndex]);
			
			$position = strlen($post["raw"]["storage_password"]) % 50;
			$injectedAt = floor(((strlen($storedEncryptedStorage) - 32) / 50) * $position);
			
			// cut salt and IV from the encrypted data
			$salt = substr($storedEncryptedStorage, $injectedAt, 16);
			$iv = substr($storedEncryptedStorage, $injectedAt + 16, 16);
			$encryptedStorage = substr($storedEncryptedStorage, 0, $injectedAt) . substr($storedEncryptedStorage, $injectedAt + 32);
			
			$decryptWith = openssl_pbkdf2($post["raw"]["storage_password"], $salt, 32, SERVER_SIDE_BACKUPS_KEY_ITERATIONS, "SHA256");
			
			/*
			Here's an interesting problem: `openssl_decrypt` doesn't return `false` if the data is partially decrypted (corrupted, but partially readable), which happened in my experiments when I tried to pad short passwords with the same "tails". A large part at the end of the key was the same and the data got decrypted, kind of. Partially, with binary characters.
			(It happened when almost the same IVs and exactly the same keys were used.)
			Anyway, what matters here is that the result theoretically might be wrong but not `false`, thus I must also additionally check if it is a correct JSON.
			_The index should not be found_ if there is a _similar but not exactly matching password_, but I'd better add an extra check.
			*/
			$decrypted = openssl_decrypt($encryptedStorage, "aes-256-cbc", $decryptWith, OPENSSL_RAW_DATA, $iv);
			
			if ($decrypted !== false) {
				$unjsoned = json_decode($decrypted, true);
				//precho(["unjsoned" => $unjsoned, ]);
				if (!is_null($unjsoned)) {	// NULL is returned if the value could not be decoded; array is expected here in `$backups` and I could just check for `$unjsoned`, but I decided to be formal this time and check for NULL specifically
					$response["storage"] = $unjsoned;
				}
				else {
					if (SQLANTERN_DEVMODE) {
						precho("DEBUG: could not unJSON");
					}
				}
			}
			else {
				if (SQLANTERN_DEVMODE) {
					precho("DEBUG: could not decrypt");
				}
			}
		}
		else {
			if (SQLANTERN_DEVMODE) {
				precho("DEBUG: could not find");
			}
		}
	}
	else {
		die(translation("server-backups-file-access-denied"));	// one more case of the untrue overgeneralized "access denied" error
	}
	
	if (!$response["storage"]) {
		// assume brute-force attack!
		$timeout = SQLANTERN_SERVER_SIDE_BACKUPS_RESTORE_WRONG_PASSWORD_TIMEOUT;
		if ($timeout < 5) {
			$timeout = 5;
		}
		sleep($timeout);
		die(translation("server-backups-backup-not-found"));
	}
	
	respond();	// respond here, because "connection_name" is not expected and required, which will trigger an error below
	
	/*
	Problem: There is no way to delete one server-side backup.
	Solution: None. Delete the whole file. Store all the needed backups locally before doing that (restore from the server and store locally one by one). Store a back-up copy of the server-side file if you're not sure.
	
	I should provide a special action just for that, BUT:
	What should the interface be? I cannot list the backups LOL!
	Like, just say "found N backups"? I don't like this idea.
	*/
}

session_write_close();

// NOTE _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _

// actions with connections

//$con = $_SESSION["connections"][0];	// use the first connection by default
$k = array_search($post["raw"]["connection_name"], array_column($connections, "name"));
if ($k !== false) {
	$con = $connections[$k];
}
else {
	//$response = ["array_column" => array_column($_SESSION["connections"], "name"), "post_connection_name" => $post["raw"]["connection_name"], "k" => $k];
	// FIXME . . .
	// FIXME . . . . . . THIS BECOMES AN ENDLESS LOOP WITH A SESSION BUT NO COOKIE
	// FIXME . . .
	//die("<h2>CONNECTION DENIED FOR {$post["raw"]["connection_name"]}</h2>");
	die(sprintf(translation("connection-failed-fake"), $post["raw"]["connection_name"]));
}

//$response["post"] = $post; respond();

$sys["db"] = [
	"host" => $con["host"],
	"port" => $con["port"],
	"user" => $con["login"],
	//"password" => $con["password"],
	"password" => decryptString($con["password"], $_SESSION["connections"][$k]["password"]),
	"dbName" => isset($post["raw"]["database_name"]) ? $post["raw"]["database_name"] : "",
];

//precho(["sys_db" => $sys["db"], "_SESSION_connections" => $_SESSION["connections"], "connections" => $connections, ]);

loadDriverByPort($sys["db"]["port"]);

sqlConnect();	// connection is enforced
$post = postProcess();	// because there was no SQL connection and no sqlEscape function before

$sys["db"]["password"] = "";	// remove the password from the RAM immediately after connection, so it's not there if some long operation is done (import, export, long queries)
// Can I remove `user` as well, it isn't used anywhere below, is it? Not doing it for now, as this idea is overkill anyway.
$_COOKIE[SQLANTERN_COOKIE_NAME] = "";	// remove encrypted logins/passwords info from the RAM as well (otherwise the password could be decoded from it again) - it doesn't remove the browser cookie, only the PHP value

// development:
if (SQLANTERN_DEVMODE && false) {
	/*
	display_database_sizes
	sizes_flexible_units
	*/
	$post["raw"]["config"] = [
		"display_databases_sizes" => true,
		"sizes_flexible_units" => false,
	];
}

if (isset($post["raw"]["config"])) {	// NOTE . . . config
	// front side can set some safe values to use in the back side
	session_start();
	//precho("saved config");
	$_SESSION["config"] = [
		"display_databases_sizes" => (boolean) $post["raw"]["config"]["display_databases_sizes"],
		"sizes_flexible_units" => (boolean) $post["raw"]["config"]["sizes_flexible_units"],
	];
	$response["config"] = "accepted";
	session_write_close();
}

if (isset($post["raw"]["list_db"])) {	// NOTE . . . list_db
	$response["databases"] = sqlListDb();
	$response["quote"] = sqlQuote();	// the "identifier quote character" is different in MariaDB/MySQL and PostgreSQL
	//precho(["response" => $response, ]); die();
}

//precho(["response" => $response, ]); die();

if (isset($post["raw"]["list_tables"])) {	// NOTE . . . list_tables
	
	$res = sqlListTables($post["sql"]["database_name"]);
	
	$response["tables"] = $res["tables"];
	$response["views"] = $res["views"];
	
	$response["driver"] = $sys["driver"];
	
	$response["export_import"] = function_exists("sqlExport") && function_exists("sqlImport");
	
	// Isn't the code below a bit overcomplicated AF? :-(
	$limitStr = function( $src, $limit, $value ) {
		return str_replace(
			["{source}", "{limit}", "{value}", ],
			[$src, $limit, $value, ],
			translation("import-server-limit")
		);
	};
	
	$limits = [];
	
	if (function_exists("sqlImportLimits")) {
		// MySQL has a package limit, but Posgres doesn't, so that one is an optional warning...
		$sqlLimits = sqlImportLimits();
		foreach ($sqlLimits as $var => $value) {
			$limits[] = $limitStr("SQL", $var, $value);
		}
	}
	
	$phpVars = ["post_max_size", "upload_max_filesize", "memory_limit", ];
	foreach ($phpVars as $varName) {
		$limits[] = $limitStr("PHP", $varName, ini_get($varName));
	}
	
	$response["import_limits"] = sprintf(
		translation("import-server-limits"),
		implode("<br>", $limits)
	);
	
}

if (isset($post["raw"]["describe_table"])) {	// NOTE . . . describe_table
	$res = sqlDescribeTable($post["sql"]["database_name"], $post["sql"]["table_name"]);
	$response["structure"] = $res["structure"] ? $res["structure"] : [];	// it can be NULL, return empty array anyway
	
	// format the "cardinality" ifany
	$numberFormat = SQLANTERN_NUMBER_FORMAT;	// constants cannot be used as function names directly
	if ($res["indexes"]) {
		foreach ($res["indexes"] as &$row) {
			foreach ($row as $key => &$value) {
				// check for non-empty `$value`, because the value can be empty, instead of NULL/0
				if ($value && (mb_strtolower($key) == "cardinality")) {
					$value = $numberFormat($value);
				}
			}
		}
		unset($row, $value);
	}
	
	$response["indexes"] = $res["indexes"] ? $res["indexes"] : NULL;	// FIXME . . . look into changing the check to `indexes + indexes.length` in JS (don't break sessions!)
}

if (isset($post["raw"]["query"])) {	// NOTE . . . query
	// cannot use ["sql"]["query"], because it converts line breaks to literal "\n" strings in requests, must use "raw"
	$query = trim(trim($post["raw"]["query"]), ";");	// allow queries ending with `;` (but it will not run multiple queries anyway)
	// (and remove any white space first, derp)
	//precho(["query" => $query, ]); die();
	
	$page = isset($post["int"]["page"]) ? (int) $post["int"]["page"] : 0;
	
	$onPage = $post["int"]["rows_per_page"];
	
	$res = sqlRunQuery($query, $onPage, $page, $post["raw"]["full_texts"]);
	
	// debug "processing":
	//sleep(2);
	
	/*
	$response["real_executed_query"] = $res["real_executed_query"];
	$response["num_rows"] = $res["num_rows"];
	$response["num_pages"] = $res["num_pages"];
	$response["cur_page"] = $res["cur_page"];
	$response["rows"] = $res["rows"];
	*/
	$response = array_merge($response, $res);
}

if (isset($post["raw"]["download_binary"])) {	// NOTE . . . download_binary
	$request = [
		"db" => $post["sql"]["db"],
		"table" => $post["sql"]["table"],
		"column" => $post["sql"]["column"],
		"uniq_column" => $post["sql"]["uniq_column"],
		"uniq_value" => $post["sql"]["uniq_value"],
	];
	
	/*
	<del>Default file name is "{database}-{table}({unique}-{ID}){column}.bin"</del>
	Default file name is "{table}-{unique}-{ID}-{column}.bin"
	However... the unique field will not always be an INT and can be long or unreadable, file-system-breaking...
	Can unique field itself be a BINARY/BLOB? :-D I think it can! That would be fascinating to see...
	*/
	
	$p = $post["raw"];
	
	//$fileName = "{$p["db"]}-{$p["table"]}({$p["uniq_column"]}-{$p["uniq_value"]}){$p["column"]}.bin";
	$fileName = "{$p["table"]}-{$p["uniq_column"]}-{$p["uniq_value"]}-{$p["column"]}.bin";
	
	header("Content-Type: application/octet-stream");	// an abstract MIME type
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: 0");
	header("Content-Disposition: attachment; filename=\"{$fileName}\"");
	
	sqlDownloadBinary($request);
}

if (isset($post["raw"]["query_timing"])) {	// NOTE . . . query_timing
	$query = trim(trim($post["raw"]["query_timing"]), ";");	// allow queries ending with `;` (but will not run multiple queries)
	$res = sqlQueryTiming($query);
	$response["time"] = $res["timeMs"];
}

if (isset($post["raw"]["export_database"])) {	// NOTE . . . export_database
	$options = [
		//"format" => "text", // no-no-no, it should be handled outside
		"structure" => in_array($post["raw"]["what"], ["data_structure", "structure"]),
		"data" => in_array($post["raw"]["what"], ["data_structure", "data"]),
		"transactionData" => in_array($post["raw"]["transaction"], ["data", "everything"]),
		"transactionStructure" => ($post["raw"]["transaction"] == "everything"),
		"rows" => (int) $post["int"]["rows"],
	];
	if (isset($post["raw"]["tables"])) {
		$options["tables"] = json_decode($post["raw"]["tables"], true);	// just an array of strings is expected
	}
	
	ini_set("max_execution_time", 0); // == set_time_limit(0)
	//ini_set("memory_limit", "1G");
	
	if ($post["raw"]["format"] == "file") {
		// force further echoes into download
		
		// FIXME . . . I'd like to append _user_ date and time, but it's surprisingly not very trivial and needs a couple of workarounds. I'll revisit it later.
		
		$append = date(SQLANTERN_EXPORT_DB_DATE_SUFFIX);
		$fileName = "{$sys["db"]["dbName"]}{$append}.sql";
		
		//header("Content-Description: File Transfer");	// Why do I keep seeing this header in examples? It must not change anything or add any value. Is it just a case of some thoughtlessly widely copied example?
		
		header("Content-Type: application/sql");
		/*
		```
		The official answer according to IANA is application/sql.
		However, since lots of people don't bother to read documentation you might also want to accept `text/sql`, `text/x-sql` and `text/plain`.
		```
		Source: https://stackoverflow.com/questions/14268401/sql-file-extension-type
		*/
		
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: 0");
		header("Content-Disposition: attachment; filename=\"{$fileName}\"");
	}
	
	sqlExport($options);
	die();	// custom case: the response is not JSON
}

if (isset($post["raw"]["import_get_id"])) {	// NOTE . . . import_get_id
	$response["import_id"] = getSessionUniqueId();
	session_start();
	$_SESSION["import_{$response["import_id"]}"] = json_encode([
		"startedUnix" => time(),
		"progress" => translation("import-progress-uploading"),
		"finished" => false,
	]);
	session_write_close();
}

if (isset($post["raw"]["import_database"])) {	// NOTE . . . import_database
	// import in fact executes any set queries, but it's "import_database" to be in line with "export_database"
	ini_set("max_execution_time", 0); // == set_time_limit(0)
	
	//var_dump(["_FILES" => $_FILES, ]); die();
	//var_dump(translation("import-progress")); die();
	
	// format "file" or "text"
	
	$importSql = "";
	if ($post["raw"]["format"] == "text") {
		$importSql = $post["raw"]["import"];
	}
	elseif ($post["raw"]["format"] == "file") {
		$importSql = file_get_contents($_FILES["import_file"]["tmp_name"]);
	}
	// ^ ^ ^ can run out or memory here
	
	sqlImport((int) $post["int"]["import_id"], $importSql);	// `$importSql` is in fact passed by reference to save RAM
	
	//$response["import"] = $post["raw"]["import"];
	$response["import_database"] = "ok";
}



/*
The code below is a momument to my failed attempts to monitor progress in EventSource.
It didn't work for me here, although exactly the same approach apparently works for other people on the internet.
E.g.: https://stackoverflow.com/questions/31636465/javascript-eventsource-to-update-import-progress-in-a-php-file

Leaving it, because I want to look into it again later, that's so much better than polling.

My search when I abandoned this idea:
php eventsource refresh session site:stackoverflow.com
*/
if (false && isset($post["raw"]["__NOT__import_progress"])) {	// NOTE . . . __NOT__import_progress
	// establishes an EventSource connection, which reports an import progress periodically
	
	ini_set("max_execution_time", 0); // == set_time_limit(0)
	//ini_set("output_buffering", "Off");
	
	// Set file mime type event-stream
	header("Content-Type: text/event-stream");
	header("Cache-Control: no-cache");
	
	$response["finished"] = false;
	
	$importId = (int) $post["int"]["import_id"];
	if (!$importId) {
		$response["error"] = translation("import-bad-id");
		respond();
	}
	if (!$_SESSION["import_{$importId}"]) {
		$response["error"] = translation("import-id-not-found");;
		respond();
	}
	
	/*
	
	The same idea is used here as I'm trying to implement, but I can't do it, while the author could... :-(
	https://stackoverflow.com/questions/31636465/javascript-eventsource-to-update-import-progress-in-a-php-file
	
	*/
	
	// read session, report progress, close session, etc
	
	//sleep(10);	// test if SESSION is updated by `sqlImport`
	
	$n = 0;
	
	while (true) {
		//unset($_SESSION);
		/*
		Caution: Do NOT unset the whole $_SESSION with unset($_SESSION) as this will disable the registering of session variables through the $_SESSION superglobal.
		
		Use $_SESSION = [] to unset all session variables even if the session is not active.
		*/
		//$_SESSION = [];
		
		session_start();	// just read the current values in session
		//session_reset();
		/*
		I can't find it in the official docs, but this seems to be true and it makes sense in almost all cases:
		"session_start() cannot be called once an output has started"
		How the hell do I reread the session in EventSource, though...
		*/
		session_abort();
		//session_write_close();
		
		$progress = json_decode($_SESSION["import_{$importId}"], true);
		
		$sendThis = [
			//"import_id" => $importId,
			//"session" => print_r($_SESSION["import_{$importId}"], true),
			//"session_test" => $_SESSION["test"],
			
			"state" => sprintf(
				translation("import-progress-timer"),
				$progress["progress"],
				time() - $progress["startedUnix"]
			),
			"finished" => $progress["finished"],
		];
		
		//session_write_close();
		
		echo str_pad( 
			"event: message\n"
			. "data: " . json_encode($sendThis) . "\n\n\n",
			4096,
			" "
		);
		// the 4K problem is something I don't know how to solve better, especially without access to the server configuration (think shared hosting), so I'm afraid this "solution" stays here for quite some time or forever
		
		// Flush buffer (force sending data to client)
		//ob_end_flush();
		ob_flush();	// flushing overrides PHP buffer, but apparently not web-server buffer
		flush();
		
		usleep(2 * 1000000);	// 1 second = 1000000
		if (connection_aborted()) {
			break;
		}
		
		//die();
		
		$n++;
		if ($n > 10) {
			$sendThis = ["finished" => true];
			echo str_pad( 
				"event: message\n"
				. "data: " . json_encode($sendThis) . "\n\n\n",
				4096,
				" "
			);
			ob_flush();	// flushing overrides PHP buffer, but apparently not web-server buffer
			flush();
		}
	}
	
	die();
}


if (isset($post["raw"]["import_progress"])) {	// NOTE . . . import_progress
	$importId = (int) $post["int"]["import_id"];
	if (!$importId) {
		$response["error"] = translation("import-bad-id");
		respond();
	}
	if (!$_SESSION["import_{$importId}"]) {
		$response["error"] = translation("import-id-not-found");;
		respond();
	}
	$progress = json_decode($_SESSION["import_{$importId}"], true);
	$response["state"] = sprintf(
		translation("import-progress-timer"),
		$progress["progress"],
		time() - $progress["startedUnix"]
	);
	$response["finished"] = $progress["finished"];
}

//sleep(2);

respond();

//