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
    file_put_contents(AJDWPAPM_PATH . 'debug-rendered.html', $output);

    return $output;
}


/**
 * Parses product data from rendered HTML using provided selectors.
 *
 * @param string $url Page URL
 * @param array $selectors Custom CSS selectors for each field
 * @param array $skip_fields List of fields to skip
 * @return array|false Associative array of product data or false on failure
 */
function ajdwp_apm_scrape_product_data($url, $selectors = [], $skip_fields = [])
{
    $html = ajdwp_apm_get_rendered_html($url);
    if (!$html) return false;

    $dom = str_get_html($html);
    if (!$dom) return false;

    // Default selectors for WooCommerce/OpenGraph/HTML5
    $defaults = [
        'title'             => 'meta[property="og:title"]',
        'price'             => 'div.summary.entry-summary p ins span bdi, span.woocommerce-Price-amount bdi',
        'short_description' => 'meta[name="description"], meta[property="og:description"]',
        'long_description'  => 'div.woocommerce-Tabs-panel--description, div.product-description, div#tab-description',
        'image'             => 'div.woocommerce-product-gallery__image.flex-active-slide a',
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

        // 📸 Handle gallery images (high-resolution)
        if ($field === 'gallery') {
            $gallery_images = [];
            foreach ($found_elements as $img) {
                $high_res = $img->getAttribute('data-large_image') ??
                    $img->getAttribute('data-src') ??
                    $img->getAttribute('src');

                // Optional: Skip thumbnails like -150x150.jpg
                if (!empty($high_res) && strpos($high_res, '-150x150') === false) {
                    $gallery_images[] = $high_res;
                }
            }
            $data[$field] = $gallery_images;
            continue;
        }

        // 🔍 Process single element fields
        $found = $found_elements[0] ?? null;

        if ($found) {
            if (str_contains($actual_selector, 'meta[')) {
                $data[$field] = $found->content ?? '';
            } elseif ($field === 'image') {
                $data[$field] = $found->href ?? $found->src ?? '';
            } elseif ($field === 'price') {
                // 🏷 Extract both sale and original prices
                $ins_price_el = $dom->find('p.price ins span bdi', 0);
                $del_price_el = $dom->find('p.price del span bdi', 0);

                $extract_price = function ($el) {
                    if (!$el) return '';
                    $text = strip_tags($el->innertext ?? '');
                    $text = preg_replace('/[^\d.,]/', '', $text);
                    return str_replace(',', '.', trim($text));
                };

                $data['price'] = $extract_price($ins_price_el);         // Discounted (used by default)
                $data['price_regular'] = $extract_price($del_price_el); // Original (optional for display)
            } else {
                $data[$field] = trim($found->plaintext ?? '');
            }
        } else {
            $data[$field] = '';
        }
    }

    $data['source_url'] = $url;
    return $data;
}
