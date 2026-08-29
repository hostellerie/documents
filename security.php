<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | security.php                                                              |
// |                                                                           |
// | Server-side document input and ownership security helpers.                |
// +---------------------------------------------------------------------------+

if (strpos(strtolower(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''), 'security.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_plainTextInput($value)
{
    $value = str_replace("\0", '', (string) $value);
    if (function_exists('COM_getTextContent')) {
        return trim(COM_getTextContent($value));
    }

    return trim(html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8'));
}

/**
 * Normalize a scalar field value before the legacy save controller sees it.
 *
 * @param string $type Field type
 * @param mixed  $value Submitted value
 * @return mixed
 */
function DOCUMENTS_normalizeFieldInput($type, $value)
{
    if (is_array($value) || is_object($value) || is_resource($value)) {
        return '';
    }

    $value = str_replace("\0", '', (string) $value);
    $type = strtolower((string) $type);

    if ($type === 'checkbox') {
        return ((int) $value === 1) ? 1 : 0;
    }

    if ($type === 'decimal') {
        $value = trim($value);
        if (strpos($value, ',') !== false && strpos($value, '.') === false) {
            $value = str_replace(',', '.', $value);
        }
        return ($value !== '' && is_numeric($value)) ? $value : '';
    }

    if ($type === 'album' || $type === 'marker') {
        return ctype_digit(trim($value)) ? (string) (int) $value : '';
    }

    if ($type === 'date' || $type === 'text' || $type === 'textarea') {
        return DOCUMENTS_plainTextInput($value);
    }

    if ($type === 'select' || $type === 'radio') {
        return trim($value);
    }

    if ($type === 'image' || $type === 'file' || $type === 'category') {
        return basename(trim($value));
    }

    return DOCUMENTS_plainTextInput($value);
}

/**
 * Normalize dynamic document fields and reject forged select/radio options.
 *
 * @param array $request Request array, passed by reference
 * @param int   $categoryId Category id
 * @return void
 */
function DOCUMENTS_prepareDocumentFieldRequest(&$request, $categoryId)
{
    global $_TABLES;

    if (!is_array($request) || (int) $categoryId <= 0) {
        return;
    }

    $categoryId = (int) $categoryId;
    $result = DB_query(
        "SELECT fid, f_type, sel_id, var_name FROM {$_TABLES['documents_fields']} "
        . "WHERE cat_id={$categoryId} ORDER BY f_order ASC, fid ASC"
    );

    while ($field = DB_fetchArray($result)) {
        if (!is_array($field) || empty($field['var_name'])) {
            continue;
        }

        $name = (string) $field['var_name'];
        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
        $value = isset($request[$name]) ? $request[$name] : '';
        $value = DOCUMENTS_normalizeFieldInput($type, $value);

        if (($type === 'select' || $type === 'radio') && $value !== '') {
            $valueSql = DB_escapeString((string) $value);
            $selectGroup = isset($field['sel_id']) ? (int) $field['sel_id'] : 0;
            $valid = DB_getItem(
                $_TABLES['documents_selects'],
                'sid',
                "s_group={$selectGroup} AND s_name='{$valueSql}'"
            );
            if ($valid === '') {
                $value = '';
            }
        }

        $request[$name] = $value;
    }
}

/**
 * Prevent non-administrators from forging document ownership or permissions.
 * Dynamic field values are normalized in the same trusted server-side pass.
 *
 * @param array $request Request array, passed by reference
 * @param int   $ownerId Trusted owner id
 * @param int   $groupId Trusted group id
 * @param array $permissions Trusted permission values
 * @return void
 */
function DOCUMENTS_lockSecurityFields(&$request, $ownerId, $groupId, $permissions)
{
    if (!is_array($request) || SEC_hasRights('documents.admin')) {
        return;
    }

    $categoryId = isset($request['cid']) ? (int) $request['cid'] : 0;
    if ($categoryId > 0) {
        DOCUMENTS_prepareDocumentFieldRequest($request, $categoryId);
    }

    $request['owner_id'] = (int) $ownerId;
    $request['group_id'] = (int) $groupId;
    $request['perm_owner'] = isset($permissions['perm_owner'])
        ? (int) $permissions['perm_owner'] : 3;
    $request['perm_group'] = isset($permissions['perm_group'])
        ? (int) $permissions['perm_group'] : 3;
    $request['perm_members'] = isset($permissions['perm_members'])
        ? (int) $permissions['perm_members'] : 2;
    $request['perm_anon'] = isset($permissions['perm_anon'])
        ? (int) $permissions['perm_anon'] : 2;
}
