<?php
/*
Plugin Name: AJDWP Auto Product Maker
Description: Automatically scrapes given URLs and creates WooCommerce products.
Version: 1.0
Author: Arash Javadi
*/

defined('ABSPATH') || exit;

// Define constants
define('AJDWPAPM_PATH', plugin_dir_path(__FILE__));
define('AJDWPAPM_URL', plugin_dir_url(__FILE__));

// Includes
require_once AJDWPAPM_PATH . 'includes/scraper.php';
require_once AJDWPAPM_PATH . 'includes/product-creator.php';
require_once AJDWPAPM_PATH . 'admin/admin-menu.php';
