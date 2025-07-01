<?php
//_____________________________________ tab2-product-manager.php _____________________________________//

global $wpdb;

// Fetch all templates sorted by name
$templates = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}ajdwp_templates
    ORDER BY 
        CASE WHEN name = 'Default Template' THEN 0 ELSE 1 END,
        name ASC
");

?>

<!-- ===========================
     Template Selector Dropdown
=========================== -->

<div style="margin-bottom: 20px;">
    <label for="template_id"><strong>🧩 Select a Template:</strong></label>
    <select name="template_id" id="template_id" style="min-width: 250px;">
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
<div id="selected-template-panel" style="margin-top: 30px;"></div>


<!-- 
//==========================
//  inline product title and price edit popup
//========================== 
-->

<div id="ajdwp-edit-popup" style="display:none; position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; border:1px solid #ccc; padding:20px; z-index:10000;">
    <h2 id="ajdwp-edit-popup-title">Edit</h2>
    <form id="ajdwp-edit-popup-form">
        <input type="hidden" id="ajdwp-edit-popup-type" value="">
        <input type="hidden" id="ajdwp-edit-popup-cid" value="">
        <input type="hidden" id="ajdwp-edit-popup-wid" value="">
        <div id="ajdwp-edit-popup-field"></div>
        <div style="margin-top:10px;">
            <button type="submit" class="button button-primary">Save</button>
            <button type="button" class="button" id="ajdwp-edit-popup-cancel">Cancel</button>
        </div>
    </form>
</div>