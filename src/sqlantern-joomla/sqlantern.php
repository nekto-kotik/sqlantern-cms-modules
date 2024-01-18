<?php
/*
SQLantern integration with Joomla
Copyright (c) Misha Grafski AKA nekto
License: GNU General Public License v3.0

Official links:
https://github.com/nekto-kotik/sqlantern-cms-modules
https://github.com/nekto-kotik/sqlantern
*/


/*
 TODO ... can it be made admin-only in `xml` somehow?
*/


defined("_JEXEC") or die;

class PlgSystemSqlantern extends JPlugin {
	
	public function onRenderModule( &$module, &$attribs ) {
		//var_dump(["module" => $module, ]);
		/*
		I'm sorry that I had to resort to this way of adding a menu item, but I don't see any other way around it.
		I don't want to make an empty `component` without any functionality, just for adding a menu item into Control Panel (only `components` get automatically added). No route, no real usage, only for a menu item. It would also mean the link would sit in the "Components" menu, which is confusing and wrong. It's not a "component", it's a system-wide tool and it's place is in "System" or some similar place, but definitely not in "Components".
		
		It adds a very small and negligible overhead, in my opinion, because:
		- Joomla itself only runs it for the users from user groups defined in `access` (typically, only for Super Users)
		- it only runs for modules, which is not a lot of runs per page,
		- admin panel check is a only a couple of instructions, very minimal.
		
		/nekto
		*/
		
		// Control Panel + module "mod_menu" = this is the module to change
		
		$isControlPanel = false;
		
		$versionBelow37 = version_compare(JVERSION, "3.7", "<");
		$version37 = version_compare(JVERSION, "3.7", ">=");
		$version4 = version_compare(JVERSION, "4", ">=");	// "4.0" >= "4" == true, I tested
		
		if ($versionBelow37) {
			$isControlPanel = \JFactory::getApplication()->isAdmin();
		}
		else {	// `isClient` is Joomla 3.7+
			$isControlPanel = \JFactory::getApplication()->isClient("administrator");
		}	// actually, both work fine on 3.10.3, where I developed this code, WTF...
		
		if (
			$isControlPanel
			&&
			($module->module == "mod_menu")
		) {
			//$module->content = "???";	// confirmed: the content is changeable
			
			// the links go to `index.html`, because many servers and hosting account don't consider `index.html` an "index file", and directories' listing is universally disabled, and as a result, just going to `sqlantern-joomla/` often results in "403 Forbidden"
			
			if ($version4) {	// 4.0+
				// I'm sorry to resort to adding inline style, but `target="_blank"` adds an extra icon, which doesn't fit there at all
				$document = \JFactory::getDocument();
				$document->addCustomTag("<style>a.sqlantern[target=_blank]:before { display: none; }</style>");
				
				$module->content = str_replace(
					"</ul></nav>",
					"
						<li class=\"item item-level-1\">
							<a class=\"no-dropdown sqlantern\" href=\"/administrator/sqlantern-joomla/index.html\" target=\"_blank\" aria-label=\"SQLantern\">
								<span class=\"fa-database icon-fw\" aria-hidden=\"true\"></span>
								<span class=\"sidebar-item-title\">SQLantern</span>
							</a>
						</li>
						</ul></nav>
					",
					$module->content
				);
			}
			
			else {	// < 4.0
				$explodeBy = "<li class=\"divider\"><span></span></li>";
				$parts = explode($explodeBy, $module->content);
				
				if (count($parts) < 4) {	// it's a "disabled" menu, don't do anything, there is no place insert a menu item anyway
					return;
				}
				
				array_splice(
					$parts,
					3,
					0,
					["<li><a class=\"no-dropdown\" href=\"/administrator/sqlantern-joomla/index.html\" target=\"_blank\">SQLantern</a></li>"]
				);
				
				$module->content = implode($explodeBy, $parts);
			}
		}
	}
}
//