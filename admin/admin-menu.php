<?php

// ============================
// AJAX: Template CRUD
// ============================
add_action('wp_ajax_ajdwp_add_template', 'ajdwp_apm_ajax_add_template');
add_action('wp_ajax_ajdwp_update_template', 'ajdwp_apm_ajax_update_template');
add_action('wp_ajax_ajdwp_delete_template', 'ajdwp_apm_ajax_delete_template');

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


function ajdwp_apm_ajax_update_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id   = intval($_POST['id']);
    $name = sanitize_text_field($_POST['name']);

    $wpdb->update(
        "{$wpdb->prefix}ajdwp_templates",
        ['name' => $name],
        ['id'   => $id]
    );

    wp_send_json_success();
}

function ajdwp_apm_ajax_delete_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);

    // Move associated URLs to Default Template (ID 1)
    $wpdb->update("{$wpdb->prefix}ajdwp_template_urls", [
        'template_id' => 1
    ], ['template_id' => $id]);

    // Optionally clean up associated selectors
    $wpdb->delete("{$wpdb->prefix}ajdwp_template_selectors", ['template_id' => $id]);

    // Delete template itself
    $wpdb->delete("{$wpdb->prefix}ajdwp_templates", ['id' => $id]);

    wp_send_json_success();
}

// ============================
// AJAX: Template URL CRUD
// ============================
add_action('wp_ajax_ajdwp_add_template_url', 'ajdwp_apm_ajax_add_template_url');
add_action('wp_ajax_ajdwp_delete_template_url', 'ajdwp_apm_ajax_delete_template_url');

function ajdwp_apm_ajax_add_template_url()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $template_id = intval($_POST['template_id']);
    $url         = esc_url_raw($_POST['url']);

    $wpdb->insert("{$wpdb->prefix}ajdwp_template_urls", [
        'template_id' => $template_id,
        'product_url' => $url
    ]);

    wp_send_json_success([
        'id'  => $wpdb->insert_id,
        'url' => $url
    ]);
}

function ajdwp_apm_ajax_delete_template_url()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);
    $wpdb->delete("{$wpdb->prefix}ajdwp_template_urls", ['id' => $id]);

    wp_send_json_success();
}
