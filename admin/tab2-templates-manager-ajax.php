<?php

//_____________________________________ tab2-templates-manager-ajax.php _____________________________________//

// ============================
// AJAX: Rename and Delete Templates
// ============================

add_action('wp_ajax_ajdwp_update_template', 'ajdwp_apm_ajax_update_template');
add_action('wp_ajax_ajdwp_delete_template', 'ajdwp_apm_ajax_delete_template');


function ajdwp_apm_ajax_update_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id   = intval($_POST['id']);
    $name = sanitize_text_field($_POST['name']);

    $wpdb->update(
        "{$wpdb->prefix}ajdwp_templates",
        ['name' => $name],
        ['id'   => $id]
    );

    wp_send_json_success();
}

function ajdwp_apm_ajax_delete_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);

    // Move associated URLs to Default Template (ID 1)
    $wpdb->update("{$wpdb->prefix}ajdwp_template_urls", [
        'template_id' => 1
    ], ['template_id' => $id]);

    // Optionally clean up associated selectors
    $wpdb->delete("{$wpdb->prefix}ajdwp_template_selectors", ['template_id' => $id]);

    // Delete template itself
    $wpdb->delete("{$wpdb->prefix}ajdwp_templates", ['id' => $id]);

    wp_send_json_success();
}


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
    <p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>
    <div class="ajdwp-template-selectors" style="margin: 20px 0;">
        <h3>🔧 Scraping Selectors</h3>
        <table class="form-table">
            <?php
            $fields = [
                'title_selector' => 'Title Selector',
                'short_description_selector' => 'Short Description',
                'long_description_selector' => 'Long Description',
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

    <p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>

    <form id="ajdwp-template-products-form" method="post">

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="ajdwp-bulk-action-top" class="screen-reader-text">Select bulk action</label>
                <select name="ajdwp_bulk_action" id="ajdwp-bulk-action-top">
                    <option value="-1">Bulk actions</option>
                    <option value="delete_all">Delete</option>
                    <option value="update_price_all">Update Price</option>
                    <option value="full_update_all">Full Update</option>
                </select>
                <button type="button" class="button action" id="ajdwp-do-bulk-action">Apply All</button>
            </div>
            <div class="price-multiplier" style="float: left;">
                <label for="input_price_multiplier_all">Price Multiplier:</label>
                <input type="text" name="input_price_multiplier_all" id="input_price_multiplier_all" value="price*1" />
                <button type="button" class="button action" id="ajdwp-bulk-multiplier-all">Calculate</button>
            </div>
            <div style="float:right;">
                <label for="ajdwp-product-search">Search Products:</label>
                <input type="text" id="ajdwp-product-search" name="product-search" placeholder="Search by title or URL..." data-template-id="<?= esc_attr($template_id); ?>">
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column"><input id="cb-select-all" type="checkbox"></td>
                    <th scope="col" class="sortable" data-sort="id">ID</th>
                    <th scope="col">Image</th>
                    <th scope="col" class="sortable" data-sort="title">Title</th>
                    <th scope="col">Source Link</th>
                    <th scope="col" class="sortable" data-sort="price">Price</th>
                    <th scope="col" class="sortable" data-sort="date">Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>

            <tbody id="ajdwp-products-tbody">
                <?php foreach ($urls as $url): ?>
                    <?php
                    $custom_id = $url->id;
                    $product_url = $url->product_url;

                    // 🔍 Find linked WooCommerce product ID from your URL
                    $wc_product_id = ajdwp_apm_get_existing_product_id($product_url);
                    $wc_product = $wc_product_id ? wc_get_product($wc_product_id) : null;

                    $title = $wc_product ? $wc_product->get_name() : '❌ Not found';
                    $edit_link = $wc_product_id ? get_edit_post_link($wc_product_id) : '';
                    $price = $wc_product ? $wc_product->get_price() : '❌';

                    // Use your thumbnail preview fallback if needed
                    $image = $wc_product && $wc_product->get_image_id()
                        ? wp_get_attachment_image_url($wc_product->get_image_id(), 'thumbnail')
                        : ajdwp_apm_get_image_preview_from_url($product_url);
                    ?>
                    <tr data-id="<?= esc_attr($custom_id) ?>" data-product-id="<?= esc_attr($wc_product_id) ?>">
                        <th scope="row" class="check-column">
                            <?php if ($wc_product_id): ?>
                                <input type="checkbox" name="product_custom_ids[]" value="<?= esc_attr($custom_id) ?>">
                            <?php endif; ?>
                        </th>
                        <td><?= esc_html($wc_product_id ?: '—') ?></td>
                        <td>
                            <?php if (!empty($image)): ?>
                                <img src="<?= esc_url($image) ?>" style="width:50px;height:auto;" />
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </td>
                        <td id="product-title-<?php echo esc_attr($wc_product_id) ?>">
                            <?php if ($edit_link): ?>
                                <a href="<?= esc_url($edit_link) ?>" target="_blank"><?= esc_html($title) ?></a>
                            <?php else: ?>
                                <?= esc_html($title) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= esc_url($product_url) ?>" target="_blank"><?= esc_html($product_url) ?></a>
                        </td>
                        <td id="product-price-<?php echo esc_attr($wc_product_id) ?>">£<?= esc_html($price) ?></td>
                        <td><?= esc_html($url->last_scraped ?? '—') ?></td>
                        <td>
                            <button type="button" class="button button-small ajdwp-action-delete" data-id="<?= esc_attr($custom_id) ?>">🗑 Delete</button>
                            <button type="button" class="button button-small ajdwp-action-update-price"
                                data-id="<?= esc_attr($custom_id) ?>"
                                data-product-id="<?= esc_attr($wc_product_id) ?>">
                                💰 Update Price
                            </button>
                            <button type="button" class="button button-small ajdwp-action-full-update"
                                data-id="<?= esc_attr($custom_id) ?>"
                                data-product-id="<?= esc_attr($wc_product_id) ?>">
                                ♻ Full Update
                            </button>

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
// AJAX: Update element selector Fields of the templates
// ============================

add_action('wp_ajax_ajdwp_update_single_template_field', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $id = intval($_POST['id']);
    $field = sanitize_key($_POST['field']);
    $value = sanitize_text_field(wp_unslash($_POST['value']));

    // whitelist allowed fields
    $allowed = [
        'title_selector',
        'short_description_selector',
        'long_description_selector',
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

// ========================
// Update Price (With Instant UI Feedback)
// ========================
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

    // 🟢 Get template ID and scraping method
    $template_id = $wpdb->get_var($wpdb->prepare(
        "SELECT template_id FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
        $id
    ));

    $scrape_method = $wpdb->get_var($wpdb->prepare(
        "SELECT scrape_method FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
        $template_id
    )) ?: 'auto';

    // 🟢 Scrape just the price
    $data = ajdwp_apm_scrape_product_data($url, [], [], $scrape_method);
    if (!$data || empty($data['price'])) {
        wp_send_json_error(['message' => 'No price found']);
    }

    // 🟢 Update WooCommerce sale price
    update_post_meta($product_id, '_sale_price', $data['price']);
    update_post_meta($product_id, '_price', $data['price']); // sync

    // 🟢 Make sure WC price is correctly saved before sending back
    $confirmed_price = get_post_meta($product_id, '_sale_price', true);

    wp_send_json_success([
        'price' => $data['price'],
        'product_id' => $product_id,
    ]);
});


// -----------------------------------

add_action('wp_ajax_ajdwp_full_update_product', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;

    $id = intval($_POST['id']);
    if (!$id) {
        error_log('❌ Missing product ID.');
        wp_send_json_error(['message' => 'Missing ID']);
    }

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
        $id
    ));
    if (!$row) {
        error_log("❌ No URL row found for ID: $id");
        wp_send_json_error(['message' => 'Product not found']);
    }

    $template_id = intval($row->template_id);
    $template = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = $template_id");

    if (!$template) {
        error_log("❌ Template not found for ID: $template_id");
        wp_send_json_error(['message' => 'Template not found']);
    }

    $scrape_method = $template->scrape_method ?? 'auto';

    $selectors = [
        'title_selector' => $template->title_selector ?? '',
        'short_description_selector' => $template->short_description_selector ?? '',
        'long_description_selector' => $template->long_description_selector ?? '',
        'main_image_selector' => $template->main_image_selector ?? '',
        'gallery_image_selectors' => $template->gallery_image_selectors ?? '',
        'price_selector' => $template->price_selector ?? '',
        'price_multiplier' => $template->price_multiplier ?? '',
    ];

    $data = ajdwp_apm_scrape_product_data($row->product_url, $selectors, [], $scrape_method);
    if (!$data || empty($data['title'])) {
        error_log("❌ Scrape failed for URL: " . $row->product_url);
        wp_send_json_error(['message' => 'Scrape failed']);
    }

    $product_id = ajdwp_apm_get_existing_product_id($row->product_url);
    if (!$product_id) {
        error_log("❌ WooCommerce product not found for URL: " . $row->product_url);
        wp_send_json_error(['message' => 'Product not found']);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        error_log("❌ wc_get_product failed for ID: $product_id");
        wp_send_json_error(['message' => 'Product object missing']);
    }

    $product->set_name($data['title']);
    $product->set_description($data['long_description'] ?? '');
    $product->set_short_description($data['short_description'] ?? '');
    $product->set_price($data['price']);
    $product->set_regular_price($data['price']);
    $product->set_sale_price($data['price']);
    $product->set_status('publish');
    $product->save();

    // image handlers
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $image_url = $data['image'] ?? '';
    if (!empty($image_url)) {
        $media_id = ajdwp_apm_sideload_image($image_url, $product_id);
        if ($media_id) {
            $product->set_image_id($media_id);
            $product->save();
        }
    }

    $gallery_urls = $data['gallery'] ?? [];
    if (!empty($gallery_urls) && is_array($gallery_urls)) {
        $gallery_ids = [];

        foreach ($gallery_urls as $url) {
            $gallery_id = ajdwp_apm_sideload_image($url, $product_id);
            if ($gallery_id) {
                $gallery_ids[] = $gallery_id;
            }
        }

        if (!empty($gallery_ids)) {
            $product->set_gallery_image_ids($gallery_ids);
            $product->save();
        }
    }


    // Update URL record
    $wpdb->update(
        "{$wpdb->prefix}ajdwp_template_urls",
        [
            'title' => sanitize_text_field($data['title']),
            'price' => sanitize_text_field($data['price']),
            'image' => esc_url_raw($data['image'] ?? ''),
            'last_scraped' => current_time('mysql')
        ],
        ['id' => $id]
    );

    wp_send_json_success([
        'message' => 'Product fully updated',
        'price' => $data['price'],
        'title' => $data['title'],
        'edit_link' => get_edit_post_link($product_id),
    ]);
});

