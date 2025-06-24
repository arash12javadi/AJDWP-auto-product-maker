<?php
if (!isset($template_id)) return;

$template = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ajdwp_templates WHERE id = %d",
    $template_id
));

$urls = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ajdwp_template_urls WHERE template_id = %d",
    $template_id
));

?>
<div class="ajdwp-template-panel">
    <h3>🧩 <?= esc_html($template->name) ?></h3>

    <div style="margin-bottom: 15px;">
        <button class="button rename-template" data-id="<?= esc_attr($template->id) ?>">✏️ Rename</button>
        <button class="button delete-template" data-id="<?= esc_attr($template->id) ?>">🗑️ Delete</button>
    </div>

    <div style="margin-bottom: 10px;">
        <input type="url" class="new-template-url" placeholder="Add product URL..." data-template-id="<?= esc_attr($template_id) ?>">
        <button class="button add-template-url" data-template-id="<?= esc_attr($template_id) ?>">➕ Add URL</button>
    </div>

    <ul class="url-list" data-template-id="<?= esc_attr($template_id) ?>" style="margin-top: 10px;">
        <?php if (!empty($urls)) : foreach ($urls as $url) :
                $image = ajdwp_apm_get_image_preview_from_url($url->product_url);
        ?>
                <li data-id="<?= esc_attr($url->id) ?>" style="margin-bottom: 8px;">
                    <?php if ($image): ?>
                        <img src="<?= esc_url($image) ?>" alt="Preview" style="width: 40px; height: auto; margin-right: 6px; vertical-align: middle;">
                    <?php else: ?>
                        <img src="https://www.google.com/s2/favicons?domain=<?= parse_url($url->product_url, PHP_URL_HOST) ?>" style="width: 16px; margin-right: 6px; vertical-align: middle;">
                    <?php endif; ?>

                    <a href="<?= esc_url($url->product_url) ?>" target="_blank"><?= esc_html($url->product_url) ?></a>

                    <button class="button delete-template-url" data-id="<?= esc_attr($url->id) ?>" style="margin-left: 10px;">🗑️</button>
                </li>
            <?php endforeach;
        else: ?>
            <li><em>No URLs yet for this template.</em></li>
        <?php endif; ?>
    </ul>
</div>