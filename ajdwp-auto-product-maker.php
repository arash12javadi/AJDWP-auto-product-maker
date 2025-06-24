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

// ============================
// Admin Scripts & Localisation
// ============================
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'ajdwp-auto-product-maker') === false) return;

    wp_enqueue_script(
        'ajdwp-template-admin',
        AJDWPAPM_URL . 'assets/js/template-admin.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('ajdwp-template-admin', 'AJDWP', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ajdwp_template_nonce'),
    ]);
});

// ============================
// Activation: Create DB Tables
// ============================
register_activation_hook(__FILE__, 'ajdwp_apm_on_activate');

function ajdwp_apm_on_activate()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_templates = $wpdb->prefix . 'ajdwp_templates';
    $table_selectors = $wpdb->prefix . 'ajdwp_template_selectors';
    $table_urls      = $wpdb->prefix . 'ajdwp_template_urls';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("
        CREATE TABLE $table_templates (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;
    ");

    dbDelta("
        CREATE TABLE $table_selectors (
            id INT NOT NULL AUTO_INCREMENT,
            template_id INT NOT NULL,
            field_name VARCHAR(50) NOT NULL,
            selector_value TEXT NOT NULL,
            PRIMARY KEY (id),
            INDEX (template_id)
        ) $charset_collate;
    ");

    dbDelta("
        CREATE TABLE $table_urls (
            id INT NOT NULL AUTO_INCREMENT,
            template_id INT NOT NULL,
            product_url TEXT NOT NULL,
            last_scraped DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX (template_id)
        ) $charset_collate;
    ");

    // ✅ Create default template if missing
    $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_templates WHERE name = 'Default Template'");
    if (!$exists) {
        $wpdb->insert($table_templates, ['name' => 'Default Template']);
    }
}

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
}

// WooCommerce Required Notice
function ajdwp_apm_woocommerce_required_notice()
{
    echo '<div class="notice notice-error"><p><strong>AJDWP Auto Product Maker</strong> requires WooCommerce to be installed and activated.</p></div>';
}
