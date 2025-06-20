<?php

require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';

function ajdwp_apm_scrape_product_data($url, $selectors = [])
{
    $html = @file_get_contents($url);
    if (!$html) return false;

    $dom = str_get_html($html);
    if (!$dom) return false;

    // Initialize fields
    $title = '';
    $short = '';
    $long = '';
    $image = '';
    $gallery = [];
    $price = '0.00';

    // ✅ Scrape Title
    if (!empty($selectors['title'])) {
        $el = $dom->find($selectors['title'], 0);
        $title = $el ? trim($el->plaintext) : '';
    }

    // ✅ Scrape Short Description
    if (!empty($selectors['short_description'])) {
        $el = $dom->find($selectors['short_description'], 0);
        $short = $el ? trim($el->plaintext) : '';
    }

    // ✅ Scrape Long Description
    if (!empty($selectors['long_description'])) {
        $el = $dom->find($selectors['long_description'], 0);
        $long = $el ? trim($el->innertext) : '';
    }

    // ✅ Scrape Main Image
    if (!empty($selectors['image'])) {
        $el = $dom->find($selectors['image'], 0);
        $image = $el ? trim($el->src) : '';
    }

    // ✅ Scrape Gallery Images (Multiple Selectors Supported)
    if (!empty($selectors['gallery'])) {
        $gallery_selectors = explode(',', $selectors['gallery']);
        foreach ($gallery_selectors as $selector) {
            foreach ($dom->find(trim($selector)) as $img) {
                if (!empty($img->src)) {
                    $gallery[] = trim($img->src);
                }
            }
        }
        // Remove duplicates
        $gallery = array_unique($gallery);
    }

    // ✅ Scrape Price + Optional Calculation
    if (!empty($selectors['price'])) {
        $el = $dom->find($selectors['price'], 0);
        $price_raw = $el ? trim($el->plaintext) : '';
        $price_clean = floatval(preg_replace('/[^0-9\.]/', '', $price_raw));

        // Apply price multiplier or addition
        $calc = $selectors['price_calc'] ?? '';
        if ($calc) {
            if (str_starts_with($calc, 'x')) {
                $multiplier = floatval(substr($calc, 1));
                $price_clean *= $multiplier;
            } elseif (str_starts_with($calc, '+')) {
                $addition = floatval(substr($calc, 1));
                $price_clean += $addition;
            }
        }

        $price = number_format($price_clean, 2, '.', '');
    }

    return [
        'title' => $title,
        'short_description' => $short,
        'long_description' => $long,
        'image' => $image,
        'gallery' => $gallery,
        'price' => $price ?: '0.00',
        'source_url' => $url
    ];
}
