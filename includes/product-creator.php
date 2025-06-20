<?php

function ajdwp_apm_create_product($data)
{
    $product = new WC_Product_Simple();
    $product->set_name($data['title']);
    $product->set_description($data['description']);
    $product->set_regular_price($data['price']);
    $product->set_catalog_visibility('visible');
    $product->set_status('publish');
    $product->save();
    return $product->get_id();
}
