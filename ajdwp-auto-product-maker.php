<?php
/*
Plugin Name: AJDWP Auto Product Maker
Description: Automatically scrapes given URLs and creates WooCommerce products with custom selectors.
Version: 1.0
Author: Arash Javadi
Text Domain: ajdwp-auto-product-maker
*/

defined('ABSPATH') || exit;

// ============================
// Admin Menu Registration
// ============================
add_action('admin_menu', 'ajdwp_apm_register_menu');

function ajdwp_apm_register_menu()
{
    add_menu_page(
        'Auto Product Maker',
        'Auto Product Maker',
        'manage_woocommerce',
        'ajdwp-auto-product-maker',
        'ajdwp_apm_render_settings_page',
        'dashicons-cart',
        56
    );
}

function ajdwp_apm_render_settings_page()
{
    include AJDWPAPM_PATH . 'admin/settings-page.php';
}

// ============================
// Constants
// ============================
define('AJDWPAPM_PATH', plugin_dir_path(__FILE__));
define('AJDWPAPM_URL', plugin_dir_url(__FILE__));

// ============================
// Admin Scripts & Localisation
// ============================
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'ajdwp-auto-product-maker') === false) return;

    // Shared AJAX data
    $ajax_vars = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ajdwp_template_nonce'),
    ];

    // Enqueue Template Admin JS
    wp_enqueue_script(
        'ajdwp-template-admin',
        AJDWPAPM_URL . 'assets/js/template-admin.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('ajdwp-template-admin', 'AJDWP', $ajax_vars);

    // Enqueue Template Products JS
    wp_enqueue_script(
        'ajdwp-template-products',
        AJDWPAPM_URL . 'assets/js/template-products.js',
        ['jquery'],
        null,
        true
    );

    // Optional: Load only on your plugin's admin page
    if (strpos($hook, 'ajdwp-auto-product-maker') === false) return;

    wp_enqueue_style(
        'ajdwp-style',
        AJDWPAPM_URL . 'assets/css/style.css',
        [],
        null
    );
});



// ============================
// Activation: Create DB Tables -> to be tested later 
// ============================

// Activation Hook
require_once plugin_dir_path(__FILE__) . 'templates/create_db_on_activation.php';
register_activation_hook(__FILE__, 'ajdwp_apm_create_db_tables');


// ============================
// Deactivation: Optional Cleanup
// ============================
register_deactivation_hook(__FILE__, 'ajdwp_apm_on_deactivate');

function ajdwp_apm_on_deactivate()
{
    // Example: remove debug file
    $debug_path = AJDWPAPM_PATH . 'debug-rendered.html';
    if (file_exists($debug_path)) {
        unlink($debug_path);
    }

    // Optional: cleanup tasks or transients
    // wp_clear_scheduled_hook('ajdwp_cron_hook');
}

// ============================
// Plugin Init (Only if WooCommerce is Active)
// ============================
add_action('plugins_loaded', 'ajdwp_apm_init_plugin');

function ajdwp_apm_init_plugin()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'ajdwp_apm_woocommerce_required_notice');
        return;
    }

    // ✅ Load plugin components
    require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';
    require_once AJDWPAPM_PATH . 'includes/scraper.php';
    require_once AJDWPAPM_PATH . 'includes/product-creator.php';
    require_once AJDWPAPM_PATH . 'includes/helpers.php';
    require_once AJDWPAPM_PATH . 'admin/admin-menu.php';
    require_once AJDWPAPM_PATH . 'admin/ajax-handlers.php';
}

// WooCommerce Required Notice
function ajdwp_apm_woocommerce_required_notice()
{
    echo '<div class="notice notice-error"><p><strong>AJDWP Auto Product Maker</strong> requires WooCommerce to be installed and activated.</p></div>';
}