// ===========================
// AJAX: Search Template Products
// ============================

add_action('wp_ajax_ajdwp_search_template_products', function () {
    if (!check_ajax_referer('ajdwp_template_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    global $wpdb;

    $template_id = intval($_POST['template_id']);
    $query = sanitize_text_field($_POST['query'] ?? '');
    $sort_field = sanitize_key($_POST['sort_field'] ?? 'id');
    $sort_order = strtoupper($_POST['sort_order'] ?? 'ASC');

    // ✅ Only allow safe columns
    $allowed_fields = ['id', 'title', 'price', 'last_scraped'];
    if (!in_array($sort_field, $allowed_fields, true)) {
        $sort_field = 'id';
    }

    $sort_order = $sort_order === 'DESC' ? 'DESC' : 'ASC';
    $like = '%' . $wpdb->esc_like($query) . '%';

    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}ajdwp_template_urls
        WHERE template_id = %d
        AND (product_url LIKE %s OR title LIKE %s)
        ORDER BY $sort_field $sort_order
    ", $template_id, $like, $like));

    ob_start();
    foreach ($rows as $url) {
        $custom_id = $url->id;
        $product_url = $url->product_url;
        $wc_product_id = ajdwp_apm_get_existing_product_id($product_url);
        $wc_product = $wc_product_id ? wc_get_product($wc_product_id) : null;
        $title = $wc_product ? $wc_product->get_name() : '❌ Not found';
        $edit_link = $wc_product_id ? get_edit_post_link($wc_product_id) : '';
        $price = $wc_product ? $wc_product->get_price() : '❌';
        $image = $wc_product && $wc_product->get_image_id()
            ? wp_get_attachment_image_url($wc_product->get_image_id(), 'thumbnail')
            : ajdwp_apm_get_image_preview_from_url($product_url);
    ?>
        <tr data-id="<?= esc_attr($custom_id) ?>">
            <th scope="row" class="check-column">
                <?php if ($wc_product_id): ?>
                    <input type="checkbox" name="product_custom_ids[]" value="<?= esc_attr($custom_id) ?>">
                <?php endif; ?>

            </th>
            <td><?= esc_html($wc_product_id ?: '—') ?></td>
            <td>
                <?php if (!empty($image)): ?>
                    <img src="<?= esc_url($image) ?>" style="width:50px;height:auto;" />
                <?php else: ?>
                    <span>No image</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($edit_link): ?>
                    <a href="<?= esc_url($edit_link) ?>" target="_blank"><?= esc_html($title) ?></a>
                <?php else: ?>
                    <?= esc_html($title) ?>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= esc_url($product_url) ?>" target="_blank"><?= esc_html($product_url) ?></a>
            </td>
            <td>£<?= esc_html($price) ?></td>
            <td><?= esc_html($url->last_scraped ?? '—') ?></td>
            <td>
                <button type="button" class="button button-small ajdwp-action-delete" data-id="<?= esc_attr($custom_id) ?>">🗑 Delete</button>
                <button type="button" class="button button-small ajdwp-action-update-price"
                    data-id="<?= esc_attr($custom_id) ?>"
                    data-product-id="<?= esc_attr($wc_product_id) ?>">
                    💰 Update Price
                </button>
                <button type="button" class="button button-small ajdwp-action-full-update"
                    data-id="<?= esc_attr($custom_id) ?>"
                    data-product-id="<?= esc_attr($wc_product_id) ?>">
                    ♻ Full Update
                </button>
            </td>
        </tr>
<?php
    }
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});

// ============================
// AJAX: Bulk Product Actions
// =============================
add_action('wp_ajax_ajdwp_bulk_product_action', 'ajdwp_handle_bulk_product_action');
function ajdwp_handle_bulk_product_action()
{
    ob_start(); // ✅ prevent headers already sent errors
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;

    $ids = $_POST['ids'] ?? [];
    $action = sanitize_text_field($_POST['sub_action'] ?? '');

    if (empty($ids) || !is_array($ids)) {
        wp_send_json_error(['message' => 'Invalid product IDs.']);
    }

    foreach ($ids as $custom_id) {
        $custom_id = intval($custom_id);
        if (!$custom_id) continue;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
            $custom_id
        ));

        if (!$row) continue;

        $source_url = $row->product_url;
        $template_id = $row->template_id;
        $product_id = ajdwp_apm_get_existing_product_id($source_url);
        $selectors = ajdwp_apm_get_template_selectors($template_id);

        switch ($action) {
            case 'delete_all':
                // Delete WooCommerce product if it exists
                if ($product_id) {
                    wp_delete_post($product_id, true);
                }
                // Delete from your plugin table too
                $wpdb->delete("{$wpdb->prefix}ajdwp_template_urls", ['id' => $custom_id]);
                break;

            case 'update_price_all':
                if (!$product_id) continue 2;

                $price_data = ajdwp_apm_scrape_product_data($source_url, $selectors, [], 'auto');
                $raw_price = $price_data['price'] ?? '';

                // ✅ Clean and validate price
                $price = floatval(preg_replace('/[^\d.]/', '', $raw_price));
                if ($price <= 0) continue 2;

                $product = wc_get_product($product_id);
                if ($product) {
                    $product->set_sale_price($price);
                    $product->set_price($price);
                    $product->save();

                    // Force update via meta just in case
                    update_post_meta($product_id, '_sale_price', $price);
                    update_post_meta($product_id, '_price', $price);
                }
                break;

            case 'full_update_all':
                if (!$product_id) continue 2;
                $scraped = ajdwp_apm_scrape_product_data($source_url, $selectors, [], 'auto');

                if ($scraped && !empty($scraped['title'])) {
                    ajdwp_apm_create_product($scraped, $product_id);

                    // Optionally update your plugin's URL table
                    $wpdb->update("{$wpdb->prefix}ajdwp_template_urls", [
                        'title' => sanitize_text_field($scraped['title']),
                        'price' => sanitize_text_field($scraped['price']),
                        'image' => esc_url_raw($scraped['image'] ?? ''),
                        'last_scraped' => current_time('mysql')
                    ], ['id' => $custom_id]);
                }
                break;
        }
    }

    ob_end_clean(); // ✅ clean buffer to avoid header issues
    wp_send_json_success(['message' => '✅ Bulk action completed.']);
}


