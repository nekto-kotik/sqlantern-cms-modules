<?php
/*
This file is part of SQLantern CMS integration
Copyright (C) 2023, 2024, 2025 Misha Grafski AKA nekto
License: GNU General Public License v3.0
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

function sqlModuleAccessDenied( $cms ) {
	global $sys;
	// load the language and output the message in the language
	// with some explanation per-CMS
	$translation = json_decode(file_get_contents(__DIR__ . "/../translations/{$sys["language"]}.json"), true);
	echo $translation["back-end"]["{$cms}-access-denied"];
	die();
}

function sqlModuleForceSingleDatabase() {
	global $sys;
	
	// forces only one connection from the CMS config
	// written in a function just to keep used variables from going global
	
	$connectionName = "{$sys["db"]["user"]}@{$sys["db"]["host"]}" . ($sys["db"]["port"] == 3306 ? "" : ":{$sys["db"]["port"]}");
	
	// initial version was only fetch-compatible, and Export/Import didn't work
	//$incoming = json_decode(file_get_contents("php://input"), true);
	
	// fetch, POST, and GET compatible operations:
	$incoming =
		$_POST ?	// POST priority
		$_POST :
		(
			$_GET ?	// GET (only for EventSource progress monitor, because EventSource is only GET...)
			$_GET :
			json_decode(file_get_contents("php://input"), true)	// standard fetch requests
		)
	;
	
	$incoming["connection_name"] = $connectionName;
	$incoming["database_name"] = $sys["db"]["dbName"];
	
	if (isset($incoming["list_connections"])) {
		echo json_encode(["connections" => ["integration"]]);	// list one fake connection for keep-alive
		die();
	}
	
	unset($incoming["list_connections"], $incoming["list_db"]);
	
	define("SQLANTERN_INCOMING_DATA", json_encode($incoming));
	
	session_start();
	if (!isset($_SESSION["connections"])) {
		$ivLength = openssl_cipher_iv_length(SQLANTERN_CIPHER_METHOD);
		$iv = openssl_random_pseudo_bytes($ivLength);
		// keys actually combine IV and key, to store them in one string
		$loginKey = $iv . openssl_random_pseudo_bytes(SQLANTERN_CIPHER_KEY_LENGTH);
		$passwordKey = $iv . openssl_random_pseudo_bytes(SQLANTERN_CIPHER_KEY_LENGTH);
		$_SESSION["connections"] = [
			[
				"login" => $loginKey,
				"password" => $passwordKey,
			]
		];
	}
	session_write_close();
	
	// SESSION is kept, but `connections` in it are reset every time, so fiddling with the SESSION can cause a fatal error, but not jailbreak
	
	$loginJson = json_encode([
		"name" => $connectionName,
		"login" => $sys["db"]["user"],
		"host" => $sys["db"]["host"],
		"port" => $sys["db"]["port"],
	]);
	
	$ivLength = openssl_cipher_iv_length(SQLANTERN_CIPHER_METHOD);
	
	$encryptWhat = $loginJson;
	$encryptWith = $_SESSION["connections"][0]["login"];
	$iv = substr($encryptWith, 0, $ivLength);
	$key = substr($encryptWith, $ivLength);
	$encryptedLogin = openssl_encrypt($encryptWhat, SQLANTERN_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
	
	$encryptWhat = $sys["db"]["password"];
	$encryptWith = $_SESSION["connections"][0]["password"];
	$iv = substr($encryptWith, 0, $ivLength);
	$key = substr($encryptWith, $ivLength);
	$encryptedPassword = openssl_encrypt($encryptWhat, SQLANTERN_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
	
	$con = [
		[
			"login" => $encryptedLogin,
			"password" => $encryptedPassword,
		]
	];
	
	// connections come from a COOKIE, so force that COOKIE value, without setting the actual cookie itself:
	$_COOKIE[SQLANTERN_COOKIE_NAME] = base64_encode(serialize($con));
	
	// if we're here, the connection is allowed and confirmed, let's add a special integration custom request here, too
	if (isset($_GET["cms_settings"])) {
		// this version extraction is asinine, but I can't come up with a less terrible way than this, without rewriting the SQLantern just for the sake of returning the version, which is also stupid...
		$txt = file_get_contents(__DIR__ . "/index.php");
		$pos = strpos($txt, "\"SQLANTERN_VERSION\"");
		$part = substr($txt, $pos);
		$parts = explode("\n", $part);
		$versionLine = array_shift($parts);	// value like `"SQL_VERSION" => "0.9.18 beta",	// 2310xx`
		$parts = explode("\"", $versionLine);	// array like ["", "SQL_VERSION", " => ", "0.9.18 beta", ",	// 2310xx`"]
		$version = $parts[3];
		
		// language and database name
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode([
			"language" => $sys["language"],
			"database" => $sys["db"]["dbName"],
			"version" => $version,
		]);
		die();
	}
}

//