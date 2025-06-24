<?php

// ============================
// Admin Menu Registration
// ============================
add_action('admin_menu', 'ajdwp_apm_register_menu');

function ajdwp_apm_register_menu()
{
    add_menu_page(
        'Auto Product Maker',
        'Auto Product Maker',
        'manage_woocommerce',
        'ajdwp-auto-product-maker',
        'ajdwp_apm_render_settings_page',
        'dashicons-cart',
        56
    );
}

function ajdwp_apm_render_settings_page()
{
    include AJDWPAPM_PATH . 'admin/settings-page.php';
}

// ============================
// AJAX: Template CRUD
// ============================
add_action('wp_ajax_ajdwp_add_template', 'ajdwp_apm_ajax_add_template');
add_action('wp_ajax_ajdwp_update_template', 'ajdwp_apm_ajax_update_template');
add_action('wp_ajax_ajdwp_delete_template', 'ajdwp_apm_ajax_delete_template');

function ajdwp_apm_ajax_add_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $name = sanitize_text_field($_POST['name']);
    $wpdb->insert("{$wpdb->prefix}ajdwp_templates", ['name' => $name]);

    wp_send_json_success([
        'id'   => $wpdb->insert_id,
        'name' => $name
    ]);
}

function ajdwp_apm_ajax_update_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id   = intval($_POST['id']);
    $name = sanitize_text_field($_POST['name']);

    $wpdb->update(
        "{$wpdb->prefix}ajdwp_templates",
        ['name' => $name],
        ['id'   => $id]
    );

    wp_send_json_success();
}

function ajdwp_apm_ajax_delete_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);

    // Move associated URLs to Default Template (ID 1)
    $wpdb->update("{$wpdb->prefix}ajdwp_template_urls", [
        'template_id' => 1
    ], ['template_id' => $id]);

    // Optionally clean up associated selectors
    $wpdb->delete("{$wpdb->prefix}ajdwp_template_selectors", ['template_id' => $id]);

    // Delete template itself
    $wpdb->delete("{$wpdb->prefix}ajdwp_templates", ['id' => $id]);

    wp_send_json_success();
}

// ============================
// AJAX: Template URL CRUD
// ============================
add_action('wp_ajax_ajdwp_add_template_url', 'ajdwp_apm_ajax_add_template_url');
add_action('wp_ajax_ajdwp_delete_template_url', 'ajdwp_apm_ajax_delete_template_url');

function ajdwp_apm_ajax_add_template_url()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $template_id = intval($_POST['template_id']);
    $url         = esc_url_raw($_POST['url']);

    $wpdb->insert("{$wpdb->prefix}ajdwp_template_urls", [
        'template_id' => $template_id,
        'product_url' => $url
    ]);

    wp_send_json_success([
        'id'  => $wpdb->insert_id,
        'url' => $url
    ]);
}

function ajdwp_apm_ajax_delete_template_url()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $id = intval($_POST['id']);
    $wpdb->delete("{$wpdb->prefix}ajdwp_template_urls", ['id' => $id]);

    wp_send_json_success();
}

// ============================
// AJAX: Load Template Panel
// (This can be moved to ajax-handlers.php if you're separating concerns)
// ============================
add_action('wp_ajax_ajdwp_get_template_panel', 'ajdwp_handle_get_template_panel');

function ajdwp_handle_get_template_panel()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $template_id = intval($_POST['template_id']);
    if (!$template_id) {
        wp_send_json_error(['message' => 'Template ID missing']);
    }

    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
        $template_id
    ));
    if (!$template) {
        wp_send_json_error(['message' => 'Template not found']);
    }

    $urls = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE template_id = %d",
        $template_id
    ));

    ob_start(); ?>
    <h3><?= esc_html($template->name) ?> URLs</h3>
    <div style="margin-bottom: 10px;">
        <input type="url" class="new-template-url" placeholder="Add URL..." data-template-id="<?= esc_attr($template_id) ?>">
        <button class="button add-template-url" data-template-id="<?= esc_attr($template_id) ?>">➕ Add URL</button>
    </div>
    <ul class="url-list" data-template-id="<?= esc_attr($template_id) ?>">
        <?php if (!empty($urls)) :
            foreach ($urls as $url) : ?>
                <li data-id="<?= esc_attr($url->id) ?>">
                    <?php
                    $image = ajdwp_apm_get_image_preview_from_url($url->product_url);
                    if ($image) echo '<img src="' . esc_url($image) . '" style="width:40px;height:auto;margin-right:6px;">';
                    echo esc_url($url->product_url);
                    ?>
                    <button class="button delete-template-url" data-id="<?= esc_attr($url->id) ?>">🗑️</button>
                </li>
            <?php endforeach;
        else : ?>
            <li><em>No URLs yet.</em></li>
        <?php endif; ?>
    </ul>
<?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}
