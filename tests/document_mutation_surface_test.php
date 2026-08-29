<?php

$root = dirname(__DIR__);
$failures = array();

$mutationFile = $root . '/document_mutations.php';
$imageFile = $root . '/document_images.php';
$mapsFile = $root . '/maps_adapter.php';
$deleteFile = $root . '/document_delete.php';
$endpointFile = $root . '/public_html/document-save.php';
$indexFile = $root . '/public_html/index.php';
$rewriteFile = $root . '/rewrite.php';
$integrityFile = $root . '/integrity.php';

$content = is_file($mutationFile) ? file_get_contents($mutationFile) : false;
$images = is_file($imageFile) ? file_get_contents($imageFile) : false;
$maps = is_file($mapsFile) ? file_get_contents($mapsFile) : false;
$deletion = is_file($deleteFile) ? file_get_contents($deleteFile) : false;
$endpoint = is_file($endpointFile) ? file_get_contents($endpointFile) : false;
$index = is_file($indexFile) ? file_get_contents($indexFile) : false;
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
    documents_docmutation_require($content, 'in_array($type, array(\'marker\', \'album\', \'file\', \'radio\'), true)', 'Standard mutation path no longer isolates delegated field types.', $failures);
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

/* Maps is the sole owner of marker creation, editing and withdrawal. */
documents_docmutation_require($maps, 'function DOCUMENTS_mapsSaveMarker(', 'Documents Maps service adapter is missing.', $failures);
documents_docmutation_require($maps, "PLG_invokeService('maps', 'marker_save'", 'Marker mutations are not delegated to Maps marker_save.', $failures);
documents_docmutation_require($maps, 'function DOCUMENTS_mapsDeactivateMarker(', 'Marker withdrawal is not delegated to Maps.', $failures);
documents_docmutation_require($maps, 'function DOCUMENTS_saveMapsDocument(', 'Delegated marker document save path is missing.', $failures);
documents_docmutation_require($maps, 'DOCUMENTS_documentMutationUpsertValues(', 'Documents does not retain the Maps-returned marker id in its own value layer.', $failures);
documents_docmutation_forbid($maps, 'maps_markers', 'Documents adapter must never access Maps marker storage.', $failures);
documents_docmutation_forbid($maps, 'maps_maps', 'Documents adapter must never access Maps map storage.', $failures);
documents_docmutation_forbid($maps, 'COM_makeSid(', 'Documents must never allocate a Maps marker id.', $failures);
documents_docmutation_forbid($maps, 'updateMap(', 'Documents must never rebuild a Maps map.', $failures);

documents_docmutation_require($deletion, 'function DOCUMENTS_deleteDocumentSecure(', 'Secure document deletion is missing.', $failures);
documents_docmutation_require($deletion, 'DOCUMENTS_mapsDeactivateMarker($documentId, $markerId)', 'Document deletion does not ask Maps to withdraw linked markers.', $failures);
documents_docmutation_forbid($deletion, 'maps_markers', 'Document deletion must never access Maps marker storage.', $failures);
documents_docmutation_forbid($deletion, 'maps_maps', 'Document deletion must never access Maps map storage.', $failures);

documents_docmutation_require($rewrite, 'mode=save', 'Document save requests are not routed through the secure dispatcher.', $failures);
documents_docmutation_require($rewrite, 'document-save.php', 'Secure document save rewrite target is missing.', $failures);
documents_docmutation_require($endpoint, "'document_images.php'", 'Secure document save dispatcher does not load image helpers.', $failures);
documents_docmutation_require($endpoint, "'maps_adapter.php'", 'Secure document save dispatcher does not load the Maps service adapter.', $failures);
documents_docmutation_require($endpoint, "'document_delete.php'", 'Secure document save dispatcher does not load secure deletion.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_mapsCategorySupported($categoryId)', 'Dispatcher does not identify Maps-delegated categories.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_saveMapsDocument($_REQUEST)', 'Marker categories are not routed through the Maps-owned save path.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_deleteDocumentSecure($documentId)', 'Document deletion is not routed through the secure ownership boundary.', $failures);
documents_docmutation_require($endpoint, '$GLOBALS[\'DOCUMENTS_LEGACY_SAVE_DISPATCH\'] = true', 'Intentional legacy fallback is not explicitly marked.', $failures);
documents_docmutation_require($endpoint, 'SEC_checkToken()', 'Secure document save dispatcher does not validate CSRF.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_lockSecurityFields(', 'New non-admin document ownership/permissions are not locked.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_isPubliclyIndexable($documentId)', 'Previous anonymous visibility is not captured before a standard save.', $failures);
documents_docmutation_require($endpoint, 'DOCUMENTS_notifyPublicTransition($savedId, $wasPublic, $isPublic)', 'Standard saves do not emit public-only lifecycle events.', $failures);
documents_docmutation_forbid($endpoint, 'runtime.php', 'Secure document dispatcher must not register duplicate runtime lifecycle hooks.', $failures);

documents_docmutation_require($index, '$documentsMode === \'save\' && empty($GLOBALS[\'DOCUMENTS_LEGACY_SAVE_DISPATCH\'])', 'Direct index.php document saves can still reach the historical controller.', $failures);
documents_docmutation_require($index, "require __DIR__ . '/document-save.php';", 'Direct index.php saves are not delegated to the secure dispatcher.', $failures);

documents_docmutation_require($integrity, '$available = 40 - strlen($prefix);', 'Historical unique-document URL helper still ignores the 40-character schema limit.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents standard mutation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents standard mutation checks: PASS\n";
