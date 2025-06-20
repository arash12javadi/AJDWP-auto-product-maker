<?php

// These should be at the top of the file, outside the function:
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function ajdwp_apm_create_product($data, $existing_id = null)
{
    // ✅ Check if updating or creating
    if ($existing_id) {
        $product = wc_get_product($existing_id);
        if (!$product || !($product instanceof WC_Product)) {
            return 0; // fallback if invalid
        }
    } else {
        $product = new WC_Product_Simple();
    }

    // ✅ Set product details
    $product->set_name($data['title'] ?? 'Untitled');
    $product->set_regular_price($data['price'] ?? '0.00');
    $product->set_short_description($data['short_description'] ?? '');
    $product->set_description($data['long_description'] ?? '');
    $product->set_catalog_visibility('visible');
    $product->set_status('publish');
    $product->save();

    // ✅ Save or update source URL to avoid duplicates
    update_post_meta($product->get_id(), '_ajdwp_source_url', esc_url_raw($data['source_url']));

    // ✅ Attach featured image
    $image_url = $data['image'] ?? '';
    if (!empty($image_url)) {
        $media_id = media_sideload_image($image_url, $product->get_id(), null, 'id');
        if (!is_wp_error($media_id)) {
            $product->set_image_id($media_id);
            $product->save(); // Save again after setting image
        }
    }

    return $product->get_id();
}
