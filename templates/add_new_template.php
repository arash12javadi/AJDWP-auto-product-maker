<?php

// ===========================
//      Add New Template Form
// ===========================

?>
<div id="ajdwp-add-template">
    <input type="text" id="new-template-name" placeholder="Enter template name..." style="min-width: 250px;" />
    <br><br>
    <select name="scrape_method">
        <option value="auto">Auto</option>
        <option value="html">HTML Only</option>
        <option value="js">JavaScript (Playwright)</option>
    </select>
    <br><br>
    <input type="text" name="title_selector" placeholder="Title Selector"><br><br>
    <input type="text" name="short_desc_selector" placeholder="Short Description Selector"><br><br>
    <input type="text" name="long_desc_selector" placeholder="Long Description Selector"><br><br>
    <input type="text" name="main_image_selector" placeholder="Main Image Selector"><br><br>
    <input type="text" name="gallery_image_selectors" placeholder="Gallery Image Selectors (comma-separated)"><br><br>
    <input type="text" name="price_selector" placeholder="Price Selector"><br><br>
    <input type="text" name="price_multiplier" placeholder="Price Multiplier (e.g. x1.2 or +5)">
    <br><br>
    <button id="add-template-btn" class="button button-primary">Add Template</button>
</div>