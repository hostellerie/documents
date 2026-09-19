<?php

$home = file_get_contents(dirname(__DIR__) . '/public_html/home.php');
$css = file_get_contents(dirname(__DIR__) . '/public_html/css/documents-home.css');
if ($home === false || $css === false) {
    fwrite(STDERR, "Unable to read home category preview sources.\n");
    exit(1);
}

$checks = array(
    array($home, 'function DOCUMENTS_homeRecentDocuments', 'recent document helper'),
    array($home, 'd.active=1', 'public active status filter'),
    array($home, "COM_getPermSQL('AND', 0, 2, 'd')", 'document permission filter'),
    array($home, 'DOCUMENTS_homeRecentDocuments($category, 3)', 'three document category limit'),
    array($home, '$type === \'image\'', 'image field preview'),
    array($home, "'/image.php?src='", 'thumbnail endpoint'),
    array($home, 'documents-category-card__recent-item', 'recent document link markup'),
    array($home, 'documents-category-card__footer', 'category footer link'),
    array($css, 'object-fit: cover', 'thumbnail crop styling'),
    array($css, 'grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))', 'responsive category grid')
);

foreach ($checks as $check) {
    if (strpos($check[0], $check[1]) === false) {
        fwrite(STDERR, 'Missing ' . $check[2] . "\n");
        exit(1);
    }
}

echo "Home category recent document cards are configured.\n";
