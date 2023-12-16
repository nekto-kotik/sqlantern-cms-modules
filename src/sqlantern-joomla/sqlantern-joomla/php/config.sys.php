<?php
/*
This file is part of SQLantern CMS integration
Copyright (C) 2023 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

define("SQL_MYSQLI_CHARSET", "UTF8MB4");
define("SQL_POSTGRES_CHARSET", "UTF8");
define(
	"SQL_RUN_AFTER_CONNECT",
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
define("SQL_SESSION_NAME", "SQLANTERN_SESS_ID");
define("SQL_COOKIE_NAME", "sqlantern_client");
define("SQL_CIPHER_METHOD", "aes-256-cbc");
define("SQL_CIPHER_KEY_LENGTH", 32);

// since this file is linked in `index.php` before anything else, basically ANY modification can be written here and it is expected

require_once __DIR__ . "/integration-single-database.php";
require_once __DIR__ . "/integration-joomla.php";

//