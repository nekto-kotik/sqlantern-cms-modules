<?php
/*
SQLantern integration with Joomla
Copyright (c) Misha Grafski AKA nekto
License: GNU General Public License v3.0

Official links:
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/

defined("_JEXEC") or die("Access Denied");

class plgSystemSqlanternInstallerScript {
	/*
	https://docs.joomla.org/J3.x:Creating_a_simple_module/Adding_an_install-uninstall-update_script_file
	
	See also:
	https://docs.joomla.org/J3.x:Developing_an_MVC_Component/Adding_an_install-uninstall-update_script_file
	
	See also auto-update:
	https://docs.joomla.org/J3.x:Creating_a_simple_module/Adding_Auto_Update
	*/
	
	function install( $parent ) {
		$slash = DIRECTORY_SEPARATOR;
		//$this->moveRecurvise(__DIR__ . "{$slash}sqlantern-joomla", dirname(JPATH_BASE) . "{$slash}administrator");
		rename(
			__DIR__ . "{$slash}sqlantern-joomla",
			dirname(JPATH_BASE) . "{$slash}administrator{$slash}sqlantern-joomla"
		);
		// consider also `copy` + `unlink` or just `copy`
		$database = \JFactory::getDBO();
		// BEWARE THAT I'M EXPECTING `#__viewlevels` ID 6 TO BE "Super Users", AS IT IS BY DEFAULT
		$database->setQuery("
			UPDATE #__extensions
			SET enabled = 1, access = 6
			WHERE 	type = 'plugin'
					AND folder = 'system'
					AND element = 'sqlantern'
		");
		$database->execute();
		// Note many examples online use $db->query() instead of $db->execute(). This was the old method in Joomla 1.5 and 2.5 and will throw a deprecated notice in Joomla 3.0+. < and a fatal "Call to undefined method" in Joomla 4
	}
	
	function postflight( $type, $parent ) {
		if ($type == "install") {
			/*
			Is this the same as `function install( $parent )`? I'm sure it is.
			*/
		}
	}
	
	function uninstall( $parent ) {
		$slash = DIRECTORY_SEPARATOR;
		/*
		Recursive directory delete source:
		https://www.php.net/manual/en/function.rmdir.php#110489
		*/
		$delTree = function( $dir ) use ( &$delTree, $slash ) {
			$files = array_diff(scandir($dir), [".", ".."]);
			foreach ($files as $f) {
				$path = $dir . $slash . $f;
				is_dir($path) ? $delTree($path) : unlink($path);
			}
			rmdir($dir);
		};
		$delTree(dirname(JPATH_BASE) . "{$slash}administrator{$slash}sqlantern-joomla");
	}
}

//