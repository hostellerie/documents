<?php

/* Secure standard document mutations for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'document_mutations.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_documentMutationCategory($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    if ($categoryId <= 0) {
        return array();
    }

    $row = DB_fetchArray(DB_query(
        "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid={$categoryId} LIMIT 1"
    ));

    return is_array($row) ? $row : array();
}

function DOCUMENTS_documentMutationCategoryAccess($category)
{
    if (!is_array($category) || empty($category['cid'])) {
        return 0;
    }

    return SEC_hasAccess(
        (int) $category['owner_id'],
        (int) $category['group_id'],
        (int) $category['perm_owner'],
        (int) $category['perm_group'],
        (int) $category['perm_members'],
        (int) $category['perm_anon']
    );
}

function DOCUMENTS_documentMutationFields($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    $fields = array();
    if ($categoryId <= 0) {
        return $fields;
    }

    $result = DB_query(
        "SELECT fid, cat_id, f_name, f_order, f_type, sel_id, var_name, f_required "
        . "FROM {$_TABLES['documents_fields']} WHERE cat_id={$categoryId} "
        . "ORDER BY f_order ASC, fid ASC"
    );
    while ($row = DB_fetchArray($result)) {
        if (is_array($row)) {
            $fields[] = $row;
        }
    }

    return $fields;
}

function DOCUMENTS_documentMutationIsStandardCategory($categoryId)
{
    $fields = DOCUMENTS_documentMutationFields($categoryId);
    foreach ($fields as $field) {
        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
        if (in_array($type, array('image', 'marker', 'album', 'file', 'radio'), true)) {
            return false;
        }
    }

    return !empty($fields);
}

function DOCUMENTS_documentMutationExisting($documentId)
{
    global $_TABLES;

    $documentId = trim((string) $documentId);
    if ($documentId === '') {
        return array();
    }

    $safeId = DB_escapeString($documentId);
    $row = DB_fetchArray(DB_query(
        "SELECT * FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safeId}' LIMIT 1"
    ));

    return is_array($row) ? $row : array();
}

function DOCUMENTS_documentMutationDocumentCategoryId($documentId)
{
    global $_TABLES;

    $documentId = trim((string) $documentId);
    if ($documentId === '') {
        return 0;
    }

    $safeId = DB_escapeString($documentId);
    $row = DB_fetchArray(DB_query(
        "SELECT f.cat_id FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE v.doc_url='{$safeId}' ORDER BY f.f_order ASC, f.fid ASC LIMIT 1"
    ));

    return is_array($row) && isset($row['cat_id']) ? (int) $row['cat_id'] : 0;
}

function DOCUMENTS_documentMutationUniqueUrl($title)
{
    global $_TABLES;

    $slug = DOCUMENTS_normalizeRouteSlug($title);
    if ($slug === '') {
        $slug = 'document';
    }

    $next = (int) DB_getItem($_TABLES['documents_docs'], 'MAX(did)', '1=1') + 1;
    if ($next < 1) {
        $next = 1;
    }

    do {
        $prefix = (string) $next . '-';
        $available = 40 - strlen($prefix);
        if ($available < 1) {
            $available = 1;
        }
        $shortSlug = substr($slug, 0, $available);
        $shortSlug = rtrim($shortSlug, '-');
        if ($shortSlug === '') {
            $shortSlug = 'd';
        }
        $candidate = $prefix . $shortSlug;
        $safeCandidate = DB_escapeString($candidate);
        $exists = DB_getItem($_TABLES['documents_docs'], 'did', "doc_url='{$safeCandidate}'") !== '';
        $next++;
    } while ($exists);

    return $candidate;
}

function DOCUMENTS_documentMutationNormalizeValues($request, $fields)
{
    global $_TABLES;

    $values = array();
    $errors = array();

    foreach ($fields as $field) {
        if (!is_array($field) || empty($field['fid']) || empty($field['var_name'])) {
            continue;
        }

        $type = strtolower((string) $field['f_type']);
        $name = (string) $field['var_name'];
        $submitted = isset($request[$name]) ? $request[$name] : '';
        $value = DOCUMENTS_normalizeFieldInput($type, $submitted);

        if ($type === 'select' && $value !== '') {
            $safeValue = DB_escapeString((string) $value);
            $groupId = isset($field['sel_id']) ? (int) $field['sel_id'] : 0;
            $valid = DB_getItem(
                $_TABLES['documents_selects'],
                'sid',
                "s_group={$groupId} AND s_name='{$safeValue}'"
            );
            if ($valid === '') {
                $value = '';
            }
        }

        if ((int) $field['f_required'] === 1) {
            $empty = ($type === 'checkbox') ? false : trim((string) $value) === '';
            if ($empty) {
                $errors[] = stripslashes((string) $field['f_name']);
            }
        }

        $values[(int) $field['fid']] = $value;
    }

    return array($values, $errors);
}

function DOCUMENTS_documentMutationPermissions($request, $existing)
{
    global $_DOCUMENTS_CONF, $_USER;

    if (!empty($existing)) {
        $ownerId = (int) $existing['owner_id'];
        $groupId = (int) $existing['group_id'];
        $permissions = array(
            (int) $existing['perm_owner'],
            (int) $existing['perm_group'],
            (int) $existing['perm_members'],
            (int) $existing['perm_anon']
        );

        if (SEC_hasRights('documents.admin')) {
            $ownerId = isset($request['owner_id']) ? max(1, (int) $request['owner_id']) : $ownerId;
            $groupId = isset($request['group_id']) ? max(1, (int) $request['group_id']) : $groupId;
            $permissions = DOCUMENTS_requestPermissions($request, array(
                'perm_owner' => $permissions[0],
                'perm_group' => $permissions[1],
                'perm_members' => $permissions[2],
                'perm_anon' => $permissions[3]
            ));
        }

        return array($ownerId, $groupId, $permissions);
    }

    $defaults = array();
    SEC_setDefaultPermissions($defaults, $_DOCUMENTS_CONF['default_permissions']);
    $ownerId = isset($request['owner_id']) ? max(1, (int) $request['owner_id']) : (isset($_USER['uid']) ? (int) $_USER['uid'] : 1);
    $groupId = isset($request['group_id']) ? max(1, (int) $request['group_id']) : 1;
    $permissions = DOCUMENTS_requestPermissions($request, $defaults);

    return array($ownerId, $groupId, $permissions);
}

function DOCUMENTS_documentMutationUpsertValues($documentId, $values, $fields, $ownerId, $groupId, $permissions)
{
    global $_TABLES;

    $safeDocument = DB_escapeString($documentId);
    foreach ($fields as $field) {
        $fieldId = isset($field['fid']) ? (int) $field['fid'] : 0;
        if ($fieldId <= 0 || !array_key_exists($fieldId, $values)) {
            continue;
        }

        $safeValue = DB_escapeString((string) $values[$fieldId]);
        $existingValue = DB_getItem(
            $_TABLES['documents_values'],
            'vid',
            "doc_url='{$safeDocument}' AND field_id={$fieldId}"
        );

        if ($existingValue !== '') {
            DB_query(
                "UPDATE {$_TABLES['documents_values']} SET v_value='{$safeValue}', "
                . "owner_id={$ownerId}, group_id={$groupId}, "
                . "perm_owner=" . (int) $permissions[0] . ", perm_group=" . (int) $permissions[1] . ", "
                . "perm_members=" . (int) $permissions[2] . ", perm_anon=" . (int) $permissions[3] . " "
                . "WHERE vid=" . (int) $existingValue
            );
        } else {
            DB_query(
                "INSERT INTO {$_TABLES['documents_values']} SET field_id={$fieldId}, "
                . "v_value='{$safeValue}', doc_url='{$safeDocument}', owner_id={$ownerId}, group_id={$groupId}, "
                . "perm_owner=" . (int) $permissions[0] . ", perm_group=" . (int) $permissions[1] . ", "
                . "perm_members=" . (int) $permissions[2] . ", perm_anon=" . (int) $permissions[3]
            );
        }

        if (DB_error()) {
            return false;
        }
    }

    return true;
}

function DOCUMENTS_saveStandardDocument($request)
{
    global $_TABLES;

    if (!is_array($request)) {
        return array(false, 'Invalid request.', '', '', array());
    }

    $categoryId = isset($request['cid']) ? (int) $request['cid'] : 0;
    $documentId = isset($request['doc_url']) ? trim((string) $request['doc_url']) : '';
    $category = DOCUMENTS_documentMutationCategory($categoryId);
    if (empty($category)) {
        return array(false, 'Unknown category.', '', '', array());
    }
    if (DOCUMENTS_documentMutationCategoryAccess($category) < 2) {
        return array(false, 'Category access denied.', '', '', array());
    }
    if ((int) $category['submitable'] !== 1 && !SEC_hasRights('documents.admin')) {
        return array(false, 'This category does not accept submissions.', '', '', array());
    }
    if (!DOCUMENTS_documentMutationIsStandardCategory($categoryId)) {
        return array(false, 'legacy-required', '', '', array());
    }

    $fields = DOCUMENTS_documentMutationFields($categoryId);
    list($values, $missing) = DOCUMENTS_documentMutationNormalizeValues($request, $fields);
    if (!empty($missing)) {
        return array(false, 'Missing required fields.', '', (string) $category['cat_url'], $missing);
    }

    $existing = array();
    if ($documentId !== '') {
        $existing = DOCUMENTS_documentMutationExisting($documentId);
        if (empty($existing)) {
            return array(false, 'Unknown document.', '', (string) $category['cat_url'], array());
        }
        if (DOCUMENTS_documentMutationDocumentCategoryId($documentId) !== $categoryId) {
            return array(false, 'Document/category mismatch.', '', (string) $category['cat_url'], array());
        }
        if (!DOCUMENTS_canEditDocument($existing)) {
            return array(false, 'Document edit denied.', '', (string) $category['cat_url'], array());
        }
    } else {
        $firstFieldId = !empty($fields) ? (int) $fields[0]['fid'] : 0;
        $title = ($firstFieldId > 0 && isset($values[$firstFieldId])) ? (string) $values[$firstFieldId] : '';
        $documentId = DOCUMENTS_documentMutationUniqueUrl($title);
    }

    list($ownerId, $groupId, $permissions) = DOCUMENTS_documentMutationPermissions($request, $existing);
    $requestedStatus = isset($request['active']) ? (int) $request['active'] : DOCUMENTS_STATUS_SUBMISSION;
    $status = DOCUMENTS_normalizeDocumentStatus(
        $requestedStatus,
        empty($existing) ? null : (int) $existing['active']
    );
    $safeDocument = DB_escapeString($documentId);

    if (empty($existing)) {
        DB_query(
            "INSERT INTO {$_TABLES['documents_docs']} SET doc_url='{$safeDocument}', active={$status}, "
            . "created=NOW(), modified=NOW(), hits=0, owner_id={$ownerId}, group_id={$groupId}, "
            . "perm_owner=" . (int) $permissions[0] . ", perm_group=" . (int) $permissions[1] . ", "
            . "perm_members=" . (int) $permissions[2] . ", perm_anon=" . (int) $permissions[3]
        );
        if (DB_error()) {
            return array(false, 'Unable to create document.', '', (string) $category['cat_url'], array());
        }
    } else {
        DB_query(
            "UPDATE {$_TABLES['documents_docs']} SET active={$status}, modified=NOW(), "
            . "owner_id={$ownerId}, group_id={$groupId}, "
            . "perm_owner=" . (int) $permissions[0] . ", perm_group=" . (int) $permissions[1] . ", "
            . "perm_members=" . (int) $permissions[2] . ", perm_anon=" . (int) $permissions[3] . " "
            . "WHERE doc_url='{$safeDocument}'"
        );
        if (DB_error()) {
            return array(false, 'Unable to update document.', '', (string) $category['cat_url'], array());
        }
    }

    if (!DOCUMENTS_documentMutationUpsertValues(
        $documentId,
        $values,
        $fields,
        $ownerId,
        $groupId,
        $permissions
    )) {
        if (empty($existing)) {
            DB_query("DELETE FROM {$_TABLES['documents_values']} WHERE doc_url='{$safeDocument}'");
            DB_query("DELETE FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safeDocument}'");
        }
        return array(false, 'Unable to save document fields.', '', (string) $category['cat_url'], array());
    }

    return array(true, 'Document saved.', $documentId, (string) $category['cat_url'], array('status' => $status));
}
