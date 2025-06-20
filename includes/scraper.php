<?php

function ajdwp_apm_scrape_product_data($url)
{
    $html = @file_get_contents($url);
    if (!$html) return false;

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    // Example scraping logic
    $titleNode = $xpath->query('//title');
    $title = $titleNode->length ? $titleNode->item(0)->nodeValue : '';

    $description = "Auto-generated product. Source: $url";

    return [
        'title' => sanitize_text_field($title),
        'description' => sanitize_textarea_field($description),
        'price' => '9.99', // Static for now
        'image' => null // We'll add image scraping next
    ];
}
