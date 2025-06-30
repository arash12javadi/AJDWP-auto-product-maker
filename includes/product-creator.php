<?php
//_____________________________________ product-creator.php _____________________________________//
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function ajdwp_apm_create_product($data, $existing_id = null)
{
    if ($existing_id) {
        $product = wc_get_product($existing_id);
        if (!$product || !($product instanceof WC_Product)) {
            return 0;
        }
    } else {
        $product = new WC_Product_Simple();
    }

    // 🔢 Price logic
    $price          = floatval($data['price'] ?? 0.00);             // scraped discounted price
    $price_regular  = floatval($data['price_regular'] ?? 0.00);     // scraped regular price
    $final_price    = floatval($data['final_price'] ?? $price);     // user-chosen final (e.g. calculated)

    // 🧠 Always save as SALE price
    if ($price_regular > $final_price) {
        $product->set_regular_price($price_regular);
        $product->set_sale_price($final_price);
        $product->set_price($final_price);
    } else {
        // If regular is missing or same/less, just set final_price as base & sale
        $product->set_regular_price($final_price);
        $product->set_sale_price($final_price);
        $product->set_price($final_price);
    }

    // 🏷️ Basic details
    $product->set_name($data['title'] ?? 'Untitled');
    $product->set_short_description($data['short_description'] ?? '');
    $product->set_description($data['long_description'] ?? '');
    $product->set_catalog_visibility('visible');
    $product->set_status('publish');

    // 💾 Save initial product
    $product->save();

    // 🔗 Source URL tracking
    if (!empty($data['source_url'])) {
        update_post_meta($product->get_id(), '_ajdwp_source_url', esc_url_raw($data['source_url']));
    }

    // 🖼️ Featured Image
    if (!empty($data['image'])) {
        $media_id = ajdwp_apm_sideload_image($data['image'], $product->get_id());
        if ($media_id) {
            $product->set_image_id($media_id);
            $product->save();
        }
    }

    // 🖼️ Gallery Images
    if (!empty($data['gallery']) && is_array($data['gallery'])) {
        $gallery_ids = [];

        foreach ($data['gallery'] as $url) {
            $id = ajdwp_apm_sideload_image($url, $product->get_id());
            if ($id) {
                $gallery_ids[] = $id;
            }
        }

        if (!empty($gallery_ids)) {
            $product->set_gallery_image_ids($gallery_ids);
            $product->save();
        }
    }

    // ✅ Store in your plugin's template_urls table if source_url exists
    if (!empty($data['source_url'])) {
        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}ajdwp_template_urls",
            [
                'wc_product_id' => $product->get_id(),
                'title' => sanitize_text_field(wp_unslash(html_entity_decode($data['title'] ?? ''))),
                'price'         => sanitize_text_field($data['final_price'] ?? ''),
                'image'         => esc_url_raw($data['image'] ?? ''),
                'last_scraped'  => current_time('mysql'),
            ],
            ['product_url' => esc_url_raw($data['source_url'])]
        );
    }

    return $product->get_id();
}


function ajdwp_apm_sideload_image($url, $post_id)
{
    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        error_log('Image download failed: ' . $tmp->get_error_message());
        return false;
    }

    $file_array = [
        'name'     => basename($url),
        'tmp_name' => $tmp,
    ];

    $media_id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($media_id)) {
        error_log('Image sideload error: ' . $media_id->get_error_message());
        return false;
    }

    return $media_id;
}
