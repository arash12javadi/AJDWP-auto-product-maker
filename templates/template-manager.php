<?php
global $wpdb;

$templates = $wpdb->prefix . 'ajdwp_templates';
$selectors = $wpdb->prefix . 'ajdwp_template_selectors';
$urls      = $wpdb->prefix . 'ajdwp_template_urls';

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

dbDelta("
CREATE TABLE $templates (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
)");

dbDelta("
CREATE TABLE $selectors (
  id INT NOT NULL AUTO_INCREMENT,
  template_id INT NOT NULL,
  field_name VARCHAR(50) NOT NULL,
  selector_value TEXT NOT NULL,
  PRIMARY KEY (id)
)");

dbDelta("
CREATE TABLE $urls (
  id INT NOT NULL AUTO_INCREMENT,
  template_id INT NOT NULL,
  product_url TEXT NOT NULL,
  last_scraped DATETIME DEFAULT NULL,
  status VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (id)
)");
