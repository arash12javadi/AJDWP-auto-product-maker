<?php
// Hook into WordPress admin menu
add_action('admin_menu', 'ajdwp_apm_register_menu');

/**
 * Register the admin menu item under WooCommerce.
 */
function ajdwp_apm_register_menu()
{
    add_menu_page(
        'Auto Product Maker',              // Page title (browser tab)
        'Auto Product Maker',              // Menu title (WP sidebar)
        'manage_woocommerce',              // Capability required
        'ajdwp-auto-product-maker',        // Menu slug
        'ajdwp_apm_render_settings_page',  // Callback function
        'dashicons-cart',                  // Icon
        56                                 // Position
    );
}

/**
 * Render the settings page by including the admin view.
 */
function ajdwp_apm_render_settings_page()
{
    include AJDWPAPM_PATH . 'admin/settings-page.php';
}
