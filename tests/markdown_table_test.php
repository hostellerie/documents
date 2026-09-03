<?php

$root = dirname(__DIR__);
$_SERVER['PHP_SELF'] = 'tests/markdown_table_test.php';
require_once $root . '/markdown.php';

$markdown = "| Aide | Montant |\n| :--- | ---: |\n| **MaPrimeAdapt’** | 70 % |\n| <script>alert(1)</script> | 22 000 € |";
$html = DOCUMENTS_renderMarkdownTextarea($markdown);

$checks = array(
    'table wrapper' => strpos($html, 'documents-markdown-table-wrap') !== false,
    'table element' => strpos($html, '<table class="documents-markdown-table">') !== false,
    'thead' => strpos($html, '<thead><tr>') !== false,
    'header cells' => strpos($html, '<th scope="col" style="text-align:left">Aide</th>') !== false,
    'right alignment' => strpos($html, '<th scope="col" style="text-align:right">Montant</th>') !== false,
    'body row' => strpos($html, '<td style="text-align:left"><strong>MaPrimeAdapt’</strong></td>') !== false,
    'html escaped' => strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false,
    'raw html rejected' => strpos($html, '<script>') === false,
    'no h1' => stripos($html, '<h1') === false
);

$failed = array();
foreach ($checks as $label => $ok) {
    if (!$ok) {
        $failed[] = $label;
    }
}

if (!empty($failed)) {
    fwrite(STDERR, "Markdown table regression failed: " . implode(', ', $failed) . "\n" . $html . "\n");
    exit(1);
}

echo "Markdown table regression passed.\n";
