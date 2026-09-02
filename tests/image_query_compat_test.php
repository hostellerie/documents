<?php

/* Regression guard for image.php URLs that were double HTML-escaped. */

$compat = file_get_contents(dirname(__DIR__) . '/include_compat.php');

$checks = array(
    "isset(\$_GET['amp;w'])",
    "\$_GET['w'] = \$_GET['amp;w'];",
    "isset(\$_GET['amp;h'])",
    "\$_GET['h'] = \$_GET['amp;h'];"
);

foreach ($checks as $needle) {
    if (strpos($compat, $needle) === false) {
        fwrite(STDERR, "Missing image query compatibility guard: {$needle}\n");
        exit(1);
    }
}

echo "image query compatibility regression checks passed\n";
