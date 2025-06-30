<?php
//_____________________________________ tab1-product-scrape-form-ajax.php _____________________________________//

require_once AJDWPAPM_PATH . 'includes/scraper.php';
require_once AJDWPAPM_PATH . 'includes/product-creator.php';

// ============================
// AJAX: Load Templates defaults on scraping form on the tab 1
// ============================

add_action('wp_ajax_ajdwp_get_template_data', 'ajdwp_get_template_data_callback');

function ajdwp_get_template_data_callback()
{
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $template_id = intval($_POST['template_id']);
    $table = $wpdb->prefix . 'ajdwp_templates';

    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $template_id));

    if ($template) {
        wp_send_json_success($template);
    } else {
        wp_send_json_error(['message' => 'Template not found']);
    }
}

// ============================
// AJAX: Bulk Add or Update Product URLs
// ============================
add_action('wp_ajax_ajdwp_bulk_add_product_urls', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $tpl_id   = intval($_POST['template_id'] ?? 0);
    $raw_urls = $_POST['urls'] ?? [];
    $table    = $wpdb->prefix . 'ajdwp_template_urls';

    if (!$tpl_id || !is_array($raw_urls)) {
        wp_send_json_error(['message' => 'Invalid input.']);
    }

    $inserted = 0;
    $updated  = 0;
    $errors   = 0;

    // load template once
    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
        $tpl_id
    ));
    $selectors = [
        'title_selector'             => $template->title_selector             ?? '',
        'short_description_selector' => $template->short_description_selector ?? '',
        'long_description_selector'  => $template->long_description_selector  ?? '',
        'main_image_selector'        => $template->main_image_selector        ?? '',
        'gallery_image_selectors'    => $template->gallery_image_selectors    ?? '',
        'price_selector'             => $template->price_selector             ?? '',
        'price_multiplier'           => $template->price_multiplier           ?? '',
    ];

    foreach ($raw_urls as $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            $errors++;
            continue;
        }
        // sanitize
        $url = esc_url_raw($raw) ?: sanitize_text_field($raw);

        // check existing record
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE template_id = %d AND product_url = %s",
            $tpl_id,
            $url
        ));

        // scrape data
        $data = ajdwp_apm_scrape_product_data(
            $url,
            $selectors,
            [],
            $template->scrape_method ?? 'auto'
        );
        if (!$data || empty($data['title'])) {
            $errors++;
            continue;
        }

        if ($row) {
            // —— UPDATE existing URL record & product —— //
            // determine existing WC product
            $existing_id = intval($row->wc_product_id);
            // create or update product
            $prod_id = ajdwp_apm_create_product($data, $existing_id);
            if (!$prod_id) {
                $errors++;
                continue;
            }
            // update your plugin table row
            $wpdb->update(
                $table,
                [
                    'wc_product_id' => $prod_id,
                    'title'         => sanitize_text_field($data['title']),
                    'price'         => sanitize_text_field($data['final_price'] ?? $data['price']),
                    'image'         => esc_url_raw($data['image'] ?? ''),
                    'last_scraped'  => current_time('mysql'),
                ],
                ['id' => $row->id]
            );
            $updated++;
        } else {
            // —— INSERT new URL record & product —— //
            $wpdb->insert($table, [
                'template_id'  => $tpl_id,
                'product_url'  => $url,
                'last_scraped' => current_time('mysql'),
            ]);
            $inserted++;
            // create product
            $new_id = ajdwp_apm_create_product($data);
            if ($new_id) {
                // store new WC ID back on that row
                $wpdb->update(
                    $table,
                    ['wc_product_id' => $new_id],
                    ['template_id' => $tpl_id, 'product_url' => $url]
                );
            }
        }
    }

    wp_send_json_success([
        'inserted' => $inserted,
        'updated'  => $updated,
        'errors'   => $errors,
    ]);
});
