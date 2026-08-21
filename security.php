<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | security.php                                                              |
// |                                                                           |
// | Server-side document input and ownership security helpers.                |
// +---------------------------------------------------------------------------+

if (strpos(strtolower(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''), 'security.php') !== false) {
    die('This file can not be used on its own.');
}

/**
 * Prevent non-administrators from forging document ownership or permissions.
 *
 * Existing rows keep their persisted security values. New rows are owned by
 * the current user and receive the plugin's configured default permissions.
 * Documents administrators keep the historical ability to edit these values.
 *
 * @param array      $request Request array, passed by reference
 * @param array|null $existing Existing document row for edits
 * @return void
 */
function DOCUMENTS_lockSecurityFields(&$request, $existing = null)
{
    global $_DOCUMENTS_CONF, $_GROUPS, $_USER;

    if (!is_array($request) || SEC_hasRights('documents.admin')) {
        return;
    }

    if (is_array($existing)
        && isset($existing['owner_id'], $existing['group_id'], $existing['perm_owner'],
            $existing['perm_group'], $existing['perm_members'], $existing['perm_anon'])) {
        $request['owner_id'] = (int) $existing['owner_id'];
        $request['group_id'] = (int) $existing['group_id'];
        $request['perm_owner'] = (int) $existing['perm_owner'];
        $request['perm_group'] = (int) $existing['perm_group'];
        $request['perm_members'] = (int) $existing['perm_members'];
        $request['perm_anon'] = (int) $existing['perm_anon'];
        return;
    }

    $defaults = array();
    $configured = isset($_DOCUMENTS_CONF['default_permissions'])
        ? $_DOCUMENTS_CONF['default_permissions']
        : array(3, 3, 2, 2);
    SEC_setDefaultPermissions($defaults, $configured);

    $request['owner_id'] = isset($_USER['uid']) ? (int) $_USER['uid'] : 1;
    $request['group_id'] = isset($_GROUPS['Documents Admin'])
        ? (int) $_GROUPS['Documents Admin']
        : 1;
    $request['perm_owner'] = isset($defaults['perm_owner']) ? (int) $defaults['perm_owner'] : 3;
    $request['perm_group'] = isset($defaults['perm_group']) ? (int) $defaults['perm_group'] : 3;
    $request['perm_members'] = isset($defaults['perm_members']) ? (int) $defaults['perm_members'] : 2;
    $request['perm_anon'] = isset($defaults['perm_anon']) ? (int) $defaults['perm_anon'] : 2;
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

    if ($type === 'album') {
        return ctype_digit(trim($value)) ? (string) (int) $value : '';
    }

    if ($type === 'date' || $type === 'text' || $type === 'select' || $type === 'radio') {
        return trim($value);
    }

    return $value;
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
        $type = isset($field['f_type']) ? (string) $field['f_type'] : '';
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
