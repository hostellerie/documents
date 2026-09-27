<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');
$runtime = file_get_contents($root . '/runtime.php');
$home = file_get_contents($root . '/public_html/home.php');
$fieldEditor = file_get_contents($root . '/public_html/field-editor.php');
$publicDocument = file_get_contents($root . '/public_document.php');
$documentController = file_get_contents($root . '/public_html/document.php');
$interop = file_get_contents($root . '/interoperability.php');
$failures = array();

function integration_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function integration_forbid($content, $needle, $message, &$failures)
{
    if ($content !== false && strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

integration_require($functions, 'function plugin_whatsnewsupported_documents()', 'What\'s New support callback is missing.', $failures);
integration_require($functions, 'function plugin_getwhatsnew_documents()', 'What\'s New content callback is missing.', $failures);
integration_require($functions, "WHERE d.active=1", 'What\'s New/search published-document guard is missing.', $failures);
integration_require($functions, "COM_getPermSQL('AND', 0, 2, 'd')", 'What\'s New/search permission filter is missing.', $failures);
integration_require($functions, ' AS description', 'Search result description column is missing.', $failures);
integration_require($functions, "fd.f_type IN ('text','textarea')", 'Search description is not sourced from descriptive text fields.', $failures);
integration_require($home, 'DOCUMENTS_homeStatsBlock()', 'Documents home statistics rendering is missing.', $failures);
integration_forbid($runtime, "\$_DOCUMENTS_CONF['documents_main_footer'] =", 'Runtime must not inject public home statistics as a side effect.', $failures);

/* Field presentation moved from historical include_edit.php/include_html.php
 * into the modern field editor and public renderer. */
integration_require($fieldEditor, "'text_format' =>", 'Text display-format selector label is missing from field editor.', $failures);
integration_require($fieldEditor, 'id="documents-text-format"', 'Text display-format selector is missing from field editor.', $failures);
integration_require($fieldEditor, "1001 => \$text['lower']", 'Text lowercase display option is missing.', $failures);
integration_require($fieldEditor, "1004 => \$text['title_case']", 'Text title-case display option is missing.', $failures);
integration_require($publicDocument, 'DOCUMENTS_formatTextDisplay(', 'Text display formatting is not applied to rendered documents.', $failures);

integration_require(
    $documentController,
    "PLG_itemDisplay((string) \$documentSlug, 'documents')",
    'Public document pages do not expose the generic PLG_itemDisplay() provider hook.',
    $failures
);
integration_require(
    $documentController,
    '. $itemDisplayHtml',
    'Generic item-display fragments are not rendered on the full document page.',
    $failures
);
integration_require(
    $interop,
    "'type' => 'documents'",
    'Document Item Info identity no longer uses the canonical documents type.',
    $failures
);
integration_require(
    $interop,
    "'id' => (string) \$row['doc_url']",
    'Document Item Info identity no longer uses doc_url as the canonical ID.',
    $failures
);

if (!empty($failures)) {
    fwrite(STDERR, "Documents integration surface checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents integration surface checks: PASS\n";
