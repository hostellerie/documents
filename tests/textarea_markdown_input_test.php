<?php

$root = dirname(__DIR__);
require_once $root . '/security.php';

$input = "Intro\n\n## Section\n\n* Item one\n* Item two\n\n<script>alert(1)</script>";
$output = DOCUMENTS_normalizeFieldInput('textarea', $input);

$checks = array(
    strpos($output, "\n\n## Section\n\n") !== false,
    strpos($output, "\n* Item one\n* Item two") !== false,
    strpos($output, '<script>') === false,
    strpos($output, 'alert(1)') !== false
);

foreach ($checks as $check) {
    if (!$check) {
        fwrite(STDERR, "Textarea Markdown normalization regression.\n");
        exit(1);
    }
}

echo "Textarea Markdown input test passed.\n";
