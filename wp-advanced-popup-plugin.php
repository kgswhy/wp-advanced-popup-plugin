<?php
/*
Plugin Name: WP Advanced Popup Plugin
Description: Plugin popup canggih untuk WordPress dengan fitur analytics, scheduling, dan exit intent.
Version: 1.0
Author: Developer Kamu
*/

if (!defined('ABSPATH')) {
    exit; // Hentikan akses langsung
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Load class
require_once plugin_dir_path(file: __FILE__) . 'includes/class-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api.php';


function enqueue_react_app()
{
    wp_enqueue_script(
        'wpap-react-app',
        plugin_dir_url(__FILE__) . './assets/js/popup.js', // Sesuaikan dengan lokasi file hasil build React
        array('wp-element'), // WordPress React dependency
        '1.0.0',
        true
    );
    wp_enqueue_style(
        'wpap-react-style',
        plugin_dir_url(__FILE__) . './assets/scss/main.scss', // Jika menggunakan CSS atau SASS
        array(),
        '1.0.0'
    );

}
function enqueue_jwt_script()
{
    wp_enqueue_script('store-token', plugin_dir_url(__FILE__) . './assets/js/token.js', [], false, true);
}
add_action('wp_enqueue_scripts', 'enqueue_jwt_script');


// Enqueue Scripts & Styles
function enqueue_popup_scripts()
{
    wp_enqueue_script('popup-script', plugin_dir_url(__FILE__) . '/assets/js/popup-script.js', ['wp-api'], false, true);
    wp_localize_script('popup-script', 'wpPopupData', [
        'apiUrl' => rest_url('artistudio/v1/get-popups'),
        'tokenUrl' => rest_url('artistudio/v1/get-token'),
        'currentPageId' => get_queried_object_id(),
    ]);

    wp_enqueue_script('token-script', plugin_dir_url(__FILE__) . 'assets/js/token.js', [], false, true);
    wp_enqueue_style('popup-style', plugin_dir_url(__FILE__) . 'assets/css/popup-style.css');
}

add_action('wp_enqueue_scripts', 'enqueue_popup_scripts');




add_action('admin_enqueue_scripts', 'enqueue_react_app');

function wp_popup_manager_enqueue_scripts()
{
    // Enqueue JS
    wp_enqueue_script(
        'wp-popup-script',
        plugin_dir_url(__FILE__) . './assets/js/popup-script.js',
        array(),
        null,
        true
    );

    // Kirim data dari PHP ke JS
    wp_localize_script('wp-popup-script', 'wpPopupData', array(
        'apiUrl' => get_rest_url(null, 'artistudio/v1/popup'),
        'currentPageId' => get_the_ID(),
    ));

    // Enqueue CSS
    wp_enqueue_style(
        'wp-popup-style',
        plugin_dir_url(__FILE__) . './assets/css/popup-style.css'
    );
}

add_action('wp_enqueue_scripts', 'wp_popup_manager_enqueue_scripts');


new WPAP_Admin_Menu();



