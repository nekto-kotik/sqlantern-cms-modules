<?php
class ControllerExtensionModuleSqlanternOpencart extends Controller {
	
	public function install() {
		// add `event` to add the menu item
		if (version_compare(VERSION, "3.0", ">=")) {	// v3
			$this->load->model("setting/event");
			$events = $this->model_setting_event;
		}
		else {	// v2.3
			$this->load->model("extension/event");
			$events = &$this->model_extension_event;
		}
		
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
					"href" => "/admin/sqlantern-opencart/index.html#{$token64}\" target=\"_blank",
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