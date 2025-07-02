<?php
// Only run in admin
if (is_admin()) {
    add_action('admin_init', function () {
        register_setting('ajdwp_settings_group', 'ajdwp_openai_api_key');

        add_settings_section('ajdwp_ai_section', '', null, 'ajdwp_settings');

        add_settings_field('ajdwp_openai_api_key', 'OpenAI API Key', function () {
            $key = get_option('ajdwp_openai_api_key');
            echo "<input type='text' name='ajdwp_openai_api_key' value='" . esc_attr($key) . "' style='width: 500px;' placeholder='sk-...'>";
        }, 'ajdwp_settings', 'ajdwp_ai_section');
    });
}
