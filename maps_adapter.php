<?php

/* Documents -> Maps interoperability adapter. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'maps_adapter.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_mapsCategoryHasMarker($categoryId)
{
    $fields = DOCUMENTS_documentMutationFields($categoryId);
    foreach ($fields as $field) {
        if (isset($field['f_type']) && strtolower((string) $field['f_type']) === 'marker') {
            return true;
        }
    }

    return false;
}

function DOCUMENTS_mapsCategorySupported($categoryId)
{
    $fields = DOCUMENTS_documentMutationFields($categoryId);
    $hasMarker = false;
    foreach ($fields as $field) {
        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
        if ($type === 'marker') {
            $hasMarker = true;
        }
    }

    /* Legacy Documents categories often mix marker fields with radio, album,
     * file or category fields. Those field types must not make the whole
     * category unsavable. Their values are handled/preserved below. */
    return $hasMarker && DOCUMENTS_hasMaps();
}

/**
 * Ask Maps to create or update a marker for a Documents item.
 * Documents never writes to Maps tables and never allocates marker IDs.
 */
function DOCUMENTS_mapsSaveMarker($documentId, $categorySlug, $field, $request, $ownerId, $groupId, $permissions, $active, $name)
{
    if (!DOCUMENTS_hasMaps() || !function_exists('PLG_invokeService')) {
        return array(false, '', 'Maps marker service is unavailable.');
    }

    $fieldId = isset($field['fid']) ? (int) $field['fid'] : 0;
    $mapId = isset($field['sel_id']) ? (int) $field['sel_id'] : 0;
    $varName = isset($field['var_name']) ? (string) $field['var_name'] : '';
    if ($fieldId <= 0 || $varName === '') {
        return array(false, '', 'Maps marker field is not configured correctly.');
    }

    $markerId = isset($request[$varName]) ? DOCUMENTS_normalizeFieldInput('marker', $request[$varName]) : '';
    if ($mapId <= 0 && $markerId === '') {
        return array(false, '', 'A Maps map must be configured before creating a new marker.');
    }

    $address = isset($request['address']) ? DOCUMENTS_plainTextInput($request['address']) : '';
    $lat = isset($request['lat']) ? trim((string) $request['lat']) : '';
    $lng = isset($request['lng']) ? trim((string) $request['lng']) : '';

    $args = array(
        'source' => 'documents',
        'source_id' => (string) $documentId,
        'source_url' => DOCUMENTS_interopCanonicalUrl($categorySlug, $documentId),
        'marker_id' => $markerId,
        'name' => DOCUMENTS_plainTextInput($name),
        'address' => $address,
        'lat' => $lat,
        'lng' => $lng,
        'active' => ((int) $active === DOCUMENTS_STATUS_ACTIVE) ? 1 : 0,
        'hidden' => 0,
        'owner_id' => (int) $ownerId,
        'group_id' => (int) $groupId,
        'perm_owner' => (int) $permissions[0],
        'perm_group' => (int) $permissions[1],
        'perm_members' => (int) $permissions[2],
        'perm_anon' => (int) $permissions[3]
    );
    if ($mapId > 0) {
        $args['map_id'] = $mapId;
    }

    $output = array();
    $svcMsg = array();
    $result = PLG_invokeService('maps', 'marker_save', $args, $output, $svcMsg);
    if ($result !== PLG_RET_OK) {
        return array(false, '', isset($svcMsg['error_desc']) ? (string) $svcMsg['error_desc'] : 'Maps marker save failed.');
    }

    $savedId = isset($output['id']) ? preg_replace('/[^0-9]/', '', (string) $output['id']) : '';
    if ($savedId === '') {
        return array(false, '', 'Maps did not return a marker id.');
    }

    return array(true, $savedId, '');
}

/**
 * Ask Maps to withdraw a marker when its source document disappears.
 * The marker is not deleted by Documents; Maps remains its sole owner.
 */
