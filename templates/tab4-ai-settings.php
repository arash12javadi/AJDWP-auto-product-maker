<?php
// Prevent direct access
if (!defined('ABSPATH') || !is_admin()) {
    exit;
}
?>

<strong>⚛ AI Settings</strong>

<form method="post" action="options.php">
    <?php
    settings_fields('ajdwp_settings_group');     // nonce + hidden fields
    do_settings_sections('ajdwp_settings');      // output your input
    submit_button('💾 Save API Key');
    ?>
</form>