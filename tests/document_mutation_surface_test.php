<?php

$root = dirname(__DIR__);
$failures = array();

$mutationFile = $root . '/document_mutations.php';
$imageFile = $root . '/document_images.php';
$endpointFile = $root . '/public_html/document-save.php';
$rewriteFile = $root . '/rewrite.php';
$integrityFile = $root . '/integrity.php';

$content = is_file($mutationFile) ? file_get_contents($mutationFile) : false;
$images = is_file($imageFile) ? file_get_contents($imageFile) : false;
$endpoint = is_file($endpointFile) ? file_get_contents($endpointFile) : false;
$rewrite = is_file($rewriteFile) ? file_get_contents($rewriteFile) : false;
$integrity = is_file($integrityFile) ? file_get_contents($integrityFile) : false;

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
    documents_docmutation_require($content, 's_group={$selectGroupId} AND s_name=\'{$safeValue}\'', 'Select option validation is missing.', $failures);
    documents_docmutation_require($content, 'in_array($type, array(\'marker\', \'album\', \'file\', \'radio\'), true)', 'Specialized plugin field types are not excluded from the standard mutation path.', $failures);
    documents_docmutation_require($content, '$type === \'image\'', 'Image fields are not handled by the standard mutation path.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_uploadDocumentImages($documentId, $fields)', 'Image upload helper is not used before document persistence.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_cleanupReplacedImages($oldImages, $documentId)', 'Replaced document images are not cleaned after successful persistence.', $failures);
    documents_docmutation_require($content, '$available = 40 - strlen($prefix);', 'Document URL generation does not enforce the 40-character schema limit.', $failures);
    documents_docmutation_require($content, 'Missing required fields.', 'Required-field rejection is missing.', $failures);
    documents_docmutation_require($content, 'Document/category mismatch.', 'Document/category binding check is missing.', $failures);
    documents_docmutation_require($content, 'DOCUMENTS_normalizeDocumentStatus(', 'Workflow status normalization is missing.', $failures);
    documents_docmutation_require($content, 'DB_escapeString((string) $values[$fieldId])', 'Document values are not SQL escaped.', $failures);
    documents_docmutation_require($content, 'DELETE FROM {$_TABLES[\'documents_values\']}', 'New-document cleanup on partial failure is missing.', $failures);
    documents_docmutation_forbid($content, 'addslashes(', 'Standard document mutation layer must not use addslashes().', $failures);
}

documents_docmutation_require($images, 'function DOCUMENTS_uploadDocumentImages(', 'Secure document image upload helper is missing.', $failures);
documents_docmutation_require($images, 'DOCUMENTS_imageEnsureDirectory()', 'Image directory validation is missing.', $failures);
documents_docmutation_require($images, 'setAllowedMimeTypes', 'Image MIME allowlist is missing.', $failures);
documents_docmutation_require($images, "array('jpg', 'jpeg', 'png', 'gif', 'webp')", 'Image extension allowlist is missing.', $failures);
documents_docmutation_require($images, 'setMaxDimensions(', 'Image dimension limits are missing.', $failures);
documents_docmutation_require($images, 'setMaxFileSize(', 'Image size limit is missing.', $failures);
documents_docmutation_require($images, 'DOCUMENTS_imageDeleteFiles($filenames)', 'Failed uploads are not cleaned up.', $failures);
documents_docmutation_forbid($images, 'addslashes(', 'Secure image upload helper must not use addslashes().', $failures);

documents_docmutation_require($rewrite, 'mode=save', 'Document save requests are not routed through the secure dispatcher.', $failures);
documents_docmutation_require($rewrite, 'document-save.php', 'Secure document save rewrite target is missing.', $failures);
documents_docmutation_require($endpoint, "'document_images.php'", 'Secure document save dispatcher does not load image helpers.', $failures);
documents_docmutation_require($endpoint, 'SEC_checkToken()', 'Secure document save dispatcher does not validate CSRF.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_documentMutationIsStandardCategory', 'Dispatcher does not separate standard and specialized categories.', $failures);
documents_docmutation_require($endpoint, "require __DIR__ . '/index.php';", 'Specialized categories do not fall back to the legacy controller.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_lockSecurityFields(', 'New non-admin document ownership/permissions are not locked.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_isPubliclyIndexable($documentId)', 'Previous anonymous visibility is not captured before a standard save.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_notifyPublicTransition($savedId, $wasPublic, $isPublic)', 'Standard saves do not emit public-only lifecycle events.', $failures);
documents_docmutation_forbid($endpoint, 'runtime.php', 'Secure document dispatcher must not register duplicate runtime lifecycle hooks.', $failures);
documents_docmutation_require($integrity, '$available = 40 - strlen($prefix);', 'Historical unique-document URL helper still ignores the 40-character schema limit.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents standard mutation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents standard mutation checks: PASS\n";
