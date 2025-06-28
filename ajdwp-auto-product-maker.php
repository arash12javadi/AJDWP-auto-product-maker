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
// Constants
// ============================
define('AJDWPAPM_PATH', plugin_dir_path(__FILE__));
define('AJDWPAPM_URL', plugin_dir_url(__FILE__));
define('AJDWPAPM_DEBUG', false); // change to true when debugging

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
// Admin Scripts & Localisation
// ============================
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'ajdwp-auto-product-maker') === false) return;

    // Shared AJAX data
    $ajax_vars = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ajdwp_template_nonce'),
    ];

    // Enqueue Tab 2 - Template Manager JS
    wp_enqueue_script(
        'tab2-template-manager',
        AJDWPAPM_URL . 'assets/js/tab2-template-manager.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('tab2-template-manager', 'AJDWP_tab2', $ajax_vars);

    // Enqueue Tab 3 - Add New Template JS
    wp_enqueue_script(
        'tab3-add-new-template',
        AJDWPAPM_URL . 'assets/js/tab3-add-new-template.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('tab3-add-new-template', 'AJDWP_tab3', $ajax_vars);

    // Enqueue Product Scraper JS (Tab 1)
    wp_enqueue_script(
        'tab1-product-scrape-form',
        AJDWPAPM_URL . 'assets/js/tab1-product-scrape-form.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('tab1-product-scrape-form', 'AJDWP_tab1', $ajax_vars);

    // Enqueue Shared Styles
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
require_once plugin_dir_path(__FILE__) . 'templates/create-db-on-activation.php';
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
    require_once AJDWPAPM_PATH . 'admin/tab1-product-scrape-form-ajax.php';
    require_once AJDWPAPM_PATH . 'admin/tab2-templates-manager-ajax.php';
    require_once AJDWPAPM_PATH . 'admin/tab3-add-new-template-ajax.php';
    require_once AJDWPAPM_PATH . 'includes/helpers.php';
}

// WooCommerce Required Notice
function ajdwp_apm_woocommerce_required_notice()
{
    echo '<div class="notice notice-error"><p><strong>AJDWP Auto Product Maker</strong> requires WooCommerce to be installed and activated.</p></div>';
}
