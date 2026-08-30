<?php
/* Ensures temporary diagnostic markers remain present while debugging route execution. */
$index = file_get_contents(__DIR__ . '/../public_html/index.php');
$category = file_get_contents(__DIR__ . '/../public_html/category.php');
if (strpos($index, 'DOCUMENTS DEBUG I') === false) {
    fwrite(STDERR, "Missing index diagnostic markers\n");
    exit(1);
}
if (strpos($category, 'DOCUMENTS DEBUG C') === false) {
    fwrite(STDERR, "Missing category diagnostic markers\n");
    exit(1);
}
echo "diagnostic markers present\n";
