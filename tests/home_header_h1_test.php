<?php

/* Regression test: resolved Documents main header may provide the page H1. */

$source = file_get_contents(dirname(__DIR__) . '/public_html/home.php');
if ($source === false) {
    fwrite(STDERR, "Unable to read public_html/home.php\n");
    exit(1);
}

$required = array(
    "$mainHeader = PLG_replaceTags((string) $_DOCUMENTS_CONF['documents_main_header']);",
    "preg_match('/<h1\\b/i', $mainHeader) === 1",
    "if (!$mainHeaderHasH1)",
    "if ($mainHeader !== '')"
);

foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "Missing expected H1 handling: {$needle}\n");
        exit(1);
    }
}

$replacePos = strpos($source, 'PLG_replaceTags');
$detectPos = strpos($source, "preg_match('/<h1\\b/i'");
if ($replacePos === false || $detectPos === false || $replacePos > $detectPos) {
    fwrite(STDERR, "Autotags must be resolved before H1 detection.\n");
    exit(1);
}

echo "Documents home header H1 regression test passed.\n";
