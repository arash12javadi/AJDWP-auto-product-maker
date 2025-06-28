<?php

/**
 * Check if a WooCommerce product already exists using its source URL.
 *
 * @param string $url Product source URL.
 * @return bool True if a product with that URL exists, false otherwise.
 */
function ajdwp_apm_is_duplicate($url)
{
    if (empty($url)) return false;

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'any',
        'meta_key'       => '_ajdwp_source_url',
        'meta_value'     => esc_url_raw($url),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ];

    $query = new WP_Query($args);
    return !empty($query->posts);
}

/**
 * Get the existing WooCommerce product ID from a source URL.
 *
 * @param string $url Product source URL.
 * @return int|false Product ID or false if not found.
 */
function ajdwp_apm_get_existing_product_id($url)
{
    if (empty($url)) return false;

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'any',
        'meta_key'       => '_ajdwp_source_url',
        'meta_value'     => esc_url_raw($url),
        'fields'         => 'ids',
        'posts_per_page' => 1,
    ];

    $query = new WP_Query($args);
    return !empty($query->posts) ? $query->posts[0] : false;
}

/**
 * Attempt to extract a product's image URL from a live scrape for preview use.
 * (Used when listing product URLs under a template.)
 *
 * @param string $url Product page URL.
 * @return string Image URL (or empty string if not found).
 */
function ajdwp_apm_get_image_preview_from_url($url)
{
    if (empty($url)) return '';

    // Optional: Add transient caching here for performance if needed
    $data = ajdwp_apm_scrape_product_data($url, [], [], 'static'); // Use 'static' for speed
    return isset($data['image']) ? esc_url_raw($data['image']) : '';
}

// ============================
// AJAX: Get Template Selectors
// ============================

function ajdwp_apm_get_template_selectors($template_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'ajdwp_selectors';
    $selectors = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE template_id = %d", $template_id), ARRAY_A);

    return $selectors ?: [];
}
