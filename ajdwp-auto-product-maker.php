<?php
/*
Plugin Name: AJDWP Auto Product Maker
Description: Automatically scrapes given URLs and creates WooCommerce products with custom selectors.
Version: 1.0
Author: Arash Javadi
Text Domain: ajdwp-auto-product-maker
*/

defined('ABSPATH') || exit;

// Constants
define('AJDWPAPM_PATH', plugin_dir_path(__FILE__));
define('AJDWPAPM_URL', plugin_dir_url(__FILE__));

// Activation & deactivation hooks (optional but good for future use)
register_activation_hook(__FILE__, 'ajdwp_apm_on_activate');
register_deactivation_hook(__FILE__, 'ajdwp_apm_on_deactivate');

function ajdwp_apm_on_activate()
{
    // Placeholder for future setup (e.g. DB version tracking, logs)
}

function ajdwp_apm_on_deactivate()
{
    // Placeholder for cleanup (e.g. remove temp files)
}

// Load plugin only when WooCommerce is active
add_action('plugins_loaded', 'ajdwp_apm_init_plugin');

function ajdwp_apm_init_plugin()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'ajdwp_apm_woocommerce_required_notice');
        return;
    }

    // Include core plugin files
    require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';
    require_once AJDWPAPM_PATH . 'includes/scraper.php';
    require_once AJDWPAPM_PATH . 'includes/product-creator.php';
    require_once AJDWPAPM_PATH . 'includes/helpers.php';
    require_once AJDWPAPM_PATH . 'admin/admin-menu.php';
}

function ajdwp_apm_woocommerce_required_notice()
{
    echo '<div class="notice notice-error"><p><strong>AJDWP Auto Product Maker</strong> requires WooCommerce to be installed and activated.</p></div>';
}
