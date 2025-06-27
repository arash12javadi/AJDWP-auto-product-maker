<?php
// Load the HTML parser
require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';

/**
 * Runs the Node.js Playwright script to return fully rendered HTML.
 *
 * @param string $url The page URL to scrape.
 * @return string|false The HTML content or false on failure.
 */
function ajdwp_apm_get_rendered_html($url)
{
    $escaped_url = escapeshellarg($url);
    $script_path = AJDWPAPM_PATH . 'assets/js/scraper.js';
    $node_binary = 'C:\\Program Files\\nodejs\\node.exe'; // Update if your node path differs
    $node_cmd = "\"$node_binary\" " . escapeshellarg($script_path) . " $escaped_url 2>&1";

    $output = shell_exec($node_cmd);

    if (!$output || strlen($output) < 500) {
        error_log("❌ AJDWP: No or insufficient output from scraper.");
        return false;
    }

    // Optional: Save rendered HTML for inspection
    if (defined('AJDWPAPM_DEBUG') && AJDWPAPM_DEBUG === true) {
        file_put_contents(AJDWPAPM_PATH . 'debug-rendered.html', $output);
    }

    return $output;
}


/**
 * Attempts to scrape product data using static HTML first, then falls back to dynamic scraping with Playwright if needed.
 *
 * @param string $url Page URL
 * @param array $selectors Custom CSS selectors
 * @param array $skip_fields Fields to skip
 * @param string $method 'auto' | 'static' | 'dynamic'
 * @return array|false
 */
function ajdwp_apm_scrape_product_data($url, $selectors = [], $skip_fields = [], $method = 'auto')
{
    $html = '';
    $parsed = false;

    // Method 1: Try static HTML scraping
    if ($method === 'static' || $method === 'auto') {
        $html = @file_get_contents($url);
        // file_put_contents(AJDWPAPM_PATH . 'debug-static.html', $html);

        if ($html && strlen($html) > 100) {
            $parsed = ajdwp_apm_parse_product_html($html, $url, $selectors, $skip_fields);
            if ($method === 'static' || ($parsed && !empty($parsed['title']))) {
                return $parsed;
            }
        }
    }

    // Method 2: Fallback to dynamic scraping via Playwright
    if ($method === 'dynamic' || $method === 'auto') {
        $html = ajdwp_apm_get_rendered_html($url);

        if ($html && strlen($html) > 100) {
            $parsed = ajdwp_apm_parse_product_html($html, $url, $selectors, $skip_fields);
            if ($parsed && !empty($parsed['title'])) {
                return $parsed;
            }
        }
    }

    // ❌ Failed
    return false;
}


function ajdwp_apm_parse_product_html($html, $url, $selectors = [], $skip_fields = [])
{
    require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';
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

    foreach ($defaults as $field => $selector) {
        if (in_array($field, $skip_fields, true)) {
            $data[$field] = '';
            continue;
        }

        $actual_selector = !empty($selectors[$field]) ? $selectors[$field] : $selector;
        $found_elements = $dom->find($actual_selector);

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
            if (str_contains($actual_selector, 'meta[')) {
                $data[$field] = $found->content ?? '';
            } elseif ($field === 'image') {
                $src = trim($found->src ?? '');
                if (!empty($src)) {
                    $data[$field] = strpos($src, 'http') === 0 ? $src : (parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/' . ltrim($src, '/'));
                } else {
                    $data[$field] = '';
                }
            } elseif ($field === 'price') {
                // Extract prices
                $ins_el = $dom->find('p.price ins span bdi', 0);
                $del_el = $dom->find('p.price del span bdi', 0);

                $extract_clean_price = function ($el) {
                    if (!$el) return 0.00;
                    $text = strip_tags($el->innertext ?? '');
                    $text = preg_replace('/[^0-9.,]/', '', $text);
                    $text = str_replace(',', '.', $text); // optional for EU-style prices
                    return floatval($text);
                };

                $data['price'] = $extract_clean_price($ins_el);
                $data['price_regular'] = $extract_clean_price($del_el);
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
