<?php

add_action('wp_ajax_ajdwp_add_template', 'ajdwp_apm_ajax_add_template');
function ajdwp_apm_ajax_add_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $table = "{$wpdb->prefix}ajdwp_templates";

    $data = [
        'name'                    => sanitize_text_field($_POST['name'] ?? ''),
        'title_selector'          => sanitize_text_field($_POST['title_selector'] ?? ''),
        'short_desc_selector'     => sanitize_text_field($_POST['short_desc_selector'] ?? ''),
        'long_desc_selector'      => sanitize_text_field($_POST['long_desc_selector'] ?? ''),
        'main_image_selector'     => sanitize_text_field($_POST['main_image_selector'] ?? ''),
        'gallery_image_selectors' => sanitize_text_field($_POST['gallery_image_selectors'] ?? ''),
        'price_selector'          => sanitize_text_field($_POST['price_selector'] ?? ''),
        'price_multiplier'        => sanitize_text_field($_POST['price_multiplier'] ?? ''),
        'scrape_method'           => sanitize_text_field($_POST['scrape_method'] ?? 'auto'),
    ];

    // Insert into database
    $result = $wpdb->insert($table, $data);

    if ($result === false) {
        wp_send_json_error([
            'message' => '❌ Insert failed. Check database columns and data types.',
            'sql'     => $wpdb->last_query,
            'error'   => $wpdb->last_error,
        ]);
    }

    // Success
    wp_send_json_success([
        'id'   => $wpdb->insert_id,
        'name' => $data['name'],
    ]);
}
