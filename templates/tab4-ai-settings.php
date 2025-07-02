<?php
// Prevent direct access
if (!defined('ABSPATH') || !is_admin()) {
    exit;
}
?>

<h2>⚛ AI Settings</h2>

<form method="post" action="options.php">
    <?php
    settings_fields('ajdwp_settings_group');     // nonce + hidden fields
    do_settings_sections('ajdwp_settings');      // output your input
    submit_button('💾 Save API Key');
    ?>
</form>