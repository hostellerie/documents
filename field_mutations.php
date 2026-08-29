<?php

/* Secure field mutations for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'field_mutations.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_fieldAllowedTypes()
{
    $types = array('text', 'textarea', 'decimal', 'date', 'image', 'checkbox', 'select', 'category');
    if (DOCUMENTS_hasMaps()) {
        $types[] = 'marker';
    }
    if (DOCUMENTS_hasMediaGallery()) {
        $types[] = 'album';
    }
    return $types;
}

function DOCUMENTS_fieldVariableName($value)
{
    $value = DOCUMENTS_adminPlainText($value, 18);
    if ($value === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,17}$/', $value)) {
        return '';
    }
    return $value;
}

function DOCUMENTS_adminReorderFields($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    if ($categoryId <= 0) {
        return;
    }

    $result = DB_query(
        "SELECT fid, f_order FROM {$_TABLES['documents_fields']} "
        . "WHERE cat_id={$categoryId} ORDER BY f_order ASC, fid ASC"
    );
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        if ((int) $row['f_order'] !== $order) {
            DB_query("UPDATE {$_TABLES['documents_fields']} SET f_order={$order} WHERE fid=" . (int) $row['fid']);
        }
        $order += 10;
    }
}

function DOCUMENTS_fieldExistingDocuments($fieldId)
{
    global $_TABLES;

    $fieldId = (int) $fieldId;
    if ($fieldId <= 0) {
        return 0;
    }

    return (int) DB_count($_TABLES['documents_values'], 'field_id', $fieldId);
}

function DOCUMENTS_fieldCategoryDocuments($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    if ($categoryId <= 0) {
        return array();
    }

    $sql = "SELECT DISTINCT v.doc_url FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.cat_id={$categoryId} AND v.doc_url<>''";
    $result = DB_query($sql);
    $ids = array();
    while ($row = DB_fetchArray($result)) {
        if (is_array($row) && !empty($row['doc_url'])) {
            $ids[] = (string) $row['doc_url'];
        }
    }
    return array_values(array_unique($ids));
}

function DOCUMENTS_fieldValidSelectGroup($groupId)
{
    global $_TABLES;

    $groupId = (int) $groupId;
    return $groupId > 0 && (int) DB_count($_TABLES['documents_selects_group'], 'gid', $groupId) > 0;
}

function DOCUMENTS_adminSaveField($request)
{
    global $_TABLES;

    $fid = isset($request['fid']) ? (int) $request['fid'] : 0;
    $operation = isset($request['op']) ? (string) $request['op'] : 'save';

    if ($operation === 'delete') {
        if ($fid <= 0) {
            return array(false, 'Invalid field.', 0);
        }

        $existing = DB_fetchArray(DB_query(
            "SELECT fid, cat_id, f_type FROM {$_TABLES['documents_fields']} WHERE fid={$fid} LIMIT 1"
        ));
        if (!is_array($existing) || empty($existing['fid'])) {
            return array(false, 'Unknown field.', 0);
        }

        $categoryId = (int) $existing['cat_id'];
        $imageReferences = array();
        if ($existing['f_type'] === 'image' && function_exists('DOCUMENTS_fieldImageReferences')) {
            $imageReferences = DOCUMENTS_fieldImageReferences($fid);
        }

        DB_query("DELETE FROM {$_TABLES['documents_values']} WHERE field_id={$fid}");
        DB_query("DELETE FROM {$_TABLES['documents_fields']} WHERE fid={$fid}");
        if (DB_error()) {
            return array(false, 'Unable to delete field.', $categoryId);
        }

        if (!empty($imageReferences) && function_exists('DOCUMENTS_cleanupDeletedFieldImages')) {
            DOCUMENTS_cleanupDeletedFieldImages($fid, $imageReferences);
        }
        DOCUMENTS_adminReorderFields($categoryId);
        return array(true, 'Field deleted.', $categoryId);
    }

    $categoryId = isset($request['cat_id']) ? (int) $request['cat_id'] : 0;
    $name = DOCUMENTS_adminPlainText(isset($request['f_name']) ? $request['f_name'] : '', 255);
    $variable = DOCUMENTS_fieldVariableName(isset($request['var_name']) ? $request['var_name'] : '');
    $help = DOCUMENTS_adminPlainText(isset($request['f_help']) ? $request['f_help'] : '', 255);
    $type = isset($request['f_type']) ? strtolower(trim((string) $request['f_type'])) : '';
    $order = isset($request['f_order']) ? max(0, (int) $request['f_order']) : 0;
    $required = !empty($request['f_required']) ? 1 : 0;
    $onList = !empty($request['f_on_list']) ? 1 : 0;
    $ownerId = isset($request['owner_id']) ? max(1, (int) $request['owner_id']) : 1;
    $groupId = isset($request['group_id']) ? max(1, (int) $request['group_id']) : 1;
    list($permOwner, $permGroup, $permMembers, $permAnon) = DOCUMENTS_adminPermissions($request);

    if ($categoryId <= 0 || (int) DB_count($_TABLES['documents_cat'], 'cid', $categoryId) <= 0) {
        return array(false, 'Unknown category.', $categoryId);
    }
    if ($name === '' || $variable === '') {
        return array(false, 'Field name and variable name are required.', $categoryId);
    }
    if (!in_array($type, DOCUMENTS_fieldAllowedTypes(), true)) {
        return array(false, 'Unsupported or unavailable field type.', $categoryId);
    }

    $safeVariable = DB_escapeString($variable);
    $duplicateSql = "SELECT fid FROM {$_TABLES['documents_fields']} "
        . "WHERE cat_id={$categoryId} AND var_name='{$safeVariable}'";
    if ($fid > 0) {
        $duplicateSql .= " AND fid<>{$fid}";
    }
    $duplicateSql .= ' LIMIT 1';
    if (DB_numRows(DB_query($duplicateSql)) > 0) {
        return array(false, 'This variable name is already used in the category.', $categoryId);
    }

    $selId = isset($request['sel_id']) ? (int) $request['sel_id'] : 0;
    if ($type === 'text') {
        if (!in_array($selId, array(0, 1001, 1002, 1003, 1004), true)) {
            $selId = 0;
        }
    } elseif ($type === 'select') {
        if (!DOCUMENTS_fieldValidSelectGroup($selId)) {
            return array(false, 'A valid selection group is required for select fields.', $categoryId);
        }
    } else {
        $selId = 0;
    }

    $previousCategoryId = $categoryId;
    if ($fid > 0) {
        $existing = DB_fetchArray(DB_query(
            "SELECT fid, cat_id, f_type FROM {$_TABLES['documents_fields']} WHERE fid={$fid} LIMIT 1"
        ));
        if (!is_array($existing) || empty($existing['fid'])) {
            return array(false, 'Unknown field.', $categoryId);
        }
        $previousCategoryId = (int) $existing['cat_id'];
        $valueCount = DOCUMENTS_fieldExistingDocuments($fid);
        if ($valueCount > 0 && $previousCategoryId !== $categoryId) {
            return array(false, 'A field already used by documents cannot be moved to another category.', $previousCategoryId);
        }
        if ($valueCount > 0 && (string) $existing['f_type'] !== $type) {
            return array(false, 'A field already used by documents cannot change type directly.', $previousCategoryId);
        }
    }

    $safeName = DB_escapeString($name);
    $safeHelp = DB_escapeString($help);
    $set = "f_name='{$safeName}', cat_id={$categoryId}, f_order={$order}, "
        . "f_type='" . DB_escapeString($type) . "', sel_id={$selId}, "
        . "var_name='{$safeVariable}', f_help='{$safeHelp}', f_required={$required}, "
        . "f_on_list={$onList}, owner_id={$ownerId}, group_id={$groupId}, "
        . "perm_owner={$permOwner}, perm_group={$permGroup}, perm_members={$permMembers}, perm_anon={$permAnon}";

    $newField = false;
    if ($fid > 0) {
        DB_query("UPDATE {$_TABLES['documents_fields']} SET {$set} WHERE fid={$fid}");
    } else {
        DB_query("INSERT INTO {$_TABLES['documents_fields']} SET {$set}");
        $fid = (int) DB_insertId();
        $newField = true;
    }

    if (DB_error() || $fid <= 0) {
        return array(false, 'Unable to save field.', $categoryId);
    }

    if ($newField) {
        $documents = DOCUMENTS_fieldCategoryDocuments($categoryId);
        foreach ($documents as $documentId) {
            $safeDocumentId = DB_escapeString($documentId);
            DB_query(
                "INSERT INTO {$_TABLES['documents_values']} SET field_id={$fid}, v_value='', "
                . "doc_url='{$safeDocumentId}', owner_id={$ownerId}, group_id={$groupId}, "
                . "perm_owner={$permOwner}, perm_group={$permGroup}, "
                . "perm_members={$permMembers}, perm_anon={$permAnon}"
            );
        }
    }

    DOCUMENTS_adminReorderFields($categoryId);
    if ($previousCategoryId !== $categoryId) {
        DOCUMENTS_adminReorderFields($previousCategoryId);
    }

    return array(true, 'Field saved.', $categoryId);
}
