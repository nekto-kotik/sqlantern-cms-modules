<?php
class ControllerExtensionModuleSqlanternOpencart extends Controller {
	
	public function install() {
		/*
		Problem: OpenCart installer doesn't copy Linux hidden files (because of the irresponsible `glob` with "*").
		Solution: Deliver ".htaccess" as "htaccess.txt" and rename on install, if it's not renamed yet.
		*/
		$htaccessRenamed = false;
		$sqlanternPath = DIR_SYSTEM . "library/sqlantern-opencart";
		if (file_exists("{$sqlanternPath}/htaccess.txt")) {
			rename("{$sqlanternPath}/htaccess.txt", "{$sqlanternPath}/.htaccess");
			$htaccessRenamed = true;
		}
		
		/*
		If a user has module installation rights, they are a super admin anyway, so grant them permissions for SQLantern automatically here.
		!!! REMOVE PERMISSIONS ON UNINSTALL !!!
		*/
		
		// add `event` to add the menu item
		if (version_compare(VERSION, "3.0", ">=")) {	// v3
			/*
			If I need to detect non-standard `/system` URL in the future (which I'm not doing until someone gets a problem with it!), the general idea is below.
			Problem: `DIR_APPLICATION` is admin URL, not public URL, it must also be taken into account, I think using difference between `HTTP_SERVER` and `HTTP_CATALOG` somehow (and removing it from `DIR_APPLICATION`, possibly?)...
			
			$url = "https://example.com/alt-admin";
			$app = "/var/www/www/domain.name/";
			$sys = "/var/www/www/domain.name/alt-system/path";
			
			$part = substr($sys, 0, strlen($app));
			
			var_dump([($app === $part ? "true" : "false"), ]);
			*/
			$this->load->model("setting/event");
			$events = $this->model_setting_event;
			
			if ($htaccessRenamed) {	// only do it once
				/*
				Add newly created ".htaccess" to the extension files, so it's deleted on removing the extension. Keep it clean.
				Very oddly and inconsistently, uninstallation deletes hidden files correctly. And upload handler deletes temporary hidden files, too. They are just not being copied to the destination, that's the only problem, it seems.
				*/
				
				$this->load->model("setting/extension");
				
				// this is a wicked workaround to find my `extension_install_id`, but the upside is that it only uses built-in core functions, which is important; I didn't find ANY other way in already existing core models
				$historyTotal = $this->model_setting_extension->getTotalExtensionInstalls();
				$allExtensions = $this->model_setting_extension->getExtensionInstalls(0, $historyTotal);	// get all of them
				$row = array_search(
					"sqlantern_opencart.ocmod.zip",
					array_column($allExtensions, "filename")
				);
				if ($row !== false) {	// who knows, what could go wrong...
					$extensionInstallId = (int) $allExtensions[$row]["extension_install_id"];
					$this->model_setting_extension->addExtensionPath(
						$extensionInstallId,
						"system/library/sqlantern-opencart/.htaccess"
					);
				}
			}
		}
		else {	// v2.3
			$this->load->model("extension/event");
			$events = &$this->model_extension_event;
		}
		
		// NOTE . . . Uninstalling extension in OpenCart 3 doesn't run extension's `uninstall` and leaves the events behind (alongside access rights). I think it should, but there might be unwanted side effect (there shouldn't be, but it needs testing with multiple extensions).
		$events->addEvent(
			"sqlantern_menu_item",
			"admin/view/common/column_left/before",
			"extension/module/sqlantern_opencart/add_menu_item"
		);
	}
	
	public function uninstall() {
		// remove `event`
		if (version_compare(VERSION, "3.0", ">=")) {	// v3
			$this->load->model("setting/event");
			$this->model_setting_event->deleteEventByCode("sqlantern_menu_item");
		}
		else {	// v2.3
			$this->load->model("extension/event");
			$this->model_extension_event->deleteEvent("sqlantern_menu_item");
		}
	}
	
