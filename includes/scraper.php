<?php
//_____________________________________ scraper.php _____________________________________//

require_once AJDWPAPM_PATH . 'includes/simple_html_dom.php';

/**
 * -------------------------------
 * Image helpers
 * -------------------------------
 */

function ajdwp_apm_choose_from_srcset($srcset)
{
    if (!$srcset) return null;

    $best_url   = null;
    $best_width = -1;

    foreach (explode(',', $srcset) as $entry) {
        $entry = trim($entry);
        if (preg_match('~\s+(\d+)w$~i', $entry, $m)) {
            $w   = (int) $m[1];
            $url = trim(substr($entry, 0, -strlen($m[0])));
            if ($w > $best_width) {
                $best_width = $w;
                $best_url   = $url;
            }
        } else {
            $best_url = $entry; // single URL
        }
    }
    return $best_url;
}

function ajdwp_apm_pick_best_image_url($node)
{
    if (!$node) return null;

    $candidates = [
        'data-zoom-image',
        'data-image',
        'data-large',
        'data-large_image',
        'data-full-image',
        'data-original',
        'data-srcset',
        'data-src',
        'srcset',
        'src',
        'href',
    ];

    foreach ($candidates as $attr) {
        $val = $node->getAttribute($attr);
        if ($val) {
            if ($attr === 'srcset' || $attr === 'data-srcset') {
                $chosen = ajdwp_apm_choose_from_srcset($val);
                if ($chosen) return $chosen;
            } else {
                return $val;
            }
        }
    }

    if ($node->tag === 'picture') {
        foreach ($node->find('source') as $source) {
            $chosen = ajdwp_apm_choose_from_srcset($source->getAttribute('srcset'));
            if ($chosen) return $chosen;
        }
        $img = $node->find('img', 0);
        if ($img) return ajdwp_apm_pick_best_image_url($img);
    }

    if ($node->tag !== 'img') {
        $img = $node->find('img', 0);
        if ($img) return ajdwp_apm_pick_best_image_url($img);
    }

    return null;
}

function ajdwp_apm_looks_like_image($url)
{
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|tiff?)$/i', $path);
}

function ajdwp_apm_normalize_url($maybe_relative, $page_url)
{
    if (empty($maybe_relative)) return '';

    if (stripos($maybe_relative, 'http://') === 0 || stripos($maybe_relative, 'https://') === 0) {
        return $maybe_relative;
    }

    if (strpos($maybe_relative, '//') === 0) {
        return (parse_url($page_url, PHP_URL_SCHEME) ?: 'https') . ':' . $maybe_relative;
    }

    $scheme = parse_url($page_url, PHP_URL_SCHEME) ?: 'https';
    $host   = parse_url($page_url, PHP_URL_HOST) ?: '';
    return $scheme . '://' . $host . '/' . ltrim($maybe_relative, '/');
}

/**
 * OPTIONAL: Drupal style upgrader (used ONLY for the single 'image' field below).
 * We DO NOT use this in the gallery to avoid filename swaps like _3_ → _1_.
 */
function ajdwp_apm_try_upgrade_drupal_style($url)
{
    $pairs = [
        '/styles/product_image_thumbnail/' => '/styles/product_image_large/',
        '/styles/thumbnail/'               => '/styles/large/',
    ];
    foreach ($pairs as $from => $to) {
        if (strpos($url, $from) !== false) {
            return str_replace($from, $to, $url);
        }
    }
    return $url;
}

/**
 * -------------------------------
 * Rendering (dynamic) via Node/Playwright
 * -------------------------------
 */

function ajdwp_apm_get_rendered_html($url)
{
    $escaped_url = escapeshellarg($url);
    $script_path = AJDWPAPM_PATH . 'assets/js/scraper.js';
    $node_binary = 'C:\\Program Files\\nodejs\\node.exe'; // adjust if needed

    $node_cmd = "\"$node_binary\" " . escapeshellarg($script_path) . " $escaped_url 2>&1";
    $output   = shell_exec($node_cmd);

    if (!$output || strlen($output) < 500) return false;

    if (defined('AJDWPAPM_DEBUG') && AJDWPAPM_DEBUG === true) {
        file_put_contents(AJDWPAPM_PATH . 'debug-rendered.html', $output);
    }

    return $output;
}

/**
 * -------------------------------
 * Top-level scrape orchestrator
 * -------------------------------
 */

function ajdwp_apm_scrape_product_data($url, $selectors = [], $skip_fields = [], $method = 'auto')
{
    $html   = '';
    $parsed = false;

    if ($method === 'static' || $method === 'auto') {
        $html = @file_get_contents($url);
        if (defined('AJDWPAPM_DEBUG') && AJDWPAPM_DEBUG === true && $html) {
            file_put_contents(AJDWPAPM_PATH . 'debug-static.html', $html);
        }
        if ($html && strlen($html) > 100) {
            $parsed = ajdwp_apm_parse_product_html($html, $url, $selectors, $skip_fields);
            if ($parsed && (!empty($parsed['title']) || !empty($parsed['image']) || !empty($parsed['gallery']))) {
                return $parsed;
            }
        }
    }

    if ($method === 'dynamic' || $method === 'auto') {
        $html = ajdwp_apm_get_rendered_html($url);
        if ($html && strlen($html) > 100) {
            $parsed = ajdwp_apm_parse_product_html($html, $url, $selectors, $skip_fields);
            if ($parsed && (!empty($parsed['title']) || !empty($parsed['image']) || !empty($parsed['gallery']))) {
                return $parsed;
            }
        }
    }

    return false;
}

