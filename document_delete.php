<?php

/* Secure document deletion for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'document_delete.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_documentMarkerReferences($documentId)
{
    global $_TABLES;

    $documentId = trim((string) $documentId);
    if ($documentId === '') {
        return array();
    }

    $safeId = DB_escapeString($documentId);
    $result = DB_query(
        "SELECT v.v_value FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE v.doc_url='{$safeId}' AND f.f_type='marker'"
    );

    $markers = array();
    while ($row = DB_fetchArray($result)) {
        $markerId = isset($row['v_value']) ? DOCUMENTS_normalizeFieldInput('marker', $row['v_value']) : '';
        if ($markerId !== '') {
            $markers[$markerId] = $markerId;
        }
    }

    return array_values($markers);
}

function DOCUMENTS_deleteDocumentSecure($documentId)
{
    global $_TABLES;

    $documentId = trim((string) $documentId);
    if ($documentId === '') {
        return array(false, 'Unknown document.');
    }
    if (!SEC_hasRights('documents.admin')) {
        return array(false, 'Document deletion requires Documents administration rights.');
    }

    $existing = DOCUMENTS_documentMutationExisting($documentId);
    if (empty($existing)) {
        return array(false, 'Unknown document.');
    }

    $wasPublic = DOCUMENTS_isPubliclyIndexable($documentId);
    $oldUrl = DOCUMENTS_interopResolveStoredUrl($documentId);
    if ($oldUrl !== '') {
        DOCUMENTS_interopRememberUrl($documentId, $oldUrl);
    }

    $markerIds = DOCUMENTS_documentMarkerReferences($documentId);
    foreach ($markerIds as $markerId) {
        list($mapsOk, $mapsError) = DOCUMENTS_mapsDeactivateMarker($documentId, $markerId);
        if (!$mapsOk) {
            return array(false, $mapsError !== '' ? $mapsError : 'Unable to withdraw linked Maps marker.');
        }
    }

    $images = function_exists('DOCUMENTS_documentImageReferences')
        ? DOCUMENTS_documentImageReferences($documentId) : array();
    $safeId = DB_escapeString($documentId);

    DB_query("DELETE FROM {$_TABLES['documents_values']} WHERE doc_url='{$safeId}'");
    if (DB_error()) {
        return array(false, 'Unable to delete document values.');
    }

    DB_query("DELETE FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safeId}'");
    if (DB_error()) {
        return array(false, 'Unable to delete document.');
    }

    if (!empty($images) && function_exists('DOCUMENTS_imageDeleteFiles')) {
        DOCUMENTS_imageDeleteFiles(array_values($images));
    }

    DOCUMENTS_notifyPublicTransition($documentId, $wasPublic, false);
    return array(true, 'Document deleted.');
}
