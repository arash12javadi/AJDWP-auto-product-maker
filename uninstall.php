<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Access database
global $wpdb;

// Table names
$table_templates = $wpdb->prefix . 'ajdwp_templates';
$table_selectors = $wpdb->prefix . 'ajdwp_template_selectors';
$table_urls      = $wpdb->prefix . 'ajdwp_template_urls';

// Drop custom tables (if exist)
$wpdb->query("DROP TABLE IF EXISTS $table_urls");
$wpdb->query("DROP TABLE IF EXISTS $table_selectors");
$wpdb->query("DROP TABLE IF EXISTS $table_templates");

// Optional: Delete options, transients, scheduled hooks (if any)
// Example:
// delete_option('ajdwp_some_option');
// delete_transient('ajdwp_temp_data');
// wp_clear_scheduled_hook('ajdwp_cron_hook');