/**
 * -------------------------------
 * HTML parser
 * -------------------------------
 */

function ajdwp_apm_parse_product_html($html, $page_url, $selectors = [], $skip_fields = [])
{
    $dom = str_get_html($html);
    if (!$dom) return false;

    $defaults = [
        'title'             => 'meta[property="og:title"]',
        'price'             => 'div.summary.entry-summary p ins span bdi, span.woocommerce-Price-amount bdi',
        'short_description' => 'meta[name="description"], meta[property="og:description"]',
        'long_description'  => 'div.woocommerce-Tabs-panel--description, div.product-description, div#tab-description',
        'image'             => 'img.wp-post-image, .woocommerce-product-gallery__image img',
        // add Slick patterns
        'gallery'           => 'div.woocommerce-product-gallery__wrapper img, .slick-track img, .slick-slide img, .thumbnail img',
    ];

    $data = [];

    foreach ($defaults as $field => $default_selector) {
        if (in_array($field, $skip_fields, true)) {
            $data[$field] = ($field === 'price') ? 0.00 : (($field === 'gallery') ? [] : '');
            continue;
        }

        $raw_selector     = $selectors[$field] ?? $default_selector;
        $cleaned_selector = preg_replace('/\\\\+/', '', stripslashes(html_entity_decode($raw_selector)));
        $selector_array   = array_filter(array_map('trim', explode(',', $cleaned_selector)));

        $found_elements = [];
        foreach ($selector_array as $sel) {
            $tmp = $dom->find($sel);
            if (!empty($tmp)) {
                $found_elements = $tmp;
                break;
            }
        }

        // ---------------- Gallery: keep EXACT filenames; no Drupal style upgrade here ----------------
        if ($field === 'gallery') {
            $gallery_images = [];

            foreach ($found_elements as $el) {
                $img_url = null;

                // If it's already an <img>, use its src directly to preserve the exact filename (…_1_, …_2_, …_3_)
                if ($el->tag === 'img') {
                    $img_url = $el->getAttribute('src') ?: $el->getAttribute('data-src') ?: null;
                }

                // Otherwise pick best
                if (!$img_url) {
                    $img_url = ajdwp_apm_pick_best_image_url($el);
                }

                // Or fallback to <a href> if it looks like an image
                if (!$img_url && $el->tag === 'a') {
                    $href = $el->getAttribute('href');
                    if ($href && ajdwp_apm_looks_like_image($href)) {
                        $img_url = $href;
                    }
                }

                if ($img_url) {
                    $img_url = ajdwp_apm_normalize_url($img_url, $page_url);

                    // DO NOT upgrade Drupal style for gallery to avoid _3_ → _1_ swaps.
                    // Only reject obvious tiny WP size-suffixed variants like -150x150.jpg
                    if (!preg_match('/-\d{2,4}x\d{2,4}\.(jpe?g|png|gif|webp|bmp|tiff?)$/i', $img_url)) {
                        $gallery_images[] = $img_url;
                    }
                }
            }

            $data[$field] = array_values(array_unique($gallery_images));
            continue;
        }

        // ---------------- Other fields ----------------
        $found = $found_elements[0] ?? null;

        if ($found) {
            if (str_contains($cleaned_selector, 'meta[')) {
                $data[$field] = $found->content ?? '';
            } elseif ($field === 'image') {
                // Single main image: it's OK to upgrade its style (kept separate from gallery)
                $src        = trim($found->getAttribute('src') ?: $found->getAttribute('data-src') ?: '');
                $normalized = ajdwp_apm_normalize_url($src, $page_url);
                $data[$field] = ajdwp_apm_try_upgrade_drupal_style($normalized);
            } elseif ($field === 'price') {
                $ins_el = $dom->find('p.price ins span bdi, .summary .price ins span bdi', 0);
                $del_el = $dom->find('p.price del span bdi, .summary .price del span bdi', 0);

                $extract_clean_price = function ($el) {
                    if (!$el) return 0.00;
                    $text = strip_tags($el->innertext ?? '');
                    $text = preg_replace('/[^0-9.,]/', '', $text);
                    $text = str_replace(',', '.', $text);
                    if (preg_match('/\d+(?:\.\d+)?$/', $text, $m)) {
                        return floatval($m[0]);
                    }
                    return floatval($text);
                };

                $price   = $extract_clean_price($ins_el ?: $dom->find('.summary .price span bdi', 0));
                $regular = $extract_clean_price($del_el);

                $data['price']         = $price;
                $data['price_regular'] = $regular;

                if (!empty($selectors['price_calc']) && is_numeric($price)) {
                    $calc = trim($selectors['price_calc']);
                    $expression = str_replace('price', $price, $calc);
                    if (preg_match('/^[0-9\.\+\-\*\/\(\) ]+$/', $expression)) {
                        try {
                            eval('$adjusted = ' . $expression . ';');
                            if (is_numeric($adjusted)) {
                                $data['price'] = round(floatval($adjusted), 2);
                            }
                        } catch (Throwable $e) {
                        }
                    }
                }
            } else {
                $data[$field] = trim($found->plaintext ?? '');
            }
        } else {
            $data[$field] = ($field === 'price') ? 0.00 : (($field === 'gallery') ? [] : '');
        }
    }

    $data['source_url'] = $page_url;
    return $data;
}
