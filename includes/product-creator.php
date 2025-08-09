<?php
//_____________________________________ product-creator.php _____________________________________//
defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/**
 * Find an existing attachment by its original source URL (hash).
 */
function ajdwp_apm_find_existing_attachment_by_source($url)
{
    $hash = md5($url);
    $q = new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [[
            'key'   => '_ajdwp_source_image_url_hash',
            'value' => $hash,
        ]],
    ]);
    return $q->have_posts() ? (int) $q->posts[0] : 0;
}

/**
 * Ensure attachment metadata (sizes) exists.
 */
function ajdwp_apm_generate_attachment_meta($attachment_id)
{
    $filepath = get_attached_file($attachment_id);
    if (!$filepath || !file_exists($filepath)) return;
    $meta = wp_generate_attachment_metadata($attachment_id, $filepath);
    if (!empty($meta)) {
        wp_update_attachment_metadata($attachment_id, $meta);
    }
}


// Replaced with a custom function that uses the WP HTTP API with browser-like headers
// This allows us to use the sideload function with this:
function ajdwp_apm_sideload_image_with_headers($image_url, $post_id, $alt_text = '', $referer = '')
{
    $image_url = esc_url_raw($image_url);
    if (!$image_url) return 0;

    // Reuse if we already have this URL
    if (function_exists('ajdwp_apm_find_existing_attachment_by_source')) {
        $existing = ajdwp_apm_find_existing_attachment_by_source($image_url);
        if ($existing) {
            wp_update_post(['ID' => $existing, 'post_parent' => (int)$post_id]);
            if (!get_post_meta($existing, '_ajdwp_source_image_url', true)) {
                update_post_meta($existing, '_ajdwp_source_image_url', $image_url);
            }
            if (!get_post_meta($existing, '_ajdwp_source_image_url_hash', true)) {
                update_post_meta($existing, '_ajdwp_source_image_url_hash', md5($image_url));
            }
            return (int) $existing;
        }
    }

    $tmp = wp_tempnam($image_url);
    if (!$tmp) {
        error_log('[AJDWP] wp_tempnam failed for ' . $image_url);
        return 0;
    }

    $args = [
        'timeout'     => 30,
        'redirection' => 5,
        'headers'     => [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123 Safari/537.36',
            'Referer'         => $referer ?: home_url('/'),
            'Accept'          => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language' => 'en-GB,en;q=0.9',
            'Cache-Control'   => 'no-cache',
        ],
        'stream'      => true,
        'filename'    => $tmp,
    ];

    $response = wp_safe_remote_get($image_url, $args);
    $ok_stream = false;
    $content_type = '';

    if (!is_wp_error($response)) {
        $code = (int) wp_remote_retrieve_response_code($response);
        $content_type = (string) wp_remote_retrieve_header($response, 'content-type');
        $filesize = @filesize($tmp);
        if ($code >= 200 && $code < 300 && $filesize && $filesize > 0) {
            $ok_stream = true;
        } else {
            error_log(sprintf('[AJDWP] HTTP %s or empty stream (size=%s) for %s', $code ?: 'ERR', $filesize ?: 0, $image_url));
        }
    } else {
        error_log('[AJDWP] HTTP fetch error: ' . $response->get_error_message() . ' | ' . $image_url);
    }

    if (!$ok_stream) {
        @unlink($tmp);
        $tmp = download_url($image_url, 30);
        if (is_wp_error($tmp)) {
            error_log('[AJDWP] download_url failed: ' . $tmp->get_error_message() . ' | ' . $image_url);
            return 0;
        }
    }

    // Build filename; if URL lacks/has wrong extension, derive from content-type
    $path_part = parse_url($image_url, PHP_URL_PATH);
    $name = $path_part ? basename($path_part) : ('image-' . wp_generate_password(8, false));
    $name = sanitize_file_name($name);

    // Ensure extension from MIME if needed
    $ft = wp_check_filetype($name);
    if ((!$ft['ext'] || !$ft['type']) && $content_type) {
        $from_mime = wp_get_mime_types();
        // crude map; WordPress core has helpers in newer versions
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
            'image/tiff' => 'tif',
            'image/avif' => 'avif',
        ];
        $ext = isset($map[$content_type]) ? $map[$content_type] : 'jpg';
        if (!preg_match('/\.' . preg_quote($ext, '/') . '$/i', $name)) {
            $name .= '.' . $ext;
        }
    }

    $file_array = [
        'name'     => $name,
        'tmp_name' => $tmp,
    ];
    if ($content_type) {
        $file_array['type'] = $content_type; // helps WP accept webp/avif/etc.
    }

    $attach_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        error_log('[AJDWP] media_handle_sideload error: ' . $attach_id->get_error_message() . ' | ' . $image_url);
        return 0;
    }

    // Title, ALT
    $title = preg_replace('/\.[a-z0-9]+$/i', '', $name);
    wp_update_post([
        'ID'          => $attach_id,
        'post_title'  => ucwords(str_replace(['-', '_'], ' ', $title)),
        'post_parent' => (int) $post_id,
    ]);
    if ($alt_text) {
        update_post_meta($attach_id, '_wp_attachment_image_alt', wp_strip_all_tags($alt_text));
    }

    // IMPORTANT: write source metas so your dedupe works
    update_post_meta($attach_id, '_ajdwp_source_image_url', $image_url);
    update_post_meta($attach_id, '_ajdwp_source_image_url_hash', md5($image_url));

    // Generate attachment metadata/sizes
    $filepath = get_attached_file($attach_id);
    if ($filepath && file_exists($filepath)) {
        $meta = wp_generate_attachment_metadata($attach_id, $filepath);
        if (!empty($meta)) wp_update_attachment_metadata($attach_id, $meta);
    }

    return (int) $attach_id;
}




