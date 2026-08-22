<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');
$runtime = file_get_contents($root . '/runtime.php');
$includeEdit = file_get_contents($root . '/include_edit.php');
$includeHtml = file_get_contents($root . '/include_html.php');
$failures = array();

function integration_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

integration_require($functions, 'function plugin_whatsnewsupported_documents()', 'What\'s New support callback is missing.', $failures);
integration_require($functions, 'function plugin_getwhatsnew_documents()', 'What\'s New content callback is missing.', $failures);
integration_require($functions, "WHERE d.active=1", 'What\'s New/search published-document guard is missing.', $failures);
integration_require($functions, "COM_getPermSQL('AND', 0, 2, 'd')", 'What\'s New/search permission filter is missing.', $failures);
integration_require($functions, ' AS description', 'Search result description column is missing.', $failures);
integration_require($functions, "fd.f_type='text'", 'Search description is not sourced from text fields.', $failures);
integration_require($runtime, 'DOCUMENTS_homeStatsBlock()', 'Documents home statistics integration is missing.', $failures);
integration_require($includeEdit, 'DOCUMENTS_textFormatOptions(', 'Text display-format selector is missing from field editor.', $failures);
integration_require($includeHtml, 'DOCUMENTS_formatTextDisplay(', 'Text display formatting is not applied to rendered documents.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents integration surface checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents integration surface checks: PASS\n";
