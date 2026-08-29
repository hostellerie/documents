<?php

$root = dirname(__DIR__);
$failures = array();
$file = $root . '/document_mutations.php';
$content = is_file($file) ? file_get_contents($file) : false;

function documents_docmutation_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_docmutation_forbid($content, $needle, $message, &$failures)
{
    if ($content !== false && strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

if ($content === false) {
    $failures[] = 'document_mutations.php is missing.';
} else {
    documents_docmutation_require($content, 'function DOCUMENTS_saveStandardDocument(', 'Standard document save entry point is missing.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_documentMutationCategoryAccess', 'Category permission enforcement is missing.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_canEditDocument($existing)', 'Existing document edit permission enforcement is missing.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_normalizeFieldInput', 'Dynamic field normalization is missing.', $failures);
    documents_docmutation_require($content, 's_group={$groupId} AND s_name=\'{$safeValue}\'', 'Select option validation is missing.', $failures);
    documents_docmutation_require($content, 'in_array($type, array(\'image\', \'marker\', \'album\', \'file\', \'radio\'), true)', 'Specialized field types are not excluded from the standard mutation path.', $failures);
    documents_docmutation_require($content, '$available = 40 - strlen($prefix);', 'Document URL generation does not enforce the 40-character schema limit.', $failures);
    documents_docmutation_require($content, 'Missing required fields.', 'Required-field rejection is missing.', $failures);
    documents_docmutation_require($content, 'Document/category mismatch.', 'Document/category binding check is missing.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_normalizeDocumentStatus(', 'Workflow status normalization is missing.', $failures);
    documents_docmutation_require($content, 'DB_escapeString((string) $values[$fieldId])', 'Document values are not SQL escaped.', $failures);
    documents_docmutation_require($content, 'DELETE FROM {$_TABLES[\'documents_values\']}', 'New-document cleanup on partial failure is missing.', $failures);
    documents_docmutation_forbid($content, 'addslashes(', 'Standard document mutation layer must not use addslashes().', $failures);
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents standard mutation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents standard mutation checks: PASS\n";
