<?php
/*
Plugin Name: AJDWP Auto Product Maker
Description: Automatically scrapes given URLs and creates WooCommerce products with custom selectors.
Version: 1.0
Author: Arash Javadi
*/

defined('ABSPATH') || exit;

// Constants
define('AJDWPAPM_PATH', plugin_dir_path(__FILE__));
define('AJDWPAPM_URL', plugin_dir_url(__FILE__));

// Require WooCommerce before proceeding
add_action('plugins_loaded', 'ajdwp_apm_init_plugin');

function ajdwp_apm_init_plugin()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>AJDWP Auto Product Maker</strong> requires WooCommerce to be installed and activated.</p></div>';
        });
        return;
    }

    // Include dependencies
    require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';
    require_once AJDWPAPM_PATH . 'includes/scraper.php';
    require_once AJDWPAPM_PATH . 'includes/product-creator.php';
    require_once AJDWPAPM_PATH . 'includes/helpers.php';
    require_once AJDWPAPM_PATH . 'admin/admin-menu.php';
}
