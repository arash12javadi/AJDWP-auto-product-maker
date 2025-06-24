<?php

add_action('wp_ajax_ajdwp_get_template_panel', function () {
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
    <div>
        <h3><?= esc_html($template->name) ?></h3>

        <div style="margin-bottom: 10px;">
            <button class="button rename-template" data-id="<?= esc_attr($template->id) ?>">✏️ Rename</button>
            <button class="button delete-template" data-id="<?= esc_attr($template->id) ?>">🗑️ Delete</button>
        </div>

        <div style="margin-bottom: 15px;">
            <input type="url" class="new-template-url" placeholder="Add URL..." data-template-id="<?= esc_attr($template->id) ?>">
            <button class="button add-template-url" data-template-id="<?= esc_attr($template->id) ?>">➕ Add URL</button>
        </div>

        <ul class="url-list" data-template-id="<?= esc_attr($template->id) ?>" style="margin-top: 10px;">
            <?php if (!empty($urls)) : ?>
                <?php foreach ($urls as $url) : ?>
                    <li data-id="<?= esc_attr($url->id) ?>">
                        <img src="https://www.google.com/s2/favicons?domain=<?= esc_url(parse_url($url->product_url, PHP_URL_HOST)) ?>"
                            alt="favicon" style="width: 16px; margin-right: 5px;" />
                        <?= esc_url($url->product_url) ?>
                        <input type="checkbox" name="selected_urls[]" value="<?= esc_attr($url->id) ?>" style="margin-left: 10px;">
                        <button class="button delete-template-url" data-id="<?= esc_attr($url->id) ?>">🗑️</button>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><em>No URLs yet.</em></li>
            <?php endif; ?>
        </ul>
    </div>
<?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});
