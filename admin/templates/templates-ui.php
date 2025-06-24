<?php
global $wpdb;

// Fetch all templates sorted by name
$templates = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ajdwp_templates ORDER BY name ASC");
?>

<!-- ===========================
     Template Selector Dropdown
=========================== -->
<hr style="margin: 40px 0;">
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

<!-- Placeholder for dynamically loaded template data -->
<div id="template-details-container" style="margin-top: 20px;"></div>

<!-- ===========================
     Admin Controls for Template (AJAX Populated)
=========================== -->
<div id="selected-template-panel" style="margin-top: 30px;"></div>

<hr style="margin: 40px 0;">

<!-- ===========================
     Add New Template Form
=========================== -->
<div>
    <h3>➕ Add New Template</h3>
    <input type="text" id="new-template-name" placeholder="Enter template name..." style="min-width: 250px;" />
    <button id="add-template-btn" class="button button-primary">Add Template</button>
</div>