<?php
//_____________________________________ tab2-product-manager-ajax.php _____________________________________//

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
                    <th scope="col" class="sortable" data-sort="wc_product_id">ID</th>
                    <th scope="col">Image</th>
                    <th scope="col" class="sortable" data-sort="wc_title">Title</th>
                    <th scope="col">Source Link</th>
                    <th scope="col" class="sortable" data-sort="wc_price">Price</th>
                    <th scope="col" class="sortable" data-sort="last_scraped">Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>

            <!-- //---------------------------- Table body will be made by js ----------------------------// -->
            <tbody id="ajdwp-products-tbody">

            </tbody>
        </table>

        <!-- //---------------------------- pagination ----------------------------// -->
        <div id="ajdwp-pagination" style="margin-top: 20px; display: flex; align-items: center; gap: 10px;">
        </div>

    </form>

    <?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});


// ============================
// Delete Product
// ============================
add_action('wp_ajax_ajdwp_delete_product_url', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;
    $id = intval($_POST['id']);
    $result = $wpdb->delete("{$wpdb->prefix}ajdwp_template_urls", ['id' => $id]);
    wp_send_json_success(['deleted' => $result]);
});

// ========================
// Update Price
// ========================
add_action('wp_ajax_ajdwp_update_price', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);
    if (!$id) {
        wp_send_json_error(['message' => 'Missing ID']);
    }

    // ✅ Get URL + template info
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
        $id
    ));
    if (!$row) {
        wp_send_json_error(['message' => 'Product URL not found']);
    }

    $template_id = intval($row->template_id);
    $product_url = $row->product_url;
    $product_id  = ajdwp_apm_get_existing_product_id($product_url);
    if (!$product_id) {
        wp_send_json_error(['message' => 'Product not found']);
    }

    // ✅ Scrape using template selectors
    $data = ajdwp_get_scraped_data_by_template($template_id, $product_url, 'auto');
    if (!$data || empty($data['price'])) {
        wp_send_json_error(['message' => 'No price found']);
    }

    $price = floatval(preg_replace('/[^\d.]/', '', $data['price']));
    if ($price <= 0) {
        wp_send_json_error(['message' => 'Invalid price']);
    }

    // ✅ Update WooCommerce product
    update_post_meta($product_id, '_price', $price);
    update_post_meta($product_id, '_sale_price', $price);

    // ✅ Update plugin table
    $wpdb->update(
        "{$wpdb->prefix}ajdwp_template_urls",
        [
            'price' => $price,
            'last_scraped' => current_time('mysql'),
        ],
        ['id' => $id]
    );

    wp_send_json_success([
        'price' => number_format($price, 2, '.', ''),
        'product_id' => $product_id
    ]);
});

// ========================
// Full Update
// ========================
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

    $template_id   = intval($row->template_id);
    $product_url   = $row->product_url;
    $product_id    = ajdwp_apm_get_existing_product_id($product_url);

    if (!$product_id) {
        error_log("❌ WooCommerce product not found for URL: $product_url");
        wp_send_json_error(['message' => 'Product not found']);
    }

    // ✅ Get selectors and scrape using them
    $selectors = ajdwp_apm_get_template_selectors($template_id);
    $data = ajdwp_apm_scrape_product_data($product_url, $selectors, [], 'auto');
    if (!$data || empty($data['title'])) {
        error_log("❌ Scrape failed for URL: $product_url");
        wp_send_json_error(['message' => 'Scrape failed']);
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

    $wpdb->update(
        "{$wpdb->prefix}ajdwp_template_urls",
        [
            'title'         => sanitize_text_field($data['title']),
            'price'         => sanitize_text_field($data['price']),
            'image'         => esc_url_raw($data['image'] ?? ''),
            'last_scraped'  => current_time('mysql'),
            'wc_product_id' => $product_id,
        ],
        ['id' => $id]
    );

    wp_send_json_success([
        'message'   => 'Product fully updated',
        'price'     => $data['price'],
        'title'     => $data['title'],
        'edit_link' => get_edit_post_link($product_id),
    ]);
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

            // ✅ Also update plugin's custom table
            $wpdb->update(
                "{$wpdb->prefix}ajdwp_template_urls",
                [
                    'price' => $new_price,
                    'last_scraped' => current_time('mysql'),
                ],
                ['id' => $custom_id]
            );

            $updated_prices[] = [
                'product_id' => $product_id,
                'new_price'  => round($new_price, 2),
            ];
        }
    }

    wp_send_json_success(['updated_prices' => $updated_prices]);
}

