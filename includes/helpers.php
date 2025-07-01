<?php

//_____________________________________ helpers.php _____________________________________//

// ============================
// 🔍 Product Existence & Lookup
// ============================

/**
 * Check if a WooCommerce product already exists using its source URL.
 *
 * @param string $url Product source URL.
 * @return bool True if a product with that URL exists, false otherwise.
 */
function ajdwp_apm_is_duplicate($url)
{
    if (empty($url)) return false;

    $query = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'any',
        'meta_key'       => '_ajdwp_source_url',
        'meta_value'     => esc_url_raw($url),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

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

    $query = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'any',
        'meta_key'       => '_ajdwp_source_url',
        'meta_value'     => esc_url_raw($url),
        'fields'         => 'ids',
        'posts_per_page' => 1,
    ]);

    return !empty($query->posts) ? $query->posts[0] : false;
}

/**
 * Get the image preview by scraping the product page using the 'static' method.
 *
 * @param string $url Product page URL.
 * @return string Image URL or empty string if not found.
 */
function ajdwp_apm_get_image_preview_from_url($url)
{
    if (empty($url)) return '';

    $data = ajdwp_apm_scrape_product_data($url, [], [], 'static');
    return isset($data['image']) ? esc_url_raw($data['image']) : '';
}


// ============================
// 📄 Template Helpers
// ============================

/**
 * Get template selectors by template ID.
 *
 * @param int $template_id
 * @return array Associative array of selectors (or empty array on failure)
 */
function ajdwp_apm_get_template_selectors($template_id)
{
    global $wpdb;

    $template = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d", $template_id)
    );

    if (!$template) return [];

    return [
        'title_selector'             => $template->title_selector             ?? '',
        'short_description_selector' => $template->short_description_selector ?? '',
        'long_description_selector'  => $template->long_description_selector  ?? '',
        'main_image_selector'        => $template->main_image_selector        ?? '',
        'gallery_image_selectors'    => $template->gallery_image_selectors    ?? '',
        'price_selector'             => $template->price_selector             ?? '',
        'price_multiplier'           => $template->price_multiplier           ?? '',
    ];
}


/**
 * Get scraped product data using template selectors and URL.
 *
 * @param int $template_id Template ID
 * @param string $product_url Product source URL
 * @param string $scrape_method Scraping method ('auto', 'static', etc.)
 * @return array|false Scraped data array or false if template not found or scraping fails
 */
function ajdwp_get_scraped_data_by_template($template_id, $product_url, $scrape_method = 'auto')
{
    $selectors = ajdwp_apm_get_template_selectors($template_id);
    if (empty($selectors)) return false;

    return ajdwp_apm_scrape_product_data($product_url, $selectors, [], $scrape_method);
}
