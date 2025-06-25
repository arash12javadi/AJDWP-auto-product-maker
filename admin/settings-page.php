<?php
// Load scraper functions
require_once plugin_dir_path(__FILE__) . '/../includes/scraper.php';

?>

<div class="wrap ">
    <h1>Auto Product Maker</h1>

    <!-- =========================== Product Scraper Section =========================== -->
    <div class="page-setting-sections">
        <h2>🌐 Scraping Form</h2>
        <div id="ajdwp-scraper-form-section">
            <?php include AJDWPAPM_PATH . 'templates/product_scrape_form.php'; ?>
        </div>
    </div>

    <br>
    <!-- =========================== Template Manager Section =========================== -->

    <div class="page-setting-sections">
        <h2>🗂️ Template Manager</h2>
        <div id="ajdwp-template-manager">
            <?php include AJDWPAPM_PATH . 'templates/templates-manager.php'; ?>
        </div>
    </div>

    <br>
    <!-- =========================== Add New Template Section =========================== -->
    <div class="page-setting-sections">
        <h2>➕ Add New Template</h2>
        <div id="ajdwp-template-manager">
            <?php include AJDWPAPM_PATH . 'templates/add_new_template.php'; ?>
        </div>
    </div>

    <br>

</div>