// ===========================
// AJAX: Search, Sort, Paginate Products
// ===========================
add_action('wp_ajax_ajdwp_table_list_products', function () {
    if (! check_ajax_referer('ajdwp_template_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Security failed']);
    }
    global $wpdb;

    // Inputs
    $template_id = intval($_POST['template_id'] ?? 0);
    $query       = sanitize_text_field($_POST['query'] ?? '');
    $sort_field  = sanitize_key($_POST['sort_field'] ?? 'id');
    $sort_order  = strtoupper($_POST['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $page        = max(1, intval($_POST['page'] ?? 1));
    $per_page    = 5; // or 20

    // Columns map (update as needed)
    $map = [
        'id'            => 'url.id',
        'wc_product_id' => 'url.wc_product_id',
        'wc_price'      => 'CAST(pm_price.meta_value AS DECIMAL(20,2))',
        'wc_title'      => 'p.post_title',
        'last_scraped'  => 'url.last_scraped',
    ];
    $order_by = $map[$sort_field] ?? 'url.id';

    // Always join posts (p) and postmeta (pm_price) for full sorting support
    $join = "
        LEFT JOIN {$wpdb->prefix}posts p ON p.ID = url.wc_product_id
        LEFT JOIN {$wpdb->prefix}postmeta pm_price ON pm_price.post_id = url.wc_product_id AND pm_price.meta_key = '_price'
    ";

    // WHERE
    $where = $wpdb->prepare('url.template_id = %d', $template_id);
    if ($query !== '') {
        $like = '%' . $wpdb->esc_like($query) . '%';
        $where .= $wpdb->prepare(" AND (
          url.product_url LIKE %s
          OR p.post_title LIKE %s
        )", $like, $like);
    }

    // Total count (use same join/where)
    $total = intval($wpdb->get_var("
      SELECT COUNT(*) 
      FROM {$wpdb->prefix}ajdwp_template_urls url
      $join
      WHERE $where
    "));

    // Fetch page
    $offset = ($page - 1) * $per_page;
    $rows = $wpdb->get_results("
      SELECT url.*, p.post_title, pm_price.meta_value as wc_current_price
      FROM {$wpdb->prefix}ajdwp_template_urls url
      $join
      WHERE $where
      ORDER BY $order_by $sort_order
      LIMIT $offset, $per_page
    ");

    // Render rows
    ob_start();
    foreach ($rows as $url) {
        $cid     = $url->id;
        $pu      = $url->product_url;
        $wid     = ajdwp_apm_get_existing_product_id($pu);
        $wpobj   = $wid ? wc_get_product($wid) : null;
        $title   = $wpobj ? $wpobj->get_name() : '';
        // Use live WC price if available
        $price   = $wpobj ? floatval($wpobj->get_price()) : floatval($url->wc_current_price);
        $edit    = $wid ? get_edit_post_link($wid) : '';
        $img     = $wpobj && $wpobj->get_image_id()
            ? wp_get_attachment_image_url($wpobj->get_image_id(), 'thumbnail')
            : ajdwp_apm_get_image_preview_from_url($pu);
    ?>
        <tr data-id="<?= esc_attr($cid) ?>" data-product-id="<?= esc_attr($wid) ?>">
            <th class="check-column">
                <?php if ($wid): ?>
                    <input type="checkbox" name="product_custom_ids[]" value="<?= esc_attr($cid) ?>">
                <?php endif; ?>
            </th>
            <td>
                <?php if ($wid && $edit): ?>
                    <a href="<?= esc_url($edit) ?>" target="_blank"><?= esc_html($wid) ?></a>
                <?php else: ?>
                    <?= esc_html($wid ?: '—') ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($img && $edit): ?>
                    <a href="<?= esc_url($edit) ?>" target="_blank">
                        <img src="<?= esc_url($img) ?>" style="width:50px;height:auto;">
                    </a>
                <?php elseif ($img): ?>
                    <img src="<?= esc_url($img) ?>" style="width:50px;height:auto;">
                <?php else: ?>
                    No image
                <?php endif; ?>
            </td>
            <td id="product-title-<?= esc_attr($wid) ?>" class="editable-title" data-id="<?= esc_attr($cid) ?>" data-product-id="<?= esc_attr($wid) ?>" data-original-title="<?= esc_attr($title) ?>">
                <?= esc_html($title) ?>
            </td>
            <td><a href="<?= esc_url($pu) ?>" target="_blank"><?= esc_html($pu) ?></a></td>
            <td id="product-price-<?= esc_attr($wid) ?>" class="editable-price" data-id="<?= esc_attr($cid) ?>" data-product-id="<?= esc_attr($wid) ?>" data-original-price="<?= esc_attr($price) ?>">
                <?= esc_html(get_woocommerce_currency_symbol()) ?><?= esc_html(number_format($price, 2)) ?>
            </td>
            <td><?= esc_html($url->last_scraped) ?></td>
            <td>
                <button type="button" class="button ajdwp-action-delete" data-id="<?= esc_attr($cid) ?>">🗑</button>
                <button type="button" class="button ajdwp-action-update-price" data-id="<?= esc_attr($cid) ?>" data-product-id="<?= esc_attr($wid) ?>">💰</button>
                <button type="button" class="button ajdwp-action-full-update" data-id="<?= esc_attr($cid) ?>" data-product-id="<?= esc_attr($wid) ?>">♻</button>
            </td>
        </tr>
<?php
    }
    $html = ob_get_clean();

    // Pagination
    $total_pages = max(1, ceil($total / $per_page));
    ob_start();
    if ($page > 1) {
        printf('<button type="button" class="button ajdwp-pagination-prev" data-page="%d">« Prev</button>', $page - 1);
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        printf(
            '<button type="button" class="button ajdwp-pagination-btn %s" data-page="%d">%d</button>',
            $i === $page ? 'active' : '',
            $i,
            $i
        );
    }
    if ($page < $total_pages) {
        printf('<button type="button" class="button ajdwp-pagination-next" data-page="%d">Next »</button>', $page + 1);
    }
    $pagination = ob_get_clean();

    wp_send_json_success([
        'html'         => $html,
        'pagination'   => $pagination,
        'total_pages'  => $total_pages,
    ]);
});

//==========================
//  inline product title and price edit popup
//==========================
add_action('wp_ajax_ajdwp_update_single_field', function () {
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);
    $type = sanitize_text_field($_POST['type']);
    $value = wp_unslash($_POST['value']);

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE id = %d",
        $id
    ));
    if (!$row) wp_send_json_error(['message' => 'Product not found']);

    $product_id = ajdwp_apm_get_existing_product_id($row->product_url);
    if (!$product_id) wp_send_json_error(['message' => 'Product not found']);

    $ok = false;
    if ($type === 'title') {
        $ok = wp_update_post(['ID' => $product_id, 'post_title' => sanitize_text_field($value)]);
        $wpdb->update("{$wpdb->prefix}ajdwp_template_urls", ['title' => $value], ['id' => $id]);
    }
    if ($type === 'price') {
        $v = floatval($value);
        update_post_meta($product_id, '_price', $v);
        update_post_meta($product_id, '_sale_price', $v);
        update_post_meta($product_id, '_regular_price', $v);
        $wpdb->update("{$wpdb->prefix}ajdwp_template_urls", ['price' => $v], ['id' => $id]);
        $ok = true;
    }
    if ($ok !== false) {
        wp_send_json_success();
    }
    wp_send_json_error(['message' => 'Update failed']);
});
