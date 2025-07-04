<?php
//_____________________________________ tab4-ai-settings-init.php _____________________________________//
// Only run in admin

if (is_admin()) {
    add_action('admin_init', function () {
        register_setting('ajdwp_settings_group', 'ajdwp_openai_api_key');

        add_settings_section('ajdwp_ai_section', '', null, 'ajdwp_settings');

        add_settings_field('ajdwp_openai_api_key', 'OpenAI API Key', function () {
            $key = get_option('ajdwp_openai_api_key');

            echo '<p style="max-width:600px;font-size:13px;color:#555;">
                <a href="#" id="ajdwp-show-api-help">❓ How to get an OpenAI API key?</a>
            </p>';

            echo "<input type='text' name='ajdwp_openai_api_key' value='" . esc_attr($key) . "' style='width: 500px;' placeholder='sk-...'>";

?>
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
                <button id="ajdwp-close-api-help" class="button button-secondary">Close</button>
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
                    closeBtn.addEventListener("click", function() {
                        modal.style.display = "none";
                    });
                });
            </script>
<?php
        }, 'ajdwp_settings', 'ajdwp_ai_section');
    });
}
?>