// ============================
// AJAX: Bulk Price Multiplier
// ============================

add_action('wp_ajax_ajdwp_bulk_price_multiplier', 'ajdwp_handle_bulk_price_multiplier');
function ajdwp_handle_bulk_price_multiplier()
{
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;

    $ids = $_POST['ids'] ?? [];
    $formula = trim($_POST['formula'] ?? '');

    if (empty($ids) || !is_array($ids) || empty($formula)) {
        wp_send_json_error(['message' => 'Missing data.']);
    }

    $updated_prices = [];

    foreach ($ids as $custom_id) {
        $custom_id = intval($custom_id);
        if (!$custom_id) continue;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
            $custom_id
        ));

        if (!$row) continue;

        $product_id = ajdwp_apm_get_existing_product_id($row->product_url);
        if (!$product_id) continue;

        $product = wc_get_product($product_id);
        if (!$product) continue;

        $original_price = floatval($product->get_price());
        if ($original_price <= 0) continue;

        // 🔢 Evaluate custom formula
        $safe_formula = str_replace('price', $original_price, $formula);
        try {
            // very limited eval - you control the formula UI
            $new_price = @eval("return floatval($safe_formula);");
        } catch (Throwable $e) {
            continue;
        }

        if (is_numeric($new_price) && $new_price > 0) {
            $product->set_price($new_price);
            $product->set_sale_price($new_price);
            $product->save();

            update_post_meta($product_id, '_price', $new_price);
            update_post_meta($product_id, '_sale_price', $new_price);

            $updated_prices[] = [
                'product_id' => $product_id,
                'new_price'  => round($new_price, 2),
            ];
        }
    }

    wp_send_json_success(['updated_prices' => $updated_prices]);
}
