<?php
/*
This file is part of SQLantern CMS integration
Copyright (C) 2023, 2024 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

//define(OPENCART_ROOT, realpath(__DIR__ . "/../../../"));
//define(OPENCART_ROOT, realpath("{$curdir}/../../../"));
/*
`__DIR__` doesn't work as I expected when this file is a symlink. It returns the directory of the ORIGINAL file, of the source, not of the symlink.
But `$_SERVER["SCRIPT_FILENAME"]` always works, that's why it is used here, and not `__DIR__`.
(I use symlinks, because I write code in one location for multiple versions simultaneously.)

And as I symlinked the whole `sqlantern-opencart` directory, `realpath` with ".." to find the parent directory didn't work with as expected, either.
Hence the strangest `OPENCART_ROOT` workaround below.
This is more of a reminder for myself.

var_dump(["DIR" => __DIR__, "FILE" => __FILE__, "SCRIPT_FILENAME" => $_SERVER["SCRIPT_FILENAME"], "sym_dir" => pathinfo($_SERVER["SCRIPT_FILENAME"], PATHINFO_DIRNAME), ]);
*/

// remove "/admin/sqlantern-opencart/php" from the end to work correctly either symlinked or not
define(
	"OPENCART_ROOT",
	mb_substr(
		pathinfo($_SERVER["SCRIPT_FILENAME"], PATHINFO_DIRNAME),
		0,
		-1 * mb_strlen("/system/library/sqlantern-opencart/php")
	)
);

require_once(OPENCART_ROOT . "/config.php");


function getSessionData() {
	global $sys;
	
	require_once(DIR_CONFIG . "/default.php");
	// I may also want to look into `DIR_CONFIG . "/default.php"`, it has priority and can be modified (does anybody do it, though?)
	session_name($_["session_name"]);
	session_start();
	
	/*
	`$_["session_engine"]` can be "db" and... hm, "file", I presume?
		!!! I don't want to support `file`, it is wicked !!!
	`session_engine` doesn't exist in v2 by default, and isn't used in `framework.php` in v2 anyway...
	So, for v2 the only reasonable way to determine that db sessions are used is table existance.
	And for v3 it's the value of `session_engine`.
	
	v2 session is:
	`public function __construct($adaptor = 'native') {`
	v3 session is:
	`public function __construct($adaptor, $registry = '') {`
	
	At the same time, `native` is v2-only and extends `SessionHandler`, of all things:
	`class Native extends \SessionHandler {`
	*/
	
	$ocSessionId = session_id();
	
	$sys["session_engine"] = "";
	if (isset($_["session_engine"])) {
		$sys["session_engine"] = $_["session_engine"];
	}
	else {	// write session for later use in "native" session
		// this is basically OpenCart 2, and it uses the value from the cookie `default` inside PHP session, there is no config or alternative (or your core code is changed)
		// OpenCart 3 has some conditions about that, but version 3 doesn't use "native" sessions, so it doesn't matter for this integration
		$sys["session"] = $_SESSION[$_COOKIE["default"]];
	}
	
	$sys["date_timezone"] = array_key_exists("date_timezone", $_) ? $_["date_timezone"] : "UTC";
	
	//var_dump(["session_id" => session_id(), "_SESSION" => $_SESSION, ]); die();
	//unset($_SESSION["connections"]); session_write_close();
	session_abort();	// "Discard session array changes and finish session"
	
	// SQLantern session MUST be separated from OpenCart session, but it tends to share it without the workaround below:
	session_name(SQLANTERN_SESSION_NAME);	// defined in `config.sys.php`
	if (!isset($_COOKIE[SQLANTERN_SESSION_NAME])) {	// SQLantern only supports cookie-based sessions!
		if (function_exists("session_create_id")) {	// `session_create_id` is PHP 7+, to my surprise...
			$_COOKIE[SQLANTERN_SESSION_NAME] = session_create_id();
		}
		else {
			$_COOKIE[SQLANTERN_SESSION_NAME] = uniqid("o" . bin2hex(inet_pton($_SERVER["REMOTE_ADDR"]))) . "s";	// I can only hope that's unique enough for this module on PHP 5.6...
		}
		$_SESSION = [];
	}
	session_id($_COOKIE[SQLANTERN_SESSION_NAME]);
	
	// clear SQLantern session:
	//session_start(); $_SESSION = []; session_write_close();
	
	//var_dump(["session_id" => session_id(), "_SESSION" => $_SESSION, ]);
	
	return $ocSessionId;
}

$ocSessionId = getSessionData();
$dbPre = DB_PREFIX;

$sys["db"] = [
	"user" => DB_USERNAME,
	"host" => DB_HOSTNAME,
	"port" => DB_PORT,
	"password" => DB_PASSWORD,
	"dbName" => DB_DATABASE,
];

require_once(__DIR__ . "/php-mysqli.php");
// TODO . . . DB_DRIVER can be "mysqli" or "pgsql"

