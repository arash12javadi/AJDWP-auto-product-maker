<?php
//_____________________________________ create-db-on-activation.php _____________________________________//

/**
 * Create necessary DB tables and insert default data on plugin activation.
 */
function ajdwp_apm_create_db_tables()
{
  global $wpdb;

  $charset_collate = $wpdb->get_charset_collate();
  $table_templates = $wpdb->prefix . 'ajdwp_templates';
  $table_selectors = $wpdb->prefix . 'ajdwp_template_selectors';
  $table_urls      = $wpdb->prefix . 'ajdwp_template_urls';

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  // Templates Table
  $sql_templates = "CREATE TABLE $table_templates (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        title_selector TEXT,
        short_description_selector TEXT,
        long_description_selector TEXT,
        main_image_selector TEXT,
        gallery_image_selectors TEXT,
        price_selector TEXT,
        price_multiplier VARCHAR(50),
        scrape_method VARCHAR(50),
        ai_mode VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
  dbDelta($sql_templates);

  // Template Selectors Table
  $sql_selectors = "CREATE TABLE $table_selectors (
        id INT NOT NULL AUTO_INCREMENT,
        template_id INT NOT NULL,
        field_name VARCHAR(50) NOT NULL,
        selector_value TEXT NOT NULL,
        PRIMARY KEY (id),
        KEY idx_template_id (template_id)
    ) $charset_collate;";
  dbDelta($sql_selectors);

  // Product URLs Table
  $sql_urls = "CREATE TABLE $table_urls (
      id INT NOT NULL AUTO_INCREMENT,
      template_id INT NOT NULL,
      product_url TEXT NOT NULL,
      wc_product_id BIGINT DEFAULT NULL,
      title TEXT,
      price DECIMAL(10,2),
      image TEXT,
      last_scraped DATETIME DEFAULT NULL,
      status VARCHAR(50) DEFAULT NULL,
      PRIMARY KEY (id),
      KEY idx_template_id (template_id),
      KEY idx_wc_product_id (wc_product_id)
  ) $charset_collate;";

  dbDelta($sql_urls);

  // Insert Default Template If Not Exists
  $default_template_id = $wpdb->get_var(
    $wpdb->prepare("SELECT id FROM $table_templates WHERE name = %s", 'Default Template')
  );

  if (!$default_template_id) {
    $wpdb->insert($table_templates, ['name' => 'Default Template']);
    $default_template_id = $wpdb->insert_id;

    // Insert default selectors
    $default_selectors = [
      ['field_name' => 'title', 'selector_value' => 'h1.product-title'],
      ['field_name' => 'price', 'selector_value' => '.price .amount'],
      ['field_name' => 'image', 'selector_value' => '.product-image img'],
    ];

    foreach ($default_selectors as $selector) {
      $wpdb->insert($table_selectors, [
        'template_id'    => $default_template_id,
        'field_name'     => $selector['field_name'],
        'selector_value' => $selector['selector_value'],
      ]);
    }
  }

  // Check if 'wc_product_id' column exists before running backfill
  $has_wc_column = $wpdb->get_var("SHOW COLUMNS FROM $table_urls LIKE 'wc_product_id'");
  if ($has_wc_column) {
    $rows = $wpdb->get_results("SELECT id, product_url FROM $table_urls WHERE wc_product_id IS NULL OR wc_product_id = 0");

    foreach ($rows as $row) {
      $product_id = ajdwp_apm_get_existing_product_id($row->product_url);
      if ($product_id) {
        $wpdb->update($table_urls, ['wc_product_id' => $product_id], ['id' => $row->id]);
      }
    }
  }
}
