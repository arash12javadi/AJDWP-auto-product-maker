<?php
defined('ABSPATH') || exit;
// ===========================
//      Add New Template Form
// ===========================

?>
<h2>➕ Add New Template</h2>
<div id="ajdwp-add-template">
    <input type="text" id="new-template-name" placeholder="Enter template name..." style="min-width: 250px;" />
    <br><br>
    <select name="scrape_method">
        <option value="auto">Auto (Try HTML first)</option>
        <option value="static">Static (faster, for simple sites)</option>
        <option value="dynamic">Dynamic (JS-rendered sites)</option>
    </select>
    <br><br>
    <input type="text" name="title_selector" placeholder="Title Selector">
    <span id="title_selector" class="temp-default-exp">meta[property="og:title"]</span><br><br>

    <input type="text" name="short_description_selector" placeholder="Short Description Selector">
    <span id="short_description_selector" class="temp-default-exp">meta[name="description"], meta[property="og:description"]</span><br><br>

    <input type="text" name="long_description_selector" placeholder="Long Description Selector">
    <span id="long_description_selector" class="temp-default-exp">div.woocommerce-Tabs-panel--description, div.product-description, div#tab-description</span><br><br>

    <input type="text" name="main_image_selector" placeholder="Main Image Selector">
    <span id="main_image_selector" class="temp-default-exp">img.wp-post-image, .woocommerce-product-gallery__image img</span><br><br>

    <input type="text" name="gallery_image_selectors" placeholder="Gallery Image Selectors (comma-separated)">
    <span id="gallery_image_selectors" class="temp-default-exp">div.woocommerce-product-gallery__wrapper img</span><br><br>

    <input type="text" name="price_selector" placeholder="Price Selector">
    <span id="price_selector" class="temp-default-exp">div.summary.entry-summary p ins span bdi, span.woocommerce-Price-amount bdi</span><br><br>

    <input type="text" name="price_multiplier" placeholder="Price Multiplier (e.g. x1.2 or +5)">
    <br><br>
    <button id="add-template-btn" class="button button-primary">Add Template</button>
</div>

<p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>

<!-- ===========================
     Template Selector Dropdown
=========================== -->

<div style="margin-bottom: 20px;">
    <label for="tab3-template_id">
        <h2>🧩 Select a Template:</h2>
    </label>
    <select name="tab3-template_id" id="tab3_template_id" style="min-width: 250px;">
        <option value="">-- Choose Template --</option>
        <?php foreach ($templates as $template): ?>
            <option value="<?= esc_attr($template->id) ?>">
                <?= esc_html($template->name) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- ===========================
     Admin Controls for Template (AJAX Populated)
=========================== -->
<div id="tab3-selected-template-panel" style="margin-top: 30px;"></div>