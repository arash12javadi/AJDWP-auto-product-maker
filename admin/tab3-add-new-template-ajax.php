<?php

//==========================
//  AJAX: Add Template
//==========================

add_action('wp_ajax_ajdwp_add_template', 'ajdwp_apm_ajax_add_template');
function ajdwp_apm_ajax_add_template()
{
    check_ajax_referer('ajdwp_template_nonce');
    global $wpdb;

    $table = "{$wpdb->prefix}ajdwp_templates";

    $data = [
        'name'                          => sanitize_text_field($_POST['name'] ?? ''),
        'title_selector'                => sanitize_text_field($_POST['title_selector'] ?? ''),
        'short_description_selector'    => sanitize_text_field($_POST['short_description_selector'] ?? ''),
        'long_description_selector'     => sanitize_text_field($_POST['long_description_selector'] ?? ''),
        'main_image_selector'           => sanitize_text_field($_POST['main_image_selector'] ?? ''),
        'gallery_image_selectors'       => sanitize_text_field($_POST['gallery_image_selectors'] ?? ''),
        'price_selector'                => sanitize_text_field($_POST['price_selector'] ?? ''),
        'price_multiplier'              => sanitize_text_field($_POST['price_multiplier'] ?? ''),
        'scrape_method'                 => sanitize_text_field($_POST['scrape_method'] ?? 'auto'),
    ];

    // Insert into database
    $result = $wpdb->insert($table, $data);

    if ($result === false) {
        wp_send_json_error([
            'message' => '❌ Insert failed. Check database columns and data types.',
            'sql'     => $wpdb->last_query,
            'error'   => $wpdb->last_error,
        ]);
    }

    // Success
    wp_send_json_success([
        'id'   => $wpdb->insert_id,
        'name' => $data['name'],
    ]);
}


// ============================
// AJAX: Rename and Delete Templates
// ============================

add_action('wp_ajax_ajdwp_update_template', 'ajdwp_apm_ajax_update_template');
add_action('wp_ajax_ajdwp_delete_template', 'ajdwp_apm_ajax_delete_template');


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
// AJAX: Load Template Panel
// ============================
add_action('wp_ajax_ajdwp_tab3_get_template_panel', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $template_id = intval($_POST['template_id']);
    if (!$template_id) {
        wp_send_json_error(['message' => 'No template selected']);
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

    ob_start();
?>

    <div class="ajdwp-template-header">
        <h2 style="color: darkblue;font-size: 30px;"><?= esc_html($template->name) ?></h2>
        <?php if ((int) $template->id !== 1): ?>
            <div style="margin-bottom: 10px;">
                <button class="button rename-template" data-id="<?= esc_attr($template->id) ?>">✏ Rename Template</button>
                <button class="button delete-template" data-id="<?= esc_attr($template->id) ?>">🗑 Delete Template</button>
            </div>
        <?php endif; ?>
    </div>
    <p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>
    <div class="ajdwp-template-selectors" style="margin: 20px 0;">
        <h3>🔧 Scraping Selectors</h3>
        <table class="form-table">
            <?php
            $fields = [
                'title_selector' => 'Title Selector',
                'short_description_selector' => 'Short Description',
                'long_description_selector' => 'Long Description',
                'main_image_selector' => 'Main Image',
                'gallery_image_selectors' => 'Gallery Images',
                'price_selector' => 'Price Selector',
                'price_multiplier' => 'Price Multiplier',
                'scrape_method' => 'Scraping Method',
            ];
            foreach ($fields as $field => $label):
                $value = esc_html($template->$field);
            ?>
                <tr>
                    <th><?= $label ?></th>
                    <td>
                        <p
                            class="ajdwp-editable-selector"
                            data-field="<?= esc_attr($field) ?>"
                            data-value="<?= esc_attr($value) ?>"
                            data-id="<?= esc_attr($template->id) ?>"
                            style="cursor:pointer;color:#2271b1;">
                            <?= $value ?: '<em>Not set</em>' ?>
                        </p>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

<?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});


// ============================
// AJAX: Update element selector Fields of the templates
// ============================

add_action('wp_ajax_ajdwp_update_single_template_field', function () {
    check_ajax_referer('ajdwp_template_nonce');

    global $wpdb;
    $id = intval($_POST['id']);
    $field = sanitize_key($_POST['field']);
    $value = sanitize_text_field(wp_unslash($_POST['value']));

    // whitelist allowed fields
    $allowed = [
        'title_selector',
        'short_description_selector',
        'long_description_selector',
        'main_image_selector',
        'gallery_image_selectors',
        'price_selector',
        'price_multiplier',
        'scrape_method'
    ];

    if (!in_array($field, $allowed, true)) {
        wp_send_json_error(['message' => 'Invalid field']);
    }

    $updated = $wpdb->update(
        "{$wpdb->prefix}ajdwp_templates",
        [$field => $value],
        ['id' => $id]
    );

    if ($updated !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
});
