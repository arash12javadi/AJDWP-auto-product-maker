<?php
//_____________________________________ tab1-product-scrape-form.php _____________________________________//

// ===============================
// Handle Form Submission
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_url'])) {
    if (!isset($_POST['ajdwp_apm_nonce']) || !wp_verify_nonce($_POST['ajdwp_apm_nonce'], 'ajdwp_apm_action')) {
        echo '<div class="notice notice-error"><p>❌ Security check failed.</p></div>';
    } else {
        // ✅ Fix escaped slashes
        foreach ($_POST as $k => $v) {
            $_POST[$k] = is_array($v) ? stripslashes_deep($v) : stripslashes($v);
        }

        global $wpdb;
        $template_id = intval($_POST['template_select'] ?? 0);
        $url = esc_url_raw(trim($_POST['product_url']));

        // ✅ Now $url is available here
        if ($template_id && !empty($url)) {
            $table_urls = $wpdb->prefix . 'ajdwp_template_urls';
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_urls WHERE template_id = %d AND product_url = %s",
                $template_id,
                $url
            ));

            if (!$exists) {
                $wpdb->insert($table_urls, [
                    'template_id' => $template_id,
                    'product_url' => $url,
                    'last_scraped' => current_time('mysql'),
                ]);
            }
        }


        // Collect selectors
        $selectors = [
            'title'             => sanitize_text_field($_POST['selector_title'] ?? ''),
            'short_description' => sanitize_text_field($_POST['selector_short_description'] ?? ''),
            'long_description'  => sanitize_text_field($_POST['selector_long_description'] ?? ''),
            'image'             => sanitize_text_field($_POST['selector_image'] ?? ''),
            'gallery'           => sanitize_text_field($_POST['selector_gallery'] ?? ''),
            'price'             => sanitize_text_field($_POST['selector_price'] ?? ''),
            'price_calc'        => sanitize_text_field($_POST['selector_price_calc'] ?? ''),
        ];

        // Track skipped fields
        $skip_fields = [];
        foreach (['title', 'short_description', 'long_description', 'image', 'gallery', 'price'] as $field) {
            if (!empty($_POST['skip_' . $field])) {
                $skip_fields[] = $field;
            }
        }

        $action_stage = $_POST['action_stage'] ?? 'preview';
        $method       = sanitize_text_field($_POST['scrape_method'] ?? 'auto');
        $data         = ajdwp_apm_scrape_product_data($url, $selectors, $skip_fields, $method);

        // ✅ Handle "use_regular_price" checkbox
        if (!empty($_POST['use_regular_price'])) {
            $data['final_price'] = $data['price_regular'] ?? $data['price'];
        } else {
            $data['final_price'] = $data['price'];
        }

        if ($data && !empty($data['title'])) {
            if ($action_stage === 'preview') {
                echo '<div style="margin-top: 20px;">';
                echo '<h2>🔍 Scraped Preview</h2>';

                echo '<h3>Title:</h3><p>' . esc_html($data['title'] ?? '⛔ Not found') . '</p>';
                global $wpdb;
                $template_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
                    $template_id
                ));

                echo '<h3>Add This Product to:</h3><p>' . esc_html($template_name ?: '⛔ Not found') . '</p>';


                if (!empty($data['price_regular'])) {
                    echo '<h3>Regular Price:</h3><p>' . esc_html($data['price_regular']) . '</p>';
                }

                if (!empty($data['price'])) {
                    echo '<h3>Discounted Price:</h3><p>' . esc_html($data['price']) . '</p>';
                }

                echo '<br><label><input type="checkbox" name="use_regular_price"> Use regular price instead of discounted</label><br>';

                echo '<h3>Short Description:</h3><p>' . esc_html($data['short_description'] ?? '⛔ Not found') . '</p>';
                echo '<h3>Long Description:</h3><p>' . wp_kses_post($data['long_description'] ?? '<em>⛔ Not found</em>') . '</p>';

                echo '<h3>Main Image:</h3>';
                if (!empty($data['image'])) {
                    echo '<img src="' . esc_url($data['image']) . '" style="max-width:300px;"><br>';
                } else {
                    echo '<p>⛔ Not found</p>';
                }

                echo '<h3>Gallery Images:</h3>';
                if (!empty($data['gallery']) && is_array($data['gallery'])) {
                    foreach ($data['gallery'] as $img_url) {
                        echo '<img src="' . esc_url($img_url) . '" style="max-width:100px; margin-right: 5px;">';
                    }
                } else {
                    echo '<p>⛔ Not found</p>';
                }

                echo '</div>';

                // ✅ Confirmation form
?>
                <form method="post">
                    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
                    <input type="hidden" name="action_stage" value="submit">
                    <input type="hidden" name="product_url" value="<?php echo esc_attr($url); ?>">
                    <?php foreach ($selectors as $key => $val): ?>
                        <input type="hidden" name="selector_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($val); ?>">
                    <?php endforeach; ?>

                    <?php foreach ($skip_fields as $skip): ?>
                        <input type="hidden" name="skip_<?php echo esc_attr($skip); ?>" value="1">
                    <?php endforeach; ?>

                    <br>
                    <button class="button button-primary">✅ Confirm and Create Product</button>
                </form>
                <hr>
<?php
            } else {
                $existing_product_id = ajdwp_apm_get_existing_product_id($url);
                $product_id = ajdwp_apm_create_product($data, $existing_product_id);

                if ($existing_product_id) {
                    echo "<div class='notice notice-success'><p>🔄 Existing product updated! ID: $product_id</p></div>";
                } else {
                    echo "<div class='notice notice-success'><p>✅ New product created! ID: $product_id</p></div>";
                }
            }
        } else {
            echo '<div class="notice notice-error"><p>❌ Failed to scrape valid data. Please check your selectors.</p></div>';
        }
    }
}
?>

<!-- ===========================
     Product Scraping Form
=========================== -->
<form method="post" id="ajdwp-scrape-form">
    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
    <input type="hidden" name="action_stage" value="preview">

    <table class="form-table">

        <tr>
            <th><label for="template_select">Select Template:</label></th>
            <td>
                <select name="template_select" id="template-select-dropdown">
                    <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'ajdwp_templates';
                    $templates = $wpdb->get_results("
                SELECT * FROM $table
                ORDER BY 
                    CASE WHEN name = 'Default Template' THEN 0 ELSE 1 END,
                    name ASC
            ");
                    foreach ($templates as $template) {
                        echo '<option value="' . esc_attr($template->id) . '">' . esc_html($template->name) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>


        <tr>
            <th><label for="scrape_method">Scraping Method:</label></th>
            <td>
                <select name="scrape_method" id="scrape_method">
                    <option value="auto" selected>Auto (Try HTML first)</option>
                    <option value="static">Static (faster, for simple sites)</option>
                    <option value="dynamic">Dynamic (JS-rendered sites)</option>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="product_url">Page URL:</label></th>
            <td><input type="url" name="product_url" required style="width: 100%;" /></td>
        </tr>

        <?php
        $fields = [
            'title'             => 'Title Selector',
            'short_description' => 'Short Description Selector',
            'long_description'  => 'Long Description Selector',
            'image'             => 'Main Image Selector',
            'gallery'           => 'Gallery Image Selectors (comma-separated)',
            'price'             => 'Price Selector',
        ];


        foreach ($fields as $key => $label):
        ?>
            <tr>
                <th><label for="selector_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?>:</label></th>
                <td>
                    <input type="text" name="selector_<?php echo esc_attr($key); ?>" id="selector_<?php echo esc_attr($key); ?>" />
                    <label><input type="checkbox" name="skip_<?php echo esc_attr($key); ?>" id="skip_<?php echo esc_attr($key); ?>"> Ignore if not found</label>
                </td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <th><label for="selector_price_calc">Price Multiplier [ e.g. <span class="temp-default-exp">(price+5)*1.1 </span> &nbsp;]</label></th>
            <td><input type="text" name="selector_price_calc" id="selector_price_calc" /></td>
        </tr>
    </table>

    <p><button class="button button-primary">🔍 Preview Product</button></p>

    <div id="ajdwp-add-products-in-bulk" style="display: none;">
        <h2>Bulk Add Products</h2>
        <div id="bulk-add-urls">
            <div class="bulk-url-row">
                <input type="url"
                    name="bulk_product_urls[]"
                    placeholder="Enter product URL"
                    style="width:80%;"
                    required />
            </div>
        </div>
        <p>
            <button type="button" id="add-bulk-url" class="button">
                + Add another URL
            </button>
            <button type="button" id="add-bulk-submit" class="button button-secondary">
                ✅ Bulk Add to Template
            </button>
        </p>
        <div id="bulk-add-notice"></div>
    </div>

</form>