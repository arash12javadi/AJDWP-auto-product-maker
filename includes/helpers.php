<?php
//_____________________________________ helpers.php _____________________________________//
if (!defined('ABSPATH')) exit;

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
// 📄 Template selectors & Helpers
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
        'title'             => $template->title_selector ?? '',
        'short_description' => $template->short_description_selector ?? '',
        'long_description'  => $template->long_description_selector ?? '',
        'image'             => $template->main_image_selector ?? '',
        'gallery'           => $template->gallery_image_selectors ?? '',
        'price'             => $template->price_selector ?? '',
        'price_calc'        => $template->price_multiplier ?? '',
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

    // error_log("🧪 Template ID: $template_id");
    // error_log("🧪 Title selector: " . ($selectors['title_selector'] ?? '—'));
    // error_log("🧪 Price selector: " . ($selectors['price_selector'] ?? '—'));

    return ajdwp_apm_scrape_product_data($product_url, $selectors, [], $scrape_method);
}


//==========================
//  ai-helper
//==========================

function ajdwp_refine_with_ai($prompt)
{
    $api_key = get_option('ajdwp_openai_api_key');
    if (!$api_key) return false;

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model'    => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert copywriter and SEO specialist.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 500,
        ]),
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) return false;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['message']['content'] ?? false;
}
