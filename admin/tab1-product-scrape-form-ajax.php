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
// Scraped Results Preview
// ============================

add_action('wp_ajax_ajdwp_preview_scrape', function () {
    check_ajax_referer('ajdwp_template_nonce');

    $url         = esc_url_raw(trim($_POST['product_url'] ?? ''));
    $template_id = intval($_POST['template_id'] ?? 0);
    $method      = sanitize_text_field($_POST['scrape_method'] ?? 'auto');
    $skip_fields = array_map('sanitize_key', $_POST['skip_fields'] ?? []);

    if (!$template_id || empty($url)) {
        wp_send_json_error(['message' => 'Missing template or URL.']);
    }

    // ✅ Use your built-in helper to get scraped data
    $data = ajdwp_get_scraped_data_by_template($template_id, $url, $method, $skip_fields);

    if (!$data || empty($data['title'])) {
        wp_send_json_error(['message' => 'Failed to scrape product.']);
    }

    // ✅ Prepare preview HTML
    ob_start();
?>
    <p class="h3">🔍 Scraped Preview</p><br>

    <div class="d-flex">
        <p class="h6">Title:</p><button type="button" class="btn btn-success btn-sm ms-5" id="ai-refine-title">✨AI Refine</button>
    </div>
    <p id="preview-title"><?php echo esc_html($data['title']); ?></p>

    <?php if (!empty($data['price_regular'])): ?>
        <p class="h6">Regular Price:</p>
        <p><?php echo esc_html($data['price_regular']); ?></p>
    <?php endif; ?>

    <?php if (!empty($data['price'])): ?>
        <p class="h6">Discounted Price:</p>
        <p><?php echo esc_html($data['price']); ?></p>
    <?php endif; ?>

    <div class="d-flex">
        <p class="h6">Short Description:</p><button type="button" class="btn btn-success btn-sm ms-5" id="ai-refine-short-description">✨AI Refine</button>
    </div>
    <p id="preview-short-description"><?php echo esc_html($data['short_description'] ?? '⛔ Not found'); ?></p>

    <div class="d-flex">
        <p class="h6">Long Description:</p><button type="button" class="btn btn-success btn-sm ms-5" id="ai-refine-long-description">✨AI Refine</button>
    </div>
    <p id="preview-long-description"><?php echo wp_kses_post($data['long_description'] ?? '<em>⛔ Not found</em>'); ?></p>

    <p class="h6">Main Image:</p>
    <?php if (!empty($data['image'])): ?>
        <img src="<?php echo esc_url($data['image']); ?>" style="max-width:300px;"><br>
    <?php else: ?>
        <p>⛔ Not found</p>
    <?php endif; ?>

    <p class="h6">Gallery Images:</p>
    <?php if (!empty($data['gallery']) && is_array($data['gallery'])):
        foreach ($data['gallery'] as $img_url): ?>
            <img src="<?php echo esc_url($img_url); ?>" style="max-width:100px; margin-right: 5px;">
        <?php endforeach;
    else: ?>
        <p>⛔ Not found</p>
    <?php endif; ?>

    <p class="mt-3">
        <button type="submit" class="btn btn-primary" id="submit-button">✅ Confirm and Add to Template</button>
        <button type="button" class="btn btn-danger" id="cancel-button">✖️ Cancel</button>
    </p>
<?php
    $preview_html = ob_get_clean();

    wp_send_json_success(['html' => $preview_html]);
});


// ============================
// AJAX: Bulk Add or Update Product URLs
// ============================
add_action('wp_ajax_ajdwp_bulk_add_product_urls', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $template_id   = intval($_POST['template_id'] ?? 0);
    $raw_urls = $_POST['urls'] ?? [];
    $table    = $wpdb->prefix . 'ajdwp_template_urls';

    if (!$template_id || !is_array($raw_urls)) {
        wp_send_json_error(['message' => 'Invalid input.']);
    }

    $inserted = 0;
    $updated  = 0;
    $errors   = 0;

    // load template once
    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
        $template_id
    ));
    $selectors = ajdwp_apm_get_template_selectors($template_id);

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
            $template_id,
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
                'template_id'  => $template_id,
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
                    ['template_id' => $template_id, 'product_url' => $url]
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

// ============================
// AJAX: Add Single Product URL (Sequential)
// ============================
add_action('wp_ajax_ajdwp_add_single_product_url', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $template_id = intval($_POST['template_id'] ?? 0);
    $url = esc_url_raw(trim($_POST['product_url'] ?? ''));
    $table = $wpdb->prefix . 'ajdwp_template_urls';
    $skip_fields = array_map('sanitize_key', $_POST['skip_fields'] ?? []);

    if (!$template_id || !$url) {
        wp_send_json_error(['message' => 'Missing template or URL']);
    }

    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
        $template_id
    ));
    if (!$template) {
        wp_send_json_error(['message' => 'Template not found']);
    }

    $selectors = ajdwp_apm_get_template_selectors($template_id);

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE template_id = %d AND product_url = %s",
        $template_id,
        $url
    ));

    $data = ajdwp_apm_scrape_product_data(
        $url,
        $selectors,
        [],
        $skip_fields,
        $template->scrape_method ?? 'auto'
    );

    if (!$data || empty($data['title'])) {
        wp_send_json_error(['message' => '❌ Scrape failed or no title']);
    }

    if ($row) {
        $existing_id = intval($row->wc_product_id);
        $prod_id = ajdwp_apm_create_product($data, $existing_id);
        if (!$prod_id) {
            wp_send_json_error(['message' => '❌ Failed to update product']);
        }

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

        wp_send_json_success(['message' => '🔄 Updated', 'title' => $data['title']]);
    } else {
        $wpdb->insert($table, [
            'template_id'  => $template_id,
            'product_url'  => $url,
            'last_scraped' => current_time('mysql'),
        ]);

        $new_id = ajdwp_apm_create_product($data);
        if ($new_id) {
            $wpdb->update(
                $table,
                ['wc_product_id' => $new_id],
                ['template_id' => $template_id, 'product_url' => $url]
            );
        }

        wp_send_json_success(['message' => '✅ Inserted', 'title' => $data['title']]);
    }
});

//==========================
//  AI Refine single product (title, short_description, long_description)
//==========================
add_action('wp_ajax_ajdwp_ai_refine_single', function () {
    check_ajax_referer('ajdwp_template_nonce');

    $text = sanitize_textarea_field($_POST['text'] ?? '');
    $field = sanitize_text_field($_POST['field'] ?? '');

    if (!$text || !$field) {
        wp_send_json_error(['message' => 'Missing text or field']);
    }

    require_once AJDWPAPM_PATH . 'includes/helpers.php';

    switch ($field) {
        case 'title':
            $prompt = "Refine this product title for SEO: \"$text\"";
            break;
        case 'short_description':
            $prompt = "Make this short product description more appealing and SEO-friendly:\n\n$text";
            break;
        case 'long_description':
            $prompt = "Improve this long product description for clarity, engagement and SEO:\n\n$text";
            break;
        default:
            wp_send_json_error(['message' => 'Invalid field']);
    }

    $refined = ajdwp_refine_with_ai($prompt);

    if (!$refined) {
        wp_send_json_error(['message' => 'AI failed to refine']);
    }

    wp_send_json_success(['refined' => $refined]);
});
