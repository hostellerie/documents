<?php

/* Regression coverage for legacy encoded select/radio labels. */

$root = dirname(__DIR__);
$source = file_get_contents($root . '/public_document.php');
if ($source === false) {
    fwrite(STDERR, "Unable to read public_document.php\n");
    exit(1);
}

if (strpos($source, "html_entity_decode($display, ENT_QUOTES, 'UTF-8')") === false) {
    fwrite(STDERR, "Choice labels are not normalized before final HTML escaping.\n");
    exit(1);
}

$legacy = 'Petit déjeuner &amp; confitures';
$decoded = html_entity_decode($legacy, ENT_QUOTES, 'UTF-8');
$rendered = htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
if ($rendered !== 'Petit déjeuner &amp; confitures') {
    fwrite(STDERR, "Legacy choice label normalization failed.\n");
    exit(1);
}

/* The browser must display a single ampersand, not the literal entity text. */
if (html_entity_decode($rendered, ENT_QUOTES, 'UTF-8') !== 'Petit déjeuner & confitures') {
    fwrite(STDERR, "Rendered choice label is still double encoded.\n");
    exit(1);
}

echo "OK\n";
