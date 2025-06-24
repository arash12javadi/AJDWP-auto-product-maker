<?php
// Load scraper functions
require_once plugin_dir_path(__FILE__) . '/../includes/scraper.php';

echo '<div class="wrap">';
echo '<h1>Auto Product Maker</h1>';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_url'])) {
    if (!isset($_POST['ajdwp_apm_nonce']) || !wp_verify_nonce($_POST['ajdwp_apm_nonce'], 'ajdwp_apm_action')) {
        echo '<div class="notice notice-error"><p>❌ Security check failed.</p></div>';
    } else {
        $url = esc_url_raw(trim($_POST['product_url']));

        // Collect selectors
        $selectors = [
            'title'             => sanitize_text_field($_POST['selector_title'] ?? ''),
            'short_description' => sanitize_text_field($_POST['selector_short'] ?? ''),
            'long_description'  => sanitize_text_field($_POST['selector_long'] ?? ''),
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
        $method = sanitize_text_field($_POST['scrape_method'] ?? 'auto');
        $data = ajdwp_apm_scrape_product_data($url, $selectors, $skip_fields, $method);


        if ($data && !empty($data['title'])) {
            if ($action_stage === 'preview') {
                echo '<div style="margin-top: 20px;">';
                echo '<h2>🔍 Scraped Preview</h2>';

                echo '<h3>Title:</h3><span>' . esc_html($data['title'] ?? '⛔ Not found') . '</span>';
                if (!empty($data['price_regular'])) {
                    echo "<h3>Regular Price:</h3> <Span>" . esc_html($data['price_regular']) . "</span>";
                }
                if (!empty($data['price'])) {
                    echo "<h3>Discounted Price:</h3> <Span>" . esc_html($data['price']) . "</span>";
                }
                echo '</br></br><label><input type="checkbox" name="use_regular_price"> Use regular price instead of discounted</label><br>';

                echo '<h3>Short Description:<br></h3><p>' . esc_html($data['short_description'] ?? '⛔ Not found') . '</p>';
                echo '<h3>Long Description:</h3><p>' . wp_kses_post($data['long_description'] ?? '<em>⛔ Not found</em>') . '</p>';

                error_log('🧪 Final image value: ' . print_r($data['image'], true));

                if (!empty($data['image'])) {
                    echo '<h3>Main Image:<br><br><img src="' . esc_url($data['image']) . '" style="max-width:300px;"></h3>';
                } else {
                    echo '<h3>Main Image: ⛔ Not found</h3>';
                }

                if (!empty($data['gallery']) && is_array($data['gallery'])) {
                    echo '<h3>Gallery Images:<br><br>';
                    foreach ($data['gallery'] as $img_url) {
                        echo '<img src="' . esc_url($img_url) . '" style="max-width:100px; margin-right: 5px;">';
                    }
                    echo '</h3>';
                } else {
                    echo '<h3>Gallery Images: ⛔ Not found</h3>';
                }

                echo '</div>';

                // Confirmation form
?>
                <form method="post">
                    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
                    <input type="hidden" name="action_stage" value="submit">
                    <input type="hidden" name="product_url" value="<?php echo esc_attr($url); ?>">
                    <?php foreach ($selectors as $key => $val): ?>
                        <input type="hidden" name="selector_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($val); ?>">
                    <?php endforeach; ?>
                    <br>
                    <button class="button button-primary">✅ Confirm and Create Product</button>
                </form>
                <hr>
<?php
            } else {
                // Create or update product
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

<!-- Product scraping form -->
<form method="post">
    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
    <input type="hidden" name="action_stage" value="preview">

    <table class="form-table">
        <tr>
            <th><label for="scrape_method">Scraping Method:</label></th>
            <td>
                <select name="scrape_method">
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
            'title' => 'Title Selector',
            'short' => 'Short Description Selector',
            'long' => 'Long Description Selector',
            'image' => 'Main Image Selector',
            'gallery' => 'Gallery Image Selectors (comma-separated)',
            'price' => 'Price Selector',
        ];
        foreach ($fields as $key => $label):
        ?>
            <tr>
                <th><label><?php echo esc_html($label); ?>:</label></th>
                <td>
                    <input type="text" name="selector_<?php echo esc_attr($key); ?>" />
                    <label><input type="checkbox" name="skip_<?php echo esc_attr($key); ?>"> Ignore if it is not found</label>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th><label>Price Multiplier (e.g. x1.2 or +5):</label></th>
            <td><input type="text" name="selector_price_calc" /></td>
        </tr>
    </table>

    <p><button class="button button-primary">🔍 Preview Product</button></p>
</form>

<?php echo '</div>'; // close .wrap 
?>