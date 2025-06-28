<?php
// ====================== settings-page.php ====================== //
// Load scraper functions
require_once plugin_dir_path(__FILE__) . '/../includes/scraper.php';

?>

<div class="wrap">
    <h1>Auto Product Maker</h1>

    <h2 class="nav-tab-wrapper">
        <a href="#tab-scraper" class="nav-tab nav-tab-active">🌐 Scraping Form</a>
        <a href="#tab-manager" class="nav-tab">🗂️ Template Manager</a>
        <a href="#tab-add" class="nav-tab">➕ Add New Template</a>
    </h2>

    <div id="tab-scraper" class="ajdwp-tab-content" style="display: block;">
        <?php include AJDWPAPM_PATH . 'templates/tab1-product-scrape-form.php'; ?>
    </div>

    <div id="tab-manager" class="ajdwp-tab-content" style="display: none;">
        <?php include AJDWPAPM_PATH . 'templates/tab2-templates-manager.php'; ?>
    </div>

    <div id="tab-add" class="ajdwp-tab-content" style="display: none;">
        <?php include AJDWPAPM_PATH . 'templates/tab3-add-new-template.php'; ?>
    </div>
</div>

<script>
    jQuery(function($) {
        $(".nav-tab").on("click", function(e) {
            e.preventDefault();

            $(".nav-tab").removeClass("nav-tab-active");
            $(this).addClass("nav-tab-active");

            $(".ajdwp-tab-content").hide();
            $($(this).attr("href")).show();
        });
    });
</script>