$sys["language"] = "";	// `sqlConnect` has been rewritten to always use `translation`, and non-set `$sys["language"]` triggered a Notice
sqlConnect();	// otherwise `sqlEscape` directly below won't work
$ocSessionIdSql = sqlEscape($ocSessionId);

/*
co***no has `oc_session` << v3
ho***ca has `oc_session` << v3
	and ho***ca is 99% stock, so this logic is standard for v3, I believe

vo***k has `oc_api_session` << v2
ar***a has `oc_api_session` << v2
be***e has `oc_api_session` << v2
	but I don't think it has anything to do with the admin's session

So, diving deeper into v2...
In `v2` the session has `user_id` inside (inside the standard OpenCart-session-in-PHP-session shit), plus `token`, but the `token` value is not found in the database anywhere, so I think it's for a combination, so that session (server-side) + this token in URL (client-side) give more protection.
And they do give better protection! Client can't read server-side session, so if the client doesn't know the `token`, they might have stolen the session.
This is why there's always `token` in the dashboard URLs.
And then `oc_user.user_group_id` (for `oc_user.user_id`) must be in `oc_user_group.user_group_id` which have access rights in `oc_user_group.permission`, which I highly suspect is exactly the same in v2 and v3.

And v3 PHP session is just completely empty, there is nothing in it, it's an empty array.
(Or am I doing some stupid mistake and see the wrong data?)

Anyway, the question I think is... should I trust `user_id` from the PHP session in v2? and respect `expire` in v3?
On one hand, it's really safer, but on the other hand, session stealing is a big hack anyway and even by itself breaks most other systems, I suspect even including Google accounts.

<del>Should look what dictates that database `expire` in v3.</del> (looked into in and implemented the same logic)

v2 can just be prolonged by just session duration in `/admin/config.php`, I think. If it's not overriden somewhere, which people often do (in their custom changes).

### Later notes
v2 contains almost exactly the same code as v3 to work with sessions in the databases.
There is no table for them by default, though, so it's not used by default and I didn't see it used by my clients.
But it is a possibility, and it should be checked... somehow.
*/

/*
# User Token considerations
OpenCart's User Token adds a little bit of security, which is almost neglectable, but I still want to implement it.

In OpenCart 2, the "native" sessions are used by default, where:
- server-side session contains token
- client has session ID and URL (which contains token)
So, both server and client have both session ID and token, and token adds zero protection.

OpenCart 3 uses "db" sessions by default, which are better:
- database contains session ID and token relation
- server-side session does not contain token
- client has session and URL (which contains token)
Here, user data theft leads to both session ID and token leak.
But server-side session theft now only leaks session ID, giving no access to the dashboard.

Thus, when the user data is stolen, it will also contain URL, which contains user token, thus stealing the data at client grants full access anyway, always, in both OpenCart 2 and OpenCart 3.
But the "db" sessions protect against server-side theft (and there is also OpenCart 2 implementation, but it needs additional actions to activate).
And this is the only scenario with a bit better protection.

It all only matters while the session lasts, of course, which is usually a very short time.

After a lot of thought how to implement those tokens without side effects and user frustration, I ended up with the following solution:
- URL contains hash with token (but not the GET parameter)
- JS reads token from the hash and removes hash from the URL
- JS sends token to back side
- PHP sets a new special cookie, containing the token

This means stealing user data will still grant access, just like in OpenCart, but server-side stealing won't (just like in OpenCart).
And this way the user won't lose their work after token changes: they can open _and close_ a new integrated SQLantern window/tab, and continue work in the old existing tab (because the new tab/window will set the new token cookie value, which the old tab/window will read and work with).
*/

$ok = true;

// get token from the URL or from the cookie
$tokenCookieName = "sqlantern_opencart_token";
if (array_key_exists("opencart_token", $_GET) && $_GET["opencart_token"]) {	// sent and not empty (will be sent, but EMPTY on the manual page refresh)
	setcookie($tokenCookieName, $_GET["opencart_token"], 0, "/");	// plan per-session cookie with no options at all, not even "path"
	$_COOKIE[$tokenCookieName] = $_GET["opencart_token"];
}
$userToken = array_key_exists($tokenCookieName, $_COOKIE) ? $_COOKIE[$tokenCookieName] : "";

// module must be enabled!
$check = sqlRow("
	SELECT status
	FROM {$dbPre}event
	WHERE `code` = 'sqlantern_menu_item'
");
if (!$check || !$check["status"]) {
	$ok = false;
}

// I won't read and analyze the PHP source code to determine the session engine if it's not set in config!!!
if ($sys["session_engine"]) {
	$check = sqlArray("
		SHOW TABLES LIKE '{$dbPre}session'
	");
	$sys["session_engine"] = $check ? "db" : "native";
}
//var_dump(["session_engine" => $sys["session_engine"], ]);


// user must be recognized and have access to modify the module

function userHasAccess( $userId, $token ) {
	global $dbPre, $userToken;
	
	$check = sqlRow("
		SELECT
			users.user_id, user_groups.permission
		FROM {$dbPre}user AS users
		LEFT JOIN {$dbPre}user_group AS user_groups
			ON user_groups.user_group_id = users.user_group_id
		WHERE 	users.user_id = {$userId}
				AND users.status = 1
	");
	if (isset($check["user_id"]) && ($userToken == $token)) {
		$tmp = json_decode($check["permission"], true);
		if (
			!in_array("extension/module/sqlantern_opencart", $tmp["modify"])
		) {
			return false;
		}
	}
	else {
		return false;
	}
	return true;
}

if ($ok && ($sys["session_engine"] == "db")) {
	// There can be a discrepancy between PHP and DB time zones, which can lead to session not being found (wrong comparison of `NOW()` and `expired`), especially when session duration is short.
	// It can also work the other way around: expired session can be treated as still good.
	// Almost all servers I encounter are set to "UTC" in both PHP and DB, but it's not always so.
	// e.g. PHP time zone "Europe/Warsaw" vs DB time zone "UTC"
	date_default_timezone_set($sys["date_timezone"]);
	$timeZoneSql = sqlEscape(date("P"));	// Difference to Greenwich time (GMT) with colon between hours and minutes	Example: +02:00
	sqlQuery("SET time_zone = '{$timeZoneSql}'");
	
	$check = sqlRow("
		SELECT data
		FROM {$dbPre}session
		WHERE 	session_id = '{$ocSessionIdSql}'
				AND expire > NOW()
		-- no need to `LIMIT 1`, because `session_id` is unique
	");
	//var_dump(["ocSessionId" => $ocSessionId, "ocSessionIdSql" => $ocSessionIdSql, "check" => $check, ]);
	if ($check && $check["data"]) {
		$tmp = json_decode($check["data"], true);
		//var_dump(["tmp" => $tmp, ]);
		if (isset($tmp["user_id"]) && isset($tmp["user_token"])) {
			$userId = (int) $tmp["user_id"];
			$token = $tmp["user_token"];
			$ok = userHasAccess($userId, $token);
		}
		else {
			$ok = false;
		}
	}
	else {
		$ok = false;
	}
}


if ($ok && ($sys["session_engine"] == "native")) {
	//var_dump(["_SESSION" => $sys["session"], ]);
	$userId = isset($sys["session"]["user_id"]) ? (int) $sys["session"]["user_id"] : 0;
	$token = isset($sys["session"]["token"]) ? $sys["session"]["token"] : "";
	$ok = userHasAccess($userId, $token);
}


// prolong session exactly in the same way OpenCart does, upon every request to the server
// see `function write` in `/system/library/session/db.php`
if ($ok && ($sys["session_engine"] == "db")) {
	$maxlifetime = ini_get("session.gc_maxlifetime") !== null ? (int) ini_get("session.gc_maxlifetime") : 1440;
	$newExpireSql = sqlEscape(date("Y-m-d H:i:s", time() + $maxlifetime));
	// OpenCart runs `REPLACE INTO`, but I'm using `UPDATE` to only update `expire`, because the session exists as a matter of fact
	sqlQuery("
		UPDATE {$dbPre}session
		SET expire = '{$newExpireSql}'
		WHERE session_id = '{$ocSessionIdSql}'
		-- no need to `LIMIT 1`, because `session_id` is unique
	");
}


// use admin language
$row = sqlRow("
	SELECT `value`
	FROM {$dbPre}setting
	WHERE `key` = 'config_admin_language'
	LIMIT 1
");	// note that multi-store is ignored, no `store_id` condition, only one language can and will be used!
$dashboardLanguage = $row["value"];
/*
Compatible values are:
- en
- en-gb < most popular
- en-us
- english
- ru
- ru-ru < most popular
- russian
- ua
- ua-uk
- uk
- uk-ua < most popular
- ukrainian

Which basically boils down to the first two letters, methinks. Oh, except for `ua`.

Other values fallback to English.
*/
$row = sqlRow("
	SELECT `value`
	FROM {$dbPre}setting
	WHERE `key` = 'config_country_id'
	LIMIT 1
");
$countryLanguage = ($row["value"] == 176) ? "uk" : "";	// a custom case: ПТН ПНХ

$language = $countryLanguage ? $countryLanguage : $dashboardLanguage;

$sys["language"] = "en";	// English by default, if compatible language not found

$opencartToSqlantern = [
	"en" => "en",
	"ua" => "uk",
	"uk" => "uk",
];
$twoLetterCode = mb_substr($language, 0, 2);
$sys["language"] =
	isset($opencartToSqlantern[$twoLetterCode]) ? $opencartToSqlantern[$twoLetterCode] : $sys["language"]
;

if (!$ok) {
	sqlModuleAccessDenied("opencart");
}

sqlModuleForceSingleDatabase();

//