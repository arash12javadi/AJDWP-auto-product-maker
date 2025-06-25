<?php

// ============================
// AJAX: Load Template Panel
// ============================
add_action('wp_ajax_ajdwp_get_template_panel', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $template_id = intval($_POST['template_id']);
    if (!$template_id) {
        wp_send_json_error(['message' => 'No template selected']);
    }

    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
        $template_id
    ));

    if (!$template) {
        wp_send_json_error(['message' => 'Template not found']);
    }

    $urls = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE template_id = %d",
        $template_id
    ));

    ob_start();
?>

    <div class="ajdwp-template-header">
        <h2 style="color: darkblue;font-size: 30px;"><?= esc_html($template->name) ?></h2>
        <?php if ((int) $template->id !== 1): ?>
            <div style="margin-bottom: 10px;">
                <button class="button rename-template" data-id="<?= esc_attr($template->id) ?>">✏ Rename Template</button>
                <button class="button delete-template" data-id="<?= esc_attr($template->id) ?>">🗑 Delete Template</button>
            </div>
        <?php endif; ?>
    </div>
    <div class="ajdwp-template-selectors" style="margin: 20px 0;">
        <h3>🔧 Scraping Selectors</h3>
        <table class="form-table">
            <?php
            $fields = [
                'title_selector' => 'Title Selector',
                'short_desc_selector' => 'Short Description',
                'long_desc_selector' => 'Long Description',
                'main_image_selector' => 'Main Image',
                'gallery_image_selectors' => 'Gallery Images',
                'price_selector' => 'Price Selector',
                'price_multiplier' => 'Price Multiplier',
                'scrape_method' => 'Scraping Method',
            ];
            foreach ($fields as $field => $label):
                $value = esc_html($template->$field);
            ?>
                <tr>
                    <th><?= $label ?></th>
                    <td>
                        <p
                            class="ajdwp-editable-selector"
                            data-field="<?= esc_attr($field) ?>"
                            data-value="<?= esc_attr($value) ?>"
                            data-id="<?= esc_attr($template->id) ?>"
                            style="cursor:pointer;color:#2271b1;">
                            <?= $value ?: '<em>Not set</em>' ?>
                        </p>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <p>-------------------------------------------------------------------------------------------------------------------</p>

    <form id="ajdwp-template-products-form" method="post">
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="ajdwp-bulk-action-top" class="screen-reader-text">Select bulk action</label>
                <select name="ajdwp_bulk_action" id="ajdwp-bulk-action-top">
                    <option value="-1">Bulk actions</option>
                    <option value="delete">Delete</option>
                    <option value="update_price">Update Price</option>
                    <option value="full_update">Full Update</option>
                </select>
                <button type="button" class="button action" id="ajdwp-do-bulk-action">Apply</button>
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all" type="checkbox">
                    </td>
                    <th scope="col">ID</th>
                    <th scope="col">Image</th>
                    <th scope="col">Title</th>
                    <th scope="col">Source Link</th>
                    <th scope="col">Price</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($urls as $url): ?>
                    <?php
                    $product_id = $url->id;
                    $product_url = $url->product_url;

                    // ✅ Scrape product data dynamically (use 'static' method for speed)
                    $scraped_data = ajdwp_apm_scrape_product_data($product_url, [], [], 'static');

                    $title = $scraped_data['title'] ?? '❌ Title not found';
                    $price = $scraped_data['price'] ?? '❌';
                    $image = $scraped_data['image'] ?? ajdwp_apm_get_image_preview_from_url($product_url);
                    ?>
                    <tr data-id="<?= esc_attr($product_id) ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="product_ids[]" value="<?= esc_attr($product_id) ?>">
                        </th>
                        <td><?= esc_html($product_id) ?></td>
                        <td>
                            <?php if (!empty($image)): ?>
                                <img src="<?= esc_url($image) ?>" style="width:50px;height:auto;" />
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc_html($title) ?></td>
                        <td>
                            <a href="<?= esc_url($product_url) ?>" target="_blank"><?= esc_html($product_url) ?></a>
                        </td>
                        <?php
                        $product_id = ajdwp_apm_get_existing_product_id($url->product_url);
                        $wc_price = $product_id ? get_post_meta($product_id, '_price', true) : '—';
                        ?>
                        <td><?= esc_html($wc_price ?: '—') ?></td>

                        <td>
                            <button type="button" class="button button-small ajdwp-action-delete" data-id="<?= esc_attr($product_id) ?>">🗑 Delete</button>
                            <button type="button" class="button button-small ajdwp-action-update-price" data-id="<?= esc_attr($product_id) ?>">💰 Update Price</button>
                            <button type="button" class="button button-small ajdwp-action-full-update" data-id="<?= esc_attr($product_id) ?>">♻ Full Update</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>

<?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});


// ============================
// AJAX: Update Single Template Field
// ============================

add_action('wp_ajax_ajdwp_update_single_template_field', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $id = intval($_POST['id']);
    $field = sanitize_key($_POST['field']);
    $value = sanitize_text_field($_POST['value']);

    // whitelist allowed fields
    $allowed = [
        'title_selector',
        'short_desc_selector',
        'long_desc_selector',
        'main_image_selector',
        'gallery_image_selectors',
        'price_selector',
        'price_multiplier',
        'scrape_method'
    ];

    if (!in_array($field, $allowed, true)) {
        wp_send_json_error(['message' => 'Invalid field']);
    }

    $updated = $wpdb->update(
        "{$wpdb->prefix}ajdwp_templates",
        [$field => $value],
        ['id' => $id]
    );

    if ($updated !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
});


// ============================
// AJAX: CRUD listed products
// ============================

add_action('wp_ajax_ajdwp_delete_product_url', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;
    $id = intval($_POST['id']);
    $result = $wpdb->delete("{$wpdb->prefix}ajdwp_template_urls", ['id' => $id]);
    wp_send_json_success(['deleted' => $result]);
});

// -----------------------------------

add_action('wp_ajax_ajdwp_update_price', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);
    $url = $wpdb->get_var($wpdb->prepare(
        "SELECT product_url FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
        $id
    ));
    if (!$url) wp_send_json_error(['message' => 'URL not found']);

    $product_id = ajdwp_apm_get_existing_product_id($url);
    if (!$product_id) wp_send_json_error(['message' => 'Product not found']);

    $data = ajdwp_apm_scrape_product_data($url, [], [], 'static');
    if (!$data || empty($data['price'])) {
        wp_send_json_error(['message' => 'No price found']);
    }

    update_post_meta($product_id, '_price', $data['price']);
    update_post_meta($product_id, '_regular_price', $data['price']);

    wp_send_json_success(['price' => $data['price']]);
});

// -----------------------------------

add_action('wp_ajax_ajdwp_full_update_product', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
        $id
    ));
    if (!$row) wp_send_json_error(['message' => 'Product not found']);

    $data = ajdwp_apm_scrape_product_data($row->product_url, [], [], 'static');
    if (!$data || empty($data['title'])) {
        wp_send_json_error(['message' => 'Scrape failed']);
    }

    $product_id = ajdwp_apm_get_existing_product_id($row->product_url);
    if (!$product_id) wp_send_json_error(['message' => 'Product not found']);

    // Update WooCommerce product
    $product = wc_get_product($product_id);
    if (!$product) wp_send_json_error(['message' => 'Product object missing']);

    $product->set_name($data['title']);
    $product->set_description($data['long_description']);
    $product->set_short_description($data['short_description']);
    $product->set_price($data['price']);
    $product->set_regular_price($data['price']);
    $product->set_status('publish');
    $product->save();

    // Optionally update image and gallery too (reuse your image sideload function)

    // Update template row data
    $wpdb->update(
        "{$wpdb->prefix}ajdwp_template_urls",
        [
            'title' => sanitize_text_field($data['title']),
            'price' => sanitize_text_field($data['price']),
            'image' => esc_url_raw($data['image']),
            'last_scraped' => current_time('mysql')
        ],
        ['id' => $id]
    );

    wp_send_json_success(['message' => 'Product fully updated']);
});
