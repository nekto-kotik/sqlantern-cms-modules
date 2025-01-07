<?php
/*
This file is part of SQLantern CMS integration
Copyright (C) 2023 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

define("SQLANTERN_MYSQLI_CHARSET", "UTF8MB4");
define("SQLANTERN_POSTGRES_CHARSET", "UTF8");
define("SQLANTERN_SHOW_CONNECTION_ERROR", false);
define("SQLANTERN_USE_SSL", false);
define("SQLANTERN_TRUST_SSL", false);
define(
	"SQLANTERN_RUN_AFTER_CONNECT",
	json_encode([
		"mysqli" => [
			"SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))",
			// removing `ONLY_FULL_GROUP_BY` is REQUIRED for the built-in table indexes request to work at all
			// "MySQL anyway removes unwanted commas from the record."
		],
		"pgsql" => [
		],
	])
);
define("SQLANTERN_SESSION_NAME", "SQLANTERN_SESS_ID");
define("SQLANTERN_COOKIE_NAME", "sqlantern_client");
define("SQLANTERN_CIPHER_METHOD", "aes-256-cbc");
define("SQLANTERN_CIPHER_KEY_LENGTH", 32);
define("SQLANTERN_NUMBER_FORMAT", "builtInNumberFormat");

// since this file is linked in `index.php` before anything else, basically ANY modification can be written here and it is expected

$sys = [];

require_once __DIR__ . "/integration-single-database.php";
require_once __DIR__ . "/integration-opencart.php";

//