function DOCUMENTS_mapsDeactivateMarker($documentId, $markerId)
{
    if (!DOCUMENTS_hasMaps() || !function_exists('PLG_invokeService')) {
        return array(false, 'Maps marker service is unavailable.');
    }

    $markerId = DOCUMENTS_normalizeFieldInput('marker', $markerId);
    if ($markerId === '') {
        return array(true, '');
    }

    $args = array(
        'source' => 'documents',
        'source_id' => (string) $documentId,
        'marker_id' => $markerId,
        'active' => 0,
        'hidden' => 1
    );
    $output = array();
    $svcMsg = array();
    $result = PLG_invokeService('maps', 'marker_save', $args, $output, $svcMsg);
    if ($result !== PLG_RET_OK) {
        return array(false, isset($svcMsg['error_desc']) ? (string) $svcMsg['error_desc'] : 'Maps marker deactivation failed.');
    }

    return array(true, '');
}

/**
 * Save a Documents item whose marker fields are delegated to Maps.
 * Only Documents tables are mutated here; marker persistence belongs to Maps.
 */
function DOCUMENTS_saveMapsDocument($request)
{
    global $_TABLES;

    $categoryId = isset($request['cid']) ? (int) $request['cid'] : 0;
    $documentId = isset($request['doc_url']) ? trim((string) $request['doc_url']) : '';
    $category = DOCUMENTS_documentMutationCategory($categoryId);
    if (empty($category) || DOCUMENTS_documentMutationCategoryAccess($category) < 2) {
        return array(false, 'Category access denied.', '', '', array());
    }
    if (!DOCUMENTS_mapsCategorySupported($categoryId)) {
        return array(false, 'Maps category is not supported.', '', (string) $category['cat_url'], array());
    }

    $fields = DOCUMENTS_documentMutationFields($categoryId);
    $existing = array();
    if ($documentId !== '') {
        $existing = DOCUMENTS_documentMutationExisting($documentId);
        if (empty($existing)
            || DOCUMENTS_documentMutationDocumentCategoryId($documentId) !== $categoryId
            || !DOCUMENTS_canEditDocument($existing)) {
            return array(false, 'Document edit denied.', '', (string) $category['cat_url'], array());
        }
    }

    $values = array();
    $missing = array();
    $markerFields = array();
    foreach ($fields as $field) {
        $fieldId = (int) $field['fid'];
        $type = strtolower((string) $field['f_type']);
        $name = (string) $field['var_name'];

        if ($type === 'marker') {
            $markerFields[] = $field;
            $values[$fieldId] = isset($request[$name])
                ? DOCUMENTS_normalizeFieldInput('marker', $request[$name])
                : DOCUMENTS_documentMutationExistingFieldValue($documentId, $fieldId);
            continue;
        }
        if ($type === 'image') {
            $existingImage = DOCUMENTS_imageExistingValue($documentId, $fieldId);
            $hasUpload = DOCUMENTS_imageUploadRequestPresent($fieldId);
            if ((int) $field['f_required'] === 1 && $existingImage === '' && !$hasUpload) {
                $missing[] = stripslashes((string) $field['f_name']);
            }
            $values[$fieldId] = $existingImage;
            continue;
        }

        /* Historical file/category fields have no complete modern input control.
         * Album fields can also be absent when MediaGallery is unavailable.
         * Preserve the stored value whenever the form did not submit one. */
        if (in_array($type, array('file', 'category', 'album'), true)
            && !array_key_exists($name, $request)) {
            $values[$fieldId] = DOCUMENTS_documentMutationExistingFieldValue($documentId, $fieldId);
            continue;
        }

        $value = DOCUMENTS_normalizeFieldInput($type, isset($request[$name]) ? $request[$name] : '');
        if (in_array($type, array('select', 'radio'), true) && $value !== '') {
            $safeValue = DB_escapeString($value);
            $group = (int) $field['sel_id'];
            if (DB_getItem($_TABLES['documents_selects'], 'sid', "s_group={$group} AND s_name='{$safeValue}'") === '') {
                $value = '';
            }
        }
        if ((int) $field['f_required'] === 1 && $type !== 'checkbox' && trim((string) $value) === '') {
            $missing[] = stripslashes((string) $field['f_name']);
        }
        $values[$fieldId] = $value;
    }

    if (!empty($missing)) {
        return array(false, 'Missing required fields.', '', (string) $category['cat_url'], $missing);
    }

    $firstFieldId = !empty($fields) ? (int) $fields[0]['fid'] : 0;
    $title = ($firstFieldId > 0 && isset($values[$firstFieldId])) ? (string) $values[$firstFieldId] : '';
    if ($documentId === '') {
        $documentId = DOCUMENTS_documentMutationUniqueUrl($title);
    }

    $uploadedImages = array();
    list($uploadOk, $uploadedImages, $uploadError) = DOCUMENTS_uploadDocumentImages($documentId, $fields);
    if (!$uploadOk) {
        return array(false, $uploadError, '', (string) $category['cat_url'], array());
    }
    foreach ($uploadedImages as $fieldId => $filename) {
        $values[(int) $fieldId] = basename((string) $filename);
    }

    list($ownerId, $groupId, $permissions) = DOCUMENTS_documentMutationPermissions($request, $existing);
    $requestedStatus = isset($request['active']) ? (int) $request['active'] : DOCUMENTS_STATUS_SUBMISSION;
    $status = DOCUMENTS_normalizeDocumentStatus($requestedStatus, empty($existing) ? null : (int) $existing['active']);
    $safeDocument = DB_escapeString($documentId);

    if (empty($existing)) {
        DB_query("INSERT INTO {$_TABLES['documents_docs']} SET doc_url='{$safeDocument}',active={$status},created=NOW(),modified=NOW(),hits=0,owner_id={$ownerId},group_id={$groupId},perm_owner=" . (int)$permissions[0] . ",perm_group=" . (int)$permissions[1] . ",perm_members=" . (int)$permissions[2] . ",perm_anon=" . (int)$permissions[3]);
    } else {
        DB_query("UPDATE {$_TABLES['documents_docs']} SET active={$status},modified=NOW(),owner_id={$ownerId},group_id={$groupId},perm_owner=" . (int)$permissions[0] . ",perm_group=" . (int)$permissions[1] . ",perm_members=" . (int)$permissions[2] . ",perm_anon=" . (int)$permissions[3] . " WHERE doc_url='{$safeDocument}'");
    }
    if (DB_error()) {
        DOCUMENTS_imageDeleteFiles(array_values($uploadedImages));
        return array(false, 'Unable to save document.', '', (string) $category['cat_url'], array());
    }

    foreach ($markerFields as $field) {
        list($ok, $markerId, $error) = DOCUMENTS_mapsSaveMarker(
            $documentId,
            (string) $category['cat_url'],
            $field,
            $request,
            $ownerId,
            $groupId,
            $permissions,
            $status,
            $title
        );
        if (!$ok) {
            if (empty($existing)) {
                DB_query("DELETE FROM {$_TABLES['documents_values']} WHERE doc_url='{$safeDocument}'");
                DB_query("DELETE FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safeDocument}'");
            }
            DOCUMENTS_imageDeleteFiles(array_values($uploadedImages));
            return array(false, $error, '', (string) $category['cat_url'], array());
        }
        $values[(int) $field['fid']] = $markerId;
    }

    if (!DOCUMENTS_documentMutationUpsertValues($documentId, $values, $fields, $ownerId, $groupId, $permissions)) {
        return array(false, 'Unable to save document fields.', '', (string) $category['cat_url'], array());
    }

    return array(true, 'Document saved.', $documentId, (string) $category['cat_url'], array('status' => $status));
}
