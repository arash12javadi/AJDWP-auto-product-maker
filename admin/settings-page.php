<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_url'])) {
    if (!isset($_POST['ajdwp_apm_nonce']) || !wp_verify_nonce($_POST['ajdwp_apm_nonce'], 'ajdwp_apm_action')) {
        echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
    } else {
        $url = esc_url_raw(trim($_POST['product_url']));
        $data = ajdwp_apm_scrape_product_data($url);

        if ($data && !empty($data['title'])) {
            $product_id = ajdwp_apm_create_product($data);
            echo "<div class='notice notice-success'><p>✅ Product created! ID: $product_id</p></div>";
        } else {
            echo "<div class='notice notice-error'><p>❌ Failed to scrape valid data from the provided URL.</p></div>";
        }
    }
}
?>

<form method="post">
    <?php wp_nonce_field('ajdwp_apm_action', 'ajdwp_apm_nonce'); ?>
    <label for="product_url">Enter Product Page URL:</label><br>
    <input type="url" name="product_url" id="product_url" required style="width: 100%;" /><br><br>
    <button class="button button-primary">Scrape and Create Product</button>
</form>