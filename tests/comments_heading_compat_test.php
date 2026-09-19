<?php

$root = dirname(__DIR__);
$failures = array();

$rendererPath = $root . DIRECTORY_SEPARATOR . 'public_document.php';
$templatePath = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'document.thtml';

$renderer = is_file($rendererPath) ? file_get_contents($rendererPath) : false;
$template = is_file($templatePath) ? file_get_contents($templatePath) : false;

if ($renderer === false) {
    $failures[] = 'Unable to read public_document.php.';
} else {
    if (strpos($renderer, "version_compare((string) VERSION, '2.2.2', '<')") === false) {
        $failures[] = 'Geeklog 2.1.1/2.2.2 comments heading compatibility guard is missing.';
    }
    if (strpos($renderer, "set_var('comments_heading', \$commentsHeading)") === false) {
        $failures[] = 'Comments heading template variable is not populated.';
    }
}

if ($template === false) {
    $failures[] = 'Unable to read templates/document.thtml.';
} else {
    if (strpos($template, '{comments_heading}') === false) {
        $failures[] = 'Default document template does not use the conditional comments heading.';
    }
    if (strpos($template, '<h2 class="documents-document__comments-title">{comments_title}</h2>') !== false) {
        $failures[] = 'Default template still hard-codes a comments heading and can duplicate Geeklog 2.2.2 output.';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents comments heading compatibility checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents comments heading compatibility checks: PASS\n";
