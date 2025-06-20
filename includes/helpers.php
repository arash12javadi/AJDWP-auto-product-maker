<?php

/**
 * Check if a product already exists by its scraped source URL.
 *
 * @param string $url The product source URL.
 * @return bool True if product exists, false otherwise.
 */
function ajdwp_apm_is_duplicate($url)
{
    if (empty($url)) return false;

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'any',
        'meta_key'       => '_ajdwp_source_url',
        'meta_value'     => $url,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ];

    $query = new WP_Query($args);
    return !empty($query->posts);
}

/**
 * Get the ID of an existing product created from a given URL.
 *
 * @param string $url The product source URL.
 * @return int|false Product ID if found, false otherwise.
 */
function ajdwp_apm_get_existing_product_id($url)
{
    if (empty($url)) return false;

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'any',
        'meta_key'       => '_ajdwp_source_url',
        'meta_value'     => $url,
        'fields'         => 'ids',
        'posts_per_page' => 1,
    ];

    $query = new WP_Query($args);
    return !empty($query->posts) ? $query->posts[0] : false;
}
