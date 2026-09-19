<?php

$document = file_get_contents(dirname(__DIR__) . '/public_html/document.php');
$category = file_get_contents(dirname(__DIR__) . '/public_html/category-list.php');
$home = file_get_contents(dirname(__DIR__) . '/public_html/home.php');
$french = file_get_contents(dirname(__DIR__) . '/language/french_france_utf-8.php');

$checks = array(
    array($document, "page['category']['cat_name']", 'document breadcrumb category fallback'),
    array($category, 'customHeaderHasH1', 'category custom H1 detection'),
    array($category, 'if (!$customHeaderHasH1)', 'category default H1 guard'),
    array($home, 'mainFooterHasH2', 'footer H2 detection'),
    array($home, 'if (!$mainFooterHasH2)', 'footer default H2 guard'),
    array($french, "'more_information'    => 'En savoir plus'", 'French more information label')
);

foreach ($checks as $check) {
    if (strpos($check[0], $check[1]) === false) {
        fwrite(STDERR, 'Missing ' . $check[2] . "\n");
        exit(1);
    }
}

echo "Public navigation and heading guards are present.\n";
