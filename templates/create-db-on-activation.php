<?php

function ajdwp_apm_create_db_tables()
{
  global $wpdb;

  $charset_collate   = $wpdb->get_charset_collate();
  $table_templates   = $wpdb->prefix . 'ajdwp_templates';
  $table_selectors   = $wpdb->prefix . 'ajdwp_template_selectors';
  $table_urls        = $wpdb->prefix . 'ajdwp_template_urls';

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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  ) $charset_collate;";
  dbDelta($sql_templates);

  // Selectors Table
  $sql_selectors = "CREATE TABLE $table_selectors (
    id INT NOT NULL AUTO_INCREMENT,
    template_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    selector_value TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX (template_id)
  ) $charset_collate;";
  dbDelta($sql_selectors);

  // URLs Table with DECIMAL price
  $sql_urls = "CREATE TABLE $table_urls (
    id INT NOT NULL AUTO_INCREMENT,
    template_id INT NOT NULL,
    product_url TEXT NOT NULL,
    title TEXT,
    price DECIMAL(10,2),
    image TEXT,
    last_scraped DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (template_id)
  ) $charset_collate;";
  dbDelta($sql_urls);

  // ✅ Ensure price is DECIMAL in case it's old install
  $wpdb->query("ALTER TABLE $table_urls MODIFY price DECIMAL(10,2)");

  // Insert default template
  $default_exists = $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM $table_templates WHERE name = %s", 'Default Template')
  );

  if (!$default_exists) {
    $wpdb->insert($table_templates, ['name' => 'Default Template']);
  }
}
