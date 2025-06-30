<?php
//_____________________________________ scraper.php _____________________________________//

require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';

function ajdwp_apm_get_rendered_html($url)
{
    $escaped_url = escapeshellarg($url);
    $script_path = AJDWPAPM_PATH . 'assets/js/scraper.js';
    $node_binary = 'C:\\Program Files\\nodejs\\node.exe';
    $node_cmd = "\"$node_binary\" " . escapeshellarg($script_path) . " $escaped_url 2>&1";

    $output = shell_exec($node_cmd);
    if (!$output || strlen($output) < 500) return false;

    if (defined('AJDWPAPM_DEBUG') && AJDWPAPM_DEBUG === true) {
        file_put_contents(AJDWPAPM_PATH . 'debug-rendered.html', $output);
    }

    return $output;
}

function ajdwp_apm_scrape_product_data($url, $selectors = [], $skip_fields = [], $method = 'auto')
{
    $html = '';
    $parsed = false;

    if ($method === 'static' || $method === 'auto') {
        $html = @file_get_contents($url);
        if (defined('AJDWPAPM_DEBUG') && AJDWPAPM_DEBUG === true) {
            file_put_contents(AJDWPAPM_PATH . 'debug-static.html', $html);
        }
        if ($html && strlen($html) > 100) {
            $parsed = ajdwp_apm_parse_product_html($html, $url, $selectors, $skip_fields);
            if ($method === 'static' || ($parsed && !empty($parsed['title']))) return $parsed;
        }
    }

    if ($method === 'dynamic' || $method === 'auto') {
        $html = ajdwp_apm_get_rendered_html($url);
        if ($html && strlen($html) > 100) {
            $parsed = ajdwp_apm_parse_product_html($html, $url, $selectors, $skip_fields);
            if ($parsed && !empty($parsed['title'])) return $parsed;
        }
    }

    return false;
}

function ajdwp_apm_parse_product_html($html, $url, $selectors = [], $skip_fields = [])
{
    $dom = str_get_html($html);
    if (!$dom) return false;

    $defaults = [
        'title'             => 'meta[property="og:title"]',
        'price'             => 'div.summary.entry-summary p ins span bdi, span.woocommerce-Price-amount bdi',
        'short_description' => 'meta[name="description"], meta[property="og:description"]',
        'long_description'  => 'div.woocommerce-Tabs-panel--description, div.product-description, div#tab-description',
        'image'             => 'img.wp-post-image, .woocommerce-product-gallery__image img',
        'gallery'           => 'div.woocommerce-product-gallery__wrapper img',
    ];

    $data = [];

    foreach ($defaults as $field => $default_selector) {
        if (in_array($field, $skip_fields, true)) {
            $data[$field] = $field === 'price' ? 0.00 : '';
            continue;
        }

        $raw_selector = $selectors[$field] ?? $default_selector;
        $cleaned_selector = preg_replace('/\\\\+/', '', stripslashes(html_entity_decode($raw_selector)));
        $selector_array = array_filter(array_map('trim', explode(',', $cleaned_selector)));

        $found_elements = [];
        foreach ($selector_array as $sel) {
            $found_elements = $dom->find($sel);
            if (!empty($found_elements)) break;
        }

        if ($field === 'gallery') {
            $gallery_images = [];
            foreach ($found_elements as $img) {
                $high_res = $img->getAttribute('data-large_image') ?? $img->getAttribute('data-src') ?? $img->getAttribute('src');
                if (!empty($high_res) && strpos($high_res, '-150x150') === false) {
                    $gallery_images[] = $high_res;
                }
            }
            $data[$field] = $gallery_images;
            continue;
        }

        $found = $found_elements[0] ?? null;

        if ($found) {
            if (str_contains($cleaned_selector, 'meta[')) {
                $data[$field] = $found->content ?? '';
            } elseif ($field === 'image') {
                $src = trim($found->src ?? '');
                $data[$field] = strpos($src, 'http') === 0
                    ? $src
                    : (parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/' . ltrim($src, '/'));
            } elseif ($field === 'price') {
                $ins_el = $dom->find('p.price ins span bdi', 0);
                $del_el = $dom->find('p.price del span bdi', 0);

                $extract_clean_price = function ($el) {
                    if (!$el) return 0.00;
                    $text = strip_tags($el->innertext ?? '');
                    $text = preg_replace('/[^0-9.,]/', '', $text);
                    $text = str_replace(',', '.', $text);
                    return floatval($text);
                };

                $price = $extract_clean_price($ins_el);
                $regular = $extract_clean_price($del_el);

                $data['price'] = $price;
                $data['price_regular'] = $regular;

                // ➕ Apply price_calc if provided
                if (!empty($selectors['price_calc']) && is_numeric($price)) {
                    $calc = trim($selectors['price_calc']);
                    $expression = str_replace('price', $price, $calc);

                    // Only allow safe characters
                    if (preg_match('/^[0-9\.\+\-\*\/\(\) ]+$/', $expression)) {
                        try {
                            eval('$adjusted = ' . $expression . ';');
                            if (is_numeric($adjusted)) {
                                $data['price'] = round(floatval($adjusted), 2);
                            }
                        } catch (Throwable $e) {
                            // Fail silently
                        }
                    }
                }
            } else {
                $data[$field] = trim($found->plaintext ?? '');
            }
        } else {
            $data[$field] = $field === 'price' ? 0.00 : '';
        }
    }

    $data['source_url'] = $url;
    return $data;
}
