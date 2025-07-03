<?php
//_____________________________________ tab1-product-scrape-form.php _____________________________________//
?>

<form method="post" id="ajdwp-scrape-form">
    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
    <input type="hidden" name="action_stage" value="submit">

    <table class="form-table">
        <tr>
            <th><label for="template_select"><strong> 🧩 Select a Template: </strong></label></th>
            <td>
                <select name="template_select" id="template-select-dropdown">
                    <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'ajdwp_templates';
                    $templates = $wpdb->get_results(
                        "SELECT * FROM $table
                         ORDER BY CASE WHEN name = 'Default Template' THEN 0 ELSE 1 END, name ASC"
                    );
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
            <td><input type="url" name="product_url" required style="width:100%;max-width:800px;" /></td>
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
                    <input type="text" style="width:100%;max-width:600px;" name="selector_<?php echo esc_attr($key); ?>" id="selector_<?php echo esc_attr($key); ?>" />
                    <label><input type="checkbox" name="skip_<?php echo esc_attr($key); ?>" id="skip_<?php echo esc_attr($key); ?>"> Ignore if not found</label>
                </td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <th><label for="selector_price_calc">Price Multiplier [ e.g. <span class="temp-default-exp">(price+5)*1.1 </span> &nbsp;]</label></th>
            <td><input type="text" name="selector_price_calc" id="selector_price_calc" /></td>
        </tr>
    </table>

    <!-- Preview button triggers AJAX -->
    <p>
        <button type="button" class="button button-primary" id="preview-button">🔍 Preview Product</button>
    </p>

    <div id="ajdwp-preview-container" style="margin-top: 20px;"></div>

    <!-- Bulk Add Products UI -->
    <div id="ajdwp-add-products-in-bulk" style="display: none;">
        <h2>Bulk Add Products</h2>
        <div id="bulk-add-urls">
            <div class="bulk-url-row">
                <input type="url" name="bulk_product_urls[]" placeholder="Enter product URL" style="width:100%;max-width:800px;" />
            </div>
        </div>
        <p>
            <button type="button" id="add-bulk-url" class="button">
                + Add another URL
            </button>
        </p>
        <p>
            <button type="button" id="add-bulk-submit" class="button button-secondary">
                ✅ Bulk Add to Template
            </button>
        </p>
        <div id="bulk-add-notice"></div>
    </div>
</form>