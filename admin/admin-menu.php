<?php
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
