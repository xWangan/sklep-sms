<?php

$heart->register_page("purchase", "PagePurchase");

class PagePurchase extends Page
{
    const PAGE_ID = "purchase";

    function __construct()
    {
        global $lang;
        $this->title = $lang->translate('purchase');

        parent::__construct();
    }

    public function get_content($get, $post)
    {
        return $this->content($get, $post);
    }

    protected function content($get, $post)
    {
        global $heart, $user, $lang, $settings, $templates;

        if (($service_module = $heart->get_service_module($get['service'])) === null) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy wszystkie skrypty
        if (strlen($this::PAGE_ID)) {
            $path = "jscripts/pages/" . $this::PAGE_ID . "/";
            $path_file = $path . "main.js";
            if (file_exists(SCRIPT_ROOT . $path_file)) {
                $heart->script_add($settings['shop_url_slash'] . $path_file . "?version=" . VERSION);
            }

            $path_file = $path . $service_module->get_module_id() . ".js";
            if (file_exists(SCRIPT_ROOT . $path_file)) {
                $heart->script_add($settings['shop_url_slash'] . $path_file . "?version=" . VERSION);
            }
        }

        // Dodajemy wszystkie css
        if (strlen($this::PAGE_ID)) {
            $path = "styles/pages/" . $this::PAGE_ID . "/";
            $path_file = $path . "main.css";
            if (file_exists(SCRIPT_ROOT . $path_file)) {
                $heart->style_add($settings['shop_url_slash'] . $path_file . "?version=" . VERSION);
            }

            $path_file = $path . $service_module->get_module_id() . ".css";
            if (file_exists(SCRIPT_ROOT . $path_file)) {
                $heart->style_add($settings['shop_url_slash'] . $path_file . "?version=" . VERSION);
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        foreach ($heart->get_services_modules() as $module_info) {
            if ($module_info['id'] == $service_module->get_module_id()) {
                $path = "styles/services/" . $module_info['id'] . ".css";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $heart->style_add($settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }

                $path = "jscripts/services/" . $module_info['id'] . ".js";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $heart->script_add($settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }

                break;
            }
        }

        $heart->page_title .= " - " . $service_module->service['name'];

        // Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
        // Jeżeli wymaga, to to sprawdzamy
        if (object_implements($service_module, "I_BeLoggedMust") && !is_logged()) {
            return $lang->translate('must_be_logged_in');
        }

        // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
        if (!$heart->user_can_use_service($user->getUid(), $service_module->service)) {
            return $lang->translate('service_no_permission');
        }

        // Nie ma formularza zakupu, to tak jakby strona nie istniała
        if (!object_implements($service_module, "IService_PurchaseWeb")) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy długi opis
        $show_more = '';
        if (strlen($service_module->description_full_get())) {
            $show_more = eval($templates->render("services/show_more"));
        }

        $output = eval($templates->render("services/short_description")); // Dodajemy krótki opis
        return $output . $service_module->purchase_form_get();
    }
}