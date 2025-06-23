<?php

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

    $price         = $data['price'] ?? '0.00';
    $price_regular = $data['price_regular'] ?? '';
    $final_price   = $price;
    $has_sale      = !empty($price_regular) && $price_regular !== $price;

    $product->set_name($data['title'] ?? 'Untitled');
    $product->set_price($final_price);
    $product->set_regular_price($has_sale ? $price_regular : $price);
    if ($has_sale) {
        $product->set_sale_price($price);
    }

    $product->set_short_description($data['short_description'] ?? '');
    $product->set_description($data['long_description'] ?? '');
    $product->set_catalog_visibility('visible');
    $product->set_status('publish');
    $product->save();

    update_post_meta($product->get_id(), '_ajdwp_source_url', esc_url_raw($data['source_url']));

    // ✅ Upload and set featured image
    $image_url = $data['image'] ?? '';
    if (!empty($image_url)) {
        $media_id = ajdwp_apm_sideload_image($image_url, $product->get_id());
        if ($media_id) {
            $product->set_image_id($media_id);
            $product->save();
        }
    }

    // ✅ Upload and set gallery images
    $gallery_urls = $data['gallery'] ?? [];
    if (!empty($gallery_urls) && is_array($gallery_urls)) {
        $gallery_ids = [];

        foreach ($gallery_urls as $gallery_url) {
            $gallery_id = ajdwp_apm_sideload_image($gallery_url, $product->get_id());
            if ($gallery_id) {
                $gallery_ids[] = $gallery_id;
            }
        }

        if (!empty($gallery_ids)) {
            $product->set_gallery_image_ids($gallery_ids);
            $product->save();
        }
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
