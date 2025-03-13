<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPAP_Admin_Menu
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'WP Advanced Popup',
            'WP Popups',
            'manage_options',
            'wpap_popup_menu',
            [$this, 'render_dashboard_page',],
            'dashicons-welcome-widgets-menus',
            25
        );

        add_submenu_page('wpap_popup_menu', 'Dashboard', 'Dashboard', 'manage_options', 'wpap_popup_menu', [$this, 'render_dashboard_page']);
        add_submenu_page('wpap_popup_menu', 'Add New Popup', 'Add Popup', 'manage_options', 'wpap_add_popup', [$this, 'render_add_popup_page']);
    }

    public function render_dashboard_page()
    {
        echo '<div id="Root"></div>';
    }
    public function render_add_popup_page()
    {
        echo '<div id="addPopup"></div>';
    }

}