	public function index() {
		
		$this->load->language("extension/module/sqlantern_opencart");
		
		$titleNoTags = strip_tags($this->language->get("heading_title"));
		
		$this->document->setTitle($titleNoTags);
		
		if (version_compare(VERSION, "3.0", ">=")) {	// v3
			$userTokenURL = "user_token={$this->session->data["user_token"]}";
			$extensionsRoute = "marketplace/extension";
			$this->load->model("setting/event");
			$events = &$this->model_setting_event;
			$eventDetails = $events->getEventByCode("sqlantern_menu_item");
		}
		else {	// v2.3
			$userTokenURL = "token={$this->session->data["token"]}";
			$extensionsRoute = "extension/extension";
			$this->load->model("extension/event");
			$events = &$this->model_extension_event;
			$tmp = $events->getEvent(
				"sqlantern_menu_item",
				"admin/view/common/column_left/before",
				"extension/module/sqlantern_opencart/add_menu_item"
			);
			$eventDetails = $tmp[0];
		}
		
		if (!$this->validate()) {
		}
		
		if (($this->request->server["REQUEST_METHOD"] == "POST") && $this->validate()) {
			if ((int) $this->request->post["module_sqlantern_status"]) {
				$events->enableEvent($eventDetails["event_id"]);
			}
			else {
				$events->disableEvent($eventDetails["event_id"]);
			}
			$this->session->data["success"] = $this->language->get("text_success");
			$this->response->redirect($this->url->link($extensionsRoute, "{$userTokenURL}&type=module", true));
		}
		
		$data["breadcrumbs"] = [
			[
				"text" => $this->language->get("text_home"),
				"href" => $this->url->link("common/dashboard", $userTokenURL, true),
			],
			[
				"text" => $this->language->get("text_extensions"),
				"href" => $this->url->link($extensionsRoute, $userTokenURL, true),
			],
			[
				"text" => $this->language->get("text_modules"),
				"href" => $this->url->link($extensionsRoute, "{$userTokenURL}&type=module", true),
			],
			/*[
				//"text" => $this->language->get("heading_title"),
				"text" => $titleNoTags,
				"href" => $this->url->link("extension/module/sqlantern_opencart", $userTokenURL, true),
			],*/
		];
		
		$data["heading_title"] = $titleNoTags;
		$data["text_edit"] = $titleNoTags;
		
		// `event` status IS the enabled/disabled state, no less, no more
		$data["sqlantern_status"] = $eventDetails["status"];
		
		if (isset($this->error["warning"])) {
			$data["error_warning"] = $this->error["warning"];
		} else {
			$data["error_warning"] = "";
		}
		
		$data["action"] = $this->url->link("extension/module/sqlantern_opencart", $userTokenURL, true);
		
		$data["cancel"] = $this->url->link($extensionsRoute, "{$userTokenURL}&type=module", true);
		
		if (version_compare(VERSION, "3.0", "<")) {	// v2.3
			// add texts for version 2.3
			$data["text_status_hint"] = $this->language->get("text_status_hint");
			$data["text_status"] = $this->language->get("text_status");
			$data["text_enabled"] = $this->language->get("text_enabled");
			$data["text_disabled"] = $this->language->get("text_disabled");
		}
		
		$data["header"] = $this->load->controller("common/header");
		$data["column_left"] = $this->load->controller("common/column_left");
		$data["footer"] = $this->load->controller("common/footer");
		
		$this->response->setOutput($this->load->view("extension/module/sqlantern_opencart", $data));
	}
	
	public function add_menu_item(&$route, &$data, &$code) {
		// add a menu item
		//var_dump(["data_menus" => $data["menus"], ]);
		
		// all users from user groups with module permission get the menu item
		if ($this->user->hasPermission("modify", "extension/module/sqlantern_opencart")) {
			
			if (version_compare(VERSION, "3.0", ">=")) {	// v3
				$token = $this->session->data["user_token"];
			}
			else {	// v2.3
				$token = $this->session->data["token"];
			}
			$token64 = base64_encode(json_encode(["token" => $token]));
			
			foreach ($data["menus"] as &$m) {
				if ($m["id"] != "menu-system") {
					continue;
				}
				$m["children"][] = [
					"name" => "SQLantern",
					"href" => "/system/library/sqlantern-opencart/index.html#{$token64}\" target=\"_blank",
					"children" => [],
				];
				break;
			}
		}
	}
	
	protected function validate() {
		if (!$this->user->hasPermission("modify", "extension/module/sqlantern_opencart")) {
			$this->error["warning"] = $this->language->get("error_permission");
		}
		return !$this->error;
	}
}
//