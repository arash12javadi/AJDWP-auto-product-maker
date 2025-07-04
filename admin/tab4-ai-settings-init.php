<?php
//_____________________________________ tab4-ai-settings-init.php _____________________________________//
// Only run in admin

if (is_admin()) {
    add_action('admin_init', function () {
        // Register all settings
        register_setting('ajdwp_settings_group', 'ajdwp_openai_api_key');
        register_setting('ajdwp_settings_group', 'ajdwp_ai_prompt_title');
        register_setting('ajdwp_settings_group', 'ajdwp_ai_prompt_short');
        register_setting('ajdwp_settings_group', 'ajdwp_ai_prompt_long');
        register_setting('ajdwp_settings_group', 'ajdwp_ai_temperature');
        register_setting('ajdwp_settings_group', 'ajdwp_ai_max_tokens');

        add_settings_section('ajdwp_ai_section', '', null, 'ajdwp_settings');




        // ================= API Key ==================
        add_settings_field('ajdwp_openai_api_key', 'OpenAI API Key', function () {
            $key = get_option('ajdwp_openai_api_key');
            echo "<input type='text' name='ajdwp_openai_api_key' value='" . esc_attr($key) . "' style='width: 500px;' placeholder='sk-...'>";

?>
            <p style="max-width:600px;font-size:13px;color:#555;">
                <a href="#" id="ajdwp-show-api-help">❓ How to get an OpenAI API key?</a>
            </p>
            <div id="ajdwp-api-help-modal" style="display:none;position:fixed;top:10%;left:50%;transform:translateX(-50%);background:#fff;padding:20px;border:1px solid #ccc;box-shadow:0 4px 10px rgba(0,0,0,0.2);z-index:9999;max-width:600px;">
                <h2>🔑 How to Get an OpenAI API Key</h2>
                <ol>
                    <li>Go to <a href="https://platform.openai.com/" target="_blank">https://platform.openai.com/</a> and log in or create an account.</li>
                    <li>Once logged in, go to <a href="https://platform.openai.com/account/api-keys" target="_blank">API Keys page</a>.</li>
                    <li>Click on <strong>+ Create new secret key</strong>.</li>
                    <li>Copy the generated key. It looks like this: <code>sk-xxxxxxxxxxxx</code></li>
                    <li>Paste the key into the field below and save.</li>
                    <li>You can view usage & manage keys at <a href="https://platform.openai.com/account/usage" target="_blank">Usage Dashboard</a>.</li>
                </ol>
                <button type="button" id="ajdwp-close-api-help" class="button button-secondary">Close</button>
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const link = document.getElementById("ajdwp-show-api-help");
                    const modal = document.getElementById("ajdwp-api-help-modal");
                    const closeBtn = document.getElementById("ajdwp-close-api-help");

                    link.addEventListener("click", function(e) {
                        e.preventDefault();
                        modal.style.display = "block";
                    });
                    closeBtn.addEventListener("click", function(e) {
                        e.preventDefault();
                        modal.style.display = "none";
                    });
                });
            </script>
<?php
        }, 'ajdwp_settings', 'ajdwp_ai_section');


        // ================= Prompts ==================
        add_settings_field('ajdwp_ai_prompt_title', 'Prompt for Title', function () {
            $val = get_option('ajdwp_ai_prompt_title', 'Refine this product title for SEO: "{content}"');
            echo "<textarea name='ajdwp_ai_prompt_title' rows='2' style='width:100%;max-width:600px;'>" . esc_textarea($val) . "</textarea>";
        }, 'ajdwp_settings', 'ajdwp_ai_section');

        add_settings_field('ajdwp_ai_prompt_short', 'Prompt for Short Description', function () {
            $val = get_option('ajdwp_ai_prompt_short', 'Make this short product description more appealing and SEO-friendly:\n\n{content}');
            echo "<textarea name='ajdwp_ai_prompt_short' rows='3' style='width:100%;max-width:600px;'>" . esc_textarea($val) . "</textarea>";
        }, 'ajdwp_settings', 'ajdwp_ai_section');

        add_settings_field('ajdwp_ai_prompt_long', 'Prompt for Long Description', function () {
            $val = get_option('ajdwp_ai_prompt_long', 'Improve this long product description for clarity, engagement and SEO:\n\n{content}');
            echo "<textarea name='ajdwp_ai_prompt_long' rows='4' style='width:100%;max-width:600px;'>" . esc_textarea($val) . "</textarea>";
        }, 'ajdwp_settings', 'ajdwp_ai_section');


        // ================= Temperature ==================
        add_settings_field('ajdwp_ai_temperature', 'Temperature (0–1)', function () {
            $val = get_option('ajdwp_ai_temperature', '0.7');
            echo "<input type='number' step='0.1' min='0' max='1' name='ajdwp_ai_temperature' value='" . esc_attr($val) . "' style='width:80px;'> Default: 0.7";
        }, 'ajdwp_settings', 'ajdwp_ai_section');

        // ================= Max Tokens ==================
        add_settings_field('ajdwp_ai_max_tokens', 'Max Tokens', function () {
            $val = get_option('ajdwp_ai_max_tokens', '500');
            echo "<input type='number' min='50' max='4000' name='ajdwp_ai_max_tokens' value='" . esc_attr($val) . "' style='width:80px;'> Default: 500";
        }, 'ajdwp_settings', 'ajdwp_ai_section');
    });
}
?>