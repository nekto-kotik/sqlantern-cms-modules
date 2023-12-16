<?php
/*
This file is part of SQLantern CMS integration
Copyright (C) 2023 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

define("JOOMLA_ROOT", realpath(__DIR__ . "/../../../"));
require_once(JOOMLA_ROOT . "/configuration.php");

$jConf = new JConfig;

// database port is part of `host` in Joomla! (if it's not default)
// Joomla! uses numbers 3306 and 5432 explicitly, and so do I (see "/libraries/joomla/database/driver/mysqli.php", "/libraries/joomla/database/driver/postgresql.php" in Joomla! 3)
// Joomla! allows using IPv6 for host, but I don't support it, sorry (can't test and debug). Only host names or IPv4 for host, please.
$defaultPorts = [
	"mysqli" => 3306,
	"postgresql" => 5432,	// it's not officially supported yet by SQLantern for Joomla!
];
$parts = explode(":", $jConf->host);
$host = array_shift($parts);
$port = $defaultPorts[$jConf->dbtype];
if ($parts) {	// if anything left, it's port
	$port = array_pop($parts);
}

$sys["db"] = [
	"user" => $jConf->user,
	"host" => $host,
	"port" => $port,
	"password" => $jConf->password,
	"dbName" => $jConf->db,
];

$dbPre = $jConf->dbprefix;

$joomlaLifetimeSec = 60 * (int) $jConf->lifetime;

require_once(__DIR__ . "/php-mysqli.php");

sqlConnect();	// otherwise `sqlEscape` right below won't work

$ok = true;

/*
A million thanks to `Yves Blatti` at https://groups.google.com/g/joomla-dev-general/c/k3tfuVkZBSk

Back-end cookie:
md5(md5("iDDg9mUPKJKdeAQDadministrator")) == "f7b7fd651fe3491d152665ab333e48dd"
secret + "administrator"
Front-end cookie:
md5(md5("iDDg9mUPKJKdeAQDsite")) == "b7ec4b47b788dbaf94ac92e932a0e1e9"
secret + "site"

Enabling shared sessions made another cookie: "b2decaceebe91bcb00be99bb367c929f"...

So, enabling "Shared sessions" creates a `session_name` value in `configuration.php`, which takes priority over "administrator" (and maybe "site" too, but I didn't dig that deep, I don't need that and don't care).
So, if that value exists, it must be used for detecting a session.


 ??? WHAT IF "NONE" IS SET AS SESSION HANDLER, AND NOT "DATABASE" ???

*/

// the plugin must exist and be enabled
$row = sqlRow("
	SELECT enabled, access
	FROM {$dbPre}extensions
	WHERE	type = 'plugin'
			AND folder = 'system'
			AND element = 'sqlantern'
	LIMIT 1
");

if (!$row || !$row["enabled"]) {
	$ok = false;
}
else {
	$cookieSuffix = isset($jConf->session_name) ? $jConf->session_name : "administrator";
	//$cookieSuffix = "administrator";
	$adminCookieName = md5(md5($jConf->secret . $cookieSuffix));
	$onlyForUserGroupId = (int) $row["access"];
	
	// the visitor must be a logged-in admin, which belongs to the ONE specified group (a later version will get multiple groups selection)
	if (isset($_COOKIE[$adminCookieName])) {
		$joomlaSessionIdSql = sqlEscape($_COOKIE[$adminCookieName]);
		$afterTimeSql = time() - $joomlaLifetimeSec;
		// hm... `6` is `Super Users` for some reason, while the Super User group ID is `8`, what is going on?..
		// oh, it's `viewlevels`...
		$row = sqlRow("
			SELECT
				sessions.session_id,
				CONCAT('[', GROUP_CONCAT(users_to_groups.group_id SEPARATOR ','), ']') AS user_groups_json,
				viewlevels.rules AS plugin_groups_json
			FROM {$dbPre}session AS sessions
			LEFT JOIN {$dbPre}users AS users
				ON users.id = sessions.userid
			LEFT JOIN {$dbPre}user_usergroup_map AS users_to_groups
				ON users_to_groups.user_id = users.id
			LEFT JOIN {$dbPre}viewlevels AS viewlevels
				ON viewlevels.id = {$onlyForUserGroupId}
			WHERE 	sessions.session_id = '{$joomlaSessionIdSql}'
					-- user must be enabled
					AND users.block = 0
					-- session must be active
					AND sessions.`time` > {$afterTimeSql}
			GROUP BY users_to_groups.user_id
			LIMIT 1
		");
		//var_dump(["row" => $row, ]);
		if (!isset($row["session_id"])) {	// no active session
			$ok = false;
		}
		else {	// compare user groups and plugin groups
			if (
				!array_intersect(
					json_decode($row["user_groups_json"]),
					json_decode($row["plugin_groups_json"])
				)
			) {
				$ok = false;
			}
		}
	}
	else {
		$ok = false;
	}
	
	if ($ok) {	// prolong session
		// see `/libraries/joomla/session/storage/database.php` for the original logic
		$newTimeSql = time();
		sqlQuery("
			UPDATE {$dbPre}session
			SET `time` = {$newTimeSql}
			WHERE session_id = '{$joomlaSessionIdSql}'
		");
	}
}

// get language
$sys["language"] = "en";	// English by default, if compatible language not found

$row = sqlRow("
	SELECT params
	FROM {$dbPre}extensions
	WHERE 	type = 'component'
			AND element = 'com_languages'
	LIMIT 1
");
if ($row) {
	$joomlaToSqlantern = [
		"en" => "en",
		//"ru" => "uk",	// I gave up to Liana, russian-speaking admins won't get Ukrainian as the default language anymore
		"uk" => "uk",
	];
	$tmp = json_decode($row["params"], true);
	$twoLetterCode = mb_substr($tmp["administrator"], 0, 2);
	if (isset($joomlaToSqlantern[$twoLetterCode])) {
		$sys["language"] = $joomlaToSqlantern[$twoLetterCode];
	}
}

if (!$ok) {
	sqlModuleAccessDenied("joomla");
}

sqlModuleForceSingleDatabase();

//