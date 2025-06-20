<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_url'])) {
    if (!isset($_POST['ajdwp_apm_nonce']) || !wp_verify_nonce($_POST['ajdwp_apm_nonce'], 'ajdwp_apm_action')) {
        echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
    } else {
        $url = esc_url_raw(trim($_POST['product_url']));
        $selectors = [
            'title'             => sanitize_text_field($_POST['selector_title'] ?? ''),
            'short_description' => sanitize_text_field($_POST['selector_short'] ?? ''),
            'long_description'  => sanitize_text_field($_POST['selector_long'] ?? ''),
            'image'             => sanitize_text_field($_POST['selector_image'] ?? ''),
            'gallery'           => sanitize_text_field($_POST['selector_gallery'] ?? ''),
            'price'             => sanitize_text_field($_POST['selector_price'] ?? ''),
            'price_calc'        => sanitize_text_field($_POST['price_calc'] ?? ''),
        ];

        $data = ajdwp_apm_scrape_product_data($url, $selectors);
        $action_stage = $_POST['action_stage'] ?? 'preview';

        if ($data && !empty($data['title'])) {
            if ($action_stage === 'preview') {
                // Show preview
                echo "<div class='notice notice-info'><p>🔍 Preview below — confirm to insert product.</p></div>";
                echo "<h3>{$data['title']}</h3>";
                echo "<p><strong>Price:</strong> {$data['price']}</p>";
                echo "<p><strong>Description:</strong> {$data['short_description']}</p>";
                echo "<img src='{$data['image']}' style='max-width:300px;' />";

                // Confirm form
?>
                <form method="post">
                    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
                    <input type="hidden" name="action_stage" value="submit">
                    <input type="hidden" name="product_url" value="<?php echo esc_attr($url); ?>">
                    <?php
                    foreach ($selectors as $key => $val) {
                        echo '<input type="hidden" name="selector_' . esc_attr($key) . '" value="' . esc_attr($val) . '">';
                    }
                    ?>
                    <button class="button button-primary">✅ Confirm and Create Product</button>
                </form>
<?php
            } else {
                // ✅ Create or update
                $existing_product_id = ajdwp_apm_get_existing_product_id($url);
                $product_id = ajdwp_apm_create_product($data, $existing_product_id);

                if ($existing_product_id) {
                    echo "<div class='notice notice-success'><p>🔄 Existing product updated! ID: $product_id</p></div>";
                } else {
                    echo "<div class='notice notice-success'><p>✅ New product created! ID: $product_id</p></div>";
                }
            }
        } else {
            echo "<div class='notice notice-error'><p>❌ Failed to scrape valid data. Please check your selectors.</p></div>";
        }
    }
}
?>

<form method="post">
    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>

    <input type="hidden" name="action_stage" value="preview">

    <label>Page URL:</label><br>
    <input type="url" name="product_url" required style="width: 100%;" /><br><br>

    <label>Title Selector:</label><br>
    <input type="text" name="selector_title" value="h1" /><br>

    <label>Short Description Selector:</label><br>
    <input type="text" name="selector_short" /><br>

    <label>Long Description Selector:</label><br>
    <input type="text" name="selector_long" /><br>

    <label>Main Image Selector:</label><br>
    <input type="text" name="selector_image" /><br>

    <label>Gallery Image Selectors (comma-separated):</label><br>
    <input type="text" name="selector_gallery" /><br>

    <label>Price Selector:</label><br>
    <input type="text" name="selector_price" /><br>

    <label>Price Multiplier (e.g., x1.2 or +5):</label><br>
    <input type="text" name="price_calc" /><br><br>

    <button class="button button-primary">Preview Product</button>
</form>