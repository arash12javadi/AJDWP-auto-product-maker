<?php

require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';

function ajdwp_apm_scrape_product_data($url, $selectors = [], $skip_fields = [])
{
    $html = @file_get_contents($url);
    if (!$html) return false;

    $dom = str_get_html($html);
    if (!$dom) return false;

    // Default fallback selectors
    $defaults = [
        'title'             => 'h1',
        'short_description' => 'div.product-short-description, .woocommerce-product-details__short-description',
        'long_description'  => 'div.product-description, .woocommerce-Tabs-panel--description',
        'image'             => 'img.wp-post-image, .woocommerce-product-gallery__image img',
        'gallery'           => '.woocommerce-product-gallery__image img',
        'price'             => '.price .amount, .woocommerce-Price-amount',
    ];

    // Initialise fields
    $title  = '';
    $short  = '';
    $long   = '';
    $image  = '';
    $gallery = [];
    $price  = '0.00';

    // ✅ Scrape Title
    if (empty($skip_fields['title'])) {
        $selector = $selectors['title'] ?: $defaults['title'];
        $el = $dom->find($selector, 0);
        $title = $el ? trim($el->plaintext) : '';
    }

    // ✅ Scrape Short Description
    if (empty($skip_fields['short_description'])) {
        $selector = $selectors['short_description'] ?: $defaults['short_description'];
        $el = $dom->find($selector, 0);
        $short = $el ? trim($el->plaintext) : '';
    }

    // ✅ Scrape Long Description
    if (empty($skip_fields['long_description'])) {
        $selector = $selectors['long_description'] ?: $defaults['long_description'];
        $el = $dom->find($selector, 0);
        $long = $el ? trim($el->innertext) : '';
    }


    // ✅ Scrape Main Image
    if (empty($skip_fields['image'])) {
        $selector = $selectors['image'] ?: $defaults['image'];
        $el = $dom->find($selector, 0);

        if ($el && !empty($el->src)) {
            $src = trim($el->src);

            // If the src starts with http or https, use it as is
            if (strpos($src, 'http') === 0) {
                $image = $src;
            } else {
                // Convert relative to absolute URL
                $parsed_url = parse_url($url);
                $base = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                $image = $base . '/' . ltrim($src, '/');
            }
        }
    }


    // ✅ Scrape Gallery Images
    if (empty($skip_fields['gallery'])) {
        $selector = $selectors['gallery'] ?: $defaults['gallery'];
        $gallery_selectors = explode(',', $selector);
        foreach ($gallery_selectors as $sel) {
            foreach ($dom->find(trim($sel)) as $img) {
                if (!empty($img->src)) {
                    $gallery[] = trim($img->src);
                }
            }
        }
        $gallery = array_unique($gallery);
    }

    // ✅ Scrape Price
    if (empty($skip_fields['price'])) {
        $selector = $selectors['price'] ?: $defaults['price'];
        $el = $dom->find($selector, 0);
        $price_raw = $el ? trim($el->plaintext) : '';
        $price_clean = floatval(preg_replace('/[^0-9\.]/', '', $price_raw));

        // Apply multiplier or addition
        $calc = $selectors['price_calc'] ?? '';
        if ($calc) {
            if (str_starts_with($calc, 'x')) {
                $price_clean *= floatval(substr($calc, 1));
            } elseif (str_starts_with($calc, '+')) {
                $price_clean += floatval(substr($calc, 1));
            }
        }

        $price = number_format($price_clean, 2, '.', '');
    }

    return [
        'title'             => $title,
        'short_description' => $short,
        'long_description'  => $long,
        'image'             => $image,
        'gallery'           => $gallery,
        'price'             => $price ?: '0.00',
        'source_url'        => $url,
    ];
}
