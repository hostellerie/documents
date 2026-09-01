<?php

$root = dirname(__DIR__);
$failures = array();

$indexability = file_get_contents($root . '/indexability.php');
$runtime = file_get_contents($root . '/runtime.php');
$standardSave = file_get_contents($root . '/public_html/document-save.php');
$publicIndex = file_get_contents($root . '/public_html/index.php');

function documents_indexability_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_indexability_forbid($content, $needle, $message, &$failures)
{
    if ($content !== false && strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

documents_indexability_require($indexability, 'function DOCUMENTS_isPubliclyIndexable(', 'Public indexability helper is missing.', $failures);
documents_indexability_require($indexability, '$anonymousUid = 1;', 'Indexability must explicitly use Geeklog anonymous uid 1.', $failures);
documents_indexability_require($indexability, '(int) $row[\'active\'] !== DOCUMENTS_STATUS_ACTIVE', 'Inactive/draft/submission content is not excluded from public indexing.', $failures);
documents_indexability_require($indexability, '$documentAccess < 2', 'Anonymous document read permission is not enforced.', $failures);
documents_indexability_require($indexability, 'return $categoryAccess >= 2;', 'Anonymous category read permission is not enforced.', $failures);
documents_indexability_require($indexability, 'function DOCUMENTS_notifyPublicTransition(', 'Public lifecycle transition helper is missing.', $failures);
documents_indexability_require($indexability, 'PLG_itemSaved($documentId, \'documents\')', 'Public save event is missing.', $failures);
documents_indexability_require($indexability, 'PLG_itemDeleted($documentId, \'documents\')', 'Public removal event is missing.', $failures);

/* Runtime must not retain an independent legacy save lifecycle. Every public
 * save is routed through document-save.php, which owns the before/after
 * indexability snapshot. */
documents_indexability_forbid($runtime, 'DOCUMENTS_runtimeSaveCategoryMetaDescription', 'Obsolete category metadata shutdown handler remains.', $failures);
documents_indexability_require($publicIndex, "if (\$mode === 'save')", 'Public router does not identify document saves.', $failures);
documents_indexability_require($publicIndex, "require __DIR__ . '/document-save.php';", 'Public saves do not enter the secure lifecycle-aware dispatcher.', $failures);
documents_indexability_forbid($publicIndex, 'DOCUMENTS_LEGACY_SAVE_DISPATCH', 'Public router still exposes a legacy save lifecycle bypass.', $failures);

documents_indexability_require($standardSave, '$wasPublic = !$isCreation && DOCUMENTS_isPubliclyIndexable($documentId);', 'Standard saves do not snapshot anonymous visibility.', $failures);
documents_indexability_require($standardSave, '$isPublic = DOCUMENTS_isPubliclyIndexable($savedId);', 'Standard saves do not compute post-save public visibility.', $failures);
documents_indexability_require($standardSave, 'DOCUMENTS_notifyPublicTransition($savedId, $wasPublic, $isPublic);', 'Standard saves do not emit public-only lifecycle transitions.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents indexability checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public indexability checks: PASS\n";
