<?php

$root = dirname(__DIR__);
$failures = array();

function documents_custom_template_structure_read($root, $path, &$failures)
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        $failures[] = 'Missing file: ' . $path;
        return '';
    }
    $content = file_get_contents($file);
    if ($content === false) {
        $failures[] = 'Unable to read: ' . $path;
        return '';
    }
    return $content;
}

$renderer = documents_custom_template_structure_read($root, 'public_document.php', $failures);
$controller = documents_custom_template_structure_read($root, 'public_html/document.php', $failures);

if (strpos($renderer, "'custom_template' => \$customTemplate ? 1 : 0") === false) {
    $failures[] = 'Public renderer must expose whether a category uses a custom template.';
}
if (strpos($controller, "\$isCustomTemplate = !empty(\$page['custom_template']);") === false) {
    $failures[] = 'Document controller must detect custom-template rendering.';
}
if (strpos($controller, "preg_match('/<h1\\b/i', \$body)") === false) {
    $failures[] = 'Document controller must detect an H1 supplied by a custom template.';
}
if (strpos($controller, 'documents-breadcrumb') === false
    || strpos($controller, '$templateHasBreadcrumb') === false) {
    $failures[] = 'Document controller must detect a breadcrumb supplied by a custom template.';
}
if (strpos($controller, 'if (!$templateHasH1)') === false) {
    $failures[] = 'Default document H1 must be conditional when a custom template already supplies one.';
}
if (strpos($controller, 'if (!$templateHasBreadcrumb)') === false) {
    $failures[] = 'Default document breadcrumb must be conditional when a custom template already supplies one.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents custom template structure checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents custom template structure checks: PASS\n";
