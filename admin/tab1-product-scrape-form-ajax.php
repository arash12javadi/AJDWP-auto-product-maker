<?php

// ============================
// AJAX: Load Templates defaults on scraping form on the tab 1
// ============================

add_action('wp_ajax_ajdwp_get_template_data', 'ajdwp_get_template_data_callback');

function ajdwp_get_template_data_callback()
{
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $template_id = intval($_POST['template_id']);
    $table = $wpdb->prefix . 'ajdwp_templates';

    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $template_id));

    if ($template) {
        wp_send_json_success($template);
    } else {
        wp_send_json_error(['message' => 'Template not found']);
    }
}