/**
 * Unique URLs, limit gallery length.
 */
function ajdwp_apm_unique_image_urls(array $urls, $limit = 12)
{
    $seen = [];
    $out  = [];
    foreach ($urls as $u) {
        $u = trim($u);
        if (!$u) continue;
        if (isset($seen[$u])) continue;
        $seen[$u] = true;
        $out[] = $u;
        if ($limit && count($out) >= $limit) break;
    }
    return $out;
}

/**
 * MAIN: Create or update a simple product and attach images.
 * Expects $data keys: title, short_description, long_description, price, price_regular, final_price, image, gallery[], source_url
 */
function ajdwp_apm_create_product($data, $existing_id = null, $ai_mode = 'ai-all')
{
    // Optional AI step (yours)
    if (function_exists('ajdwp_apply_ai_refinement')) {
        $data = ajdwp_apply_ai_refinement($data, $ai_mode);
    }

    // Create/load product
    if ($existing_id) {
        $product = wc_get_product($existing_id);
        if (!$product || !($product instanceof WC_Product)) return 0;
    } else {
        $product = new WC_Product_Simple();
        // Ensure product type is set (helps some admin UIs)
        wp_set_object_terms($product->get_id(), 'simple', 'product_type');
    }

    // Prices
    $price         = floatval($data['price'] ?? 0.00);
    $price_regular = floatval($data['price_regular'] ?? 0.00);
    $final_price   = floatval($data['final_price'] ?? $price);

    if ($price_regular > $final_price) {
        $product->set_regular_price($price_regular);
        $product->set_sale_price($final_price);
        $product->set_price($final_price);
    } else {
        $product->set_regular_price($final_price);
        $product->set_sale_price($final_price);
        $product->set_price($final_price);
    }

    // Core fields
    $product->set_name($data['title'] ?? 'Untitled');
    $product->set_short_description($data['short_description'] ?? '');
    $product->set_description($data['long_description'] ?? '');
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    if (method_exists($product, 'set_stock_status')) {
        $product->set_stock_status('instock');
    }

    // Save to get an ID
    $product->save();
    $product_id = $product->get_id();
    wp_set_object_terms($product_id, 'simple', 'product_type');

    // Track source
    if (!empty($data['source_url'])) {
        update_post_meta($product_id, '_ajdwp_source_url', esc_url_raw($data['source_url']));
    }

    /**
     * IMAGES
     */
    $featured_url = isset($data['image']) ? trim($data['image']) : '';
    $gallery_urls = (isset($data['gallery']) && is_array($data['gallery'])) ? $data['gallery'] : [];
    $gallery_urls = ajdwp_apm_unique_image_urls(array_map('trim', $gallery_urls), 20);

    // Use product page as Referer (critical for ?itok=... images)
    $referer = !empty($data['source_url']) ? $data['source_url'] : home_url('/');

    // If no featured but we have gallery, promote the first gallery item to featured
    if (!$featured_url && !empty($gallery_urls)) {
        $featured_url = array_shift($gallery_urls);
    }

    // ---- Attach FEATURED first, then save ----
    $featured_id = 0;
    if ($featured_url) {
        $featured_id = ajdwp_apm_sideload_image_with_headers($featured_url, $product_id, $data['title'] ?? '', $referer);
        if ($featured_id) {
            // Woo & legacy/core
            $product->set_image_id($featured_id);
            set_post_thumbnail($product_id, $featured_id);
            update_post_meta($product_id, '_thumbnail_id', $featured_id);

            $product->save(); // persist before gallery
        } else {
            error_log('[AJDWP] Failed to attach featured image for product ' . $product_id . ' | ' . $featured_url);
        }
    }


    // ---- Attach GALLERY, skipping the featured by ID ----
    $gallery_ids = [];
    foreach ($gallery_urls as $u) {
        $aid = ajdwp_apm_sideload_image_with_headers($u, $product_id, $data['title'] ?? '', $referer);
        if ($aid && $aid !== $featured_id) {
            $gallery_ids[] = (int) $aid;
        }
    }

    if (!empty($gallery_ids)) {
        // Deduplicate any accidental repeats by ID
        $gallery_ids = array_values(array_unique(array_map('intval', $gallery_ids)));

        $product->set_gallery_image_ids($gallery_ids);
        update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids)); // legacy meta
        $product->save();
    } else {
        delete_post_meta($product_id, '_product_image_gallery');
    }


    // Persist all changes
    $product->save();

    // Clear transients so frontend picks up images immediately
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($product_id);
    }

    // Update your custom table link (optional)
    if (!empty($data['source_url'])) {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}ajdwp_template_urls",
            [
                'wc_product_id' => $product_id,
                'title'         => sanitize_text_field(wp_unslash(html_entity_decode($data['title'] ?? ''))),
                'price'         => sanitize_text_field($data['final_price'] ?? ''),
                'image'         => esc_url_raw($featured_url ?: ($data['image'] ?? '')),
                'last_scraped'  => current_time('mysql'),
            ],
            ['product_url' => esc_url_raw($data['source_url'])]
        );
    }

    return $product_id;
}
