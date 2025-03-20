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
 TODO . . . can it be made admin-only in `xml` somehow?
 
 FIXME . . . I can create a proper menu item the following way:
 - create an admin-only menu
 - add one item to it (custom URL) with Target Window "New Window With Navigation"
 - create an Administrator module of type "Administrator menu" with position "menu" (disable "Checks")
 - "Link icon class" must be "icon-fw fa-up-right-from-square" ("icon-fw fa-arrow-up-right-from-square" looks exactly the same) or "icon-fw fa-square-arrow-up-right" or "icon-fw fa-square-up-right"
 - PROFIT without messing with HTML
 It works in Joomla 5. I'm sure it works in Joomla 4 (but haven't tested yet).
 It works in Joomla 3 and might actually work with versions below 3.6, which I don't support at the moment (not that it matters that much).
 Problem: The icon for `<a>`s with `target="_blank"` fucks it visually in Joomla 5 :-(
 Solution: Inject the following crazy style:
 ```
 .main-nav a[href*="sqlantern"]:before {
   display: none; }
 ```
 
 Joomla 3 with its horizontal menu looks fine, but I'd really like to add an item into "System" menu instead of creating a global item - can I somehow legitimately do that?
 As far as I can tell, the standard menu is defined in `/administrator/components/com_menus/presets/joomla.xml`
 I don't know... I can't say I really care about Joomla 3 that much to override the preset, I might introduce new problems there instead of solving my small non-issue.
 
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