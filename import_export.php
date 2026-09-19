<?php

/* Portable CSV import/export helpers for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'import_export.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_csvText($value, $maxLength)
{
    if (is_array($value) || is_object($value) || is_resource($value)) {
        return '';
    }
    $value = str_replace("\0", '', (string) $value);
    $maxLength = max(1, (int) $maxLength);
    if (function_exists('MBYTE_strlen') && function_exists('MBYTE_substr')) {
        return MBYTE_strlen($value) > $maxLength ? MBYTE_substr($value, 0, $maxLength) : $value;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $maxLength ? mb_substr($value, 0, $maxLength, 'UTF-8') : $value;
    }
    return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
}

function DOCUMENTS_csvUserName($uid)
{
    global $_TABLES;
    $uid = (int) $uid;
    return $uid > 0 ? (string) DB_getItem($_TABLES['users'], 'username', 'uid=' . $uid) : '';
}

function DOCUMENTS_csvGroupName($gid)
{
    global $_TABLES;
    $gid = (int) $gid;
    return $gid > 0 ? (string) DB_getItem($_TABLES['groups'], 'grp_name', 'grp_id=' . $gid) : '';
}

function DOCUMENTS_csvDefaultGroupId()
{
    global $_TABLES;
    $gid = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='Documents Admin'");
    return $gid > 0 ? $gid : 1;
}

function DOCUMENTS_csvUserId($username, $fallback)
{
    global $_TABLES;
    $username = trim((string) $username);
    if ($username !== '') {
        $safe = DB_escapeString($username);
        $uid = (int) DB_getItem($_TABLES['users'], 'uid', "username='{$safe}'");
        if ($uid > 0) {
            return $uid;
        }
    }
    return max(1, (int) $fallback);
}

function DOCUMENTS_csvGroupId($name, $fallback)
{
    global $_TABLES;
    $name = trim((string) $name);
    if ($name !== '') {
        $safe = DB_escapeString($name);
        $gid = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='{$safe}'");
        if ($gid > 0) {
            return $gid;
        }
    }
    return max(1, (int) $fallback);
}

function DOCUMENTS_csvCategory($categoryId)
{
    global $_TABLES;
    $categoryId = (int) $categoryId;
    if ($categoryId <= 0) {
        return array();
    }
    $row = DB_fetchArray(DB_query("SELECT * FROM {$_TABLES['documents_cat']} WHERE cid={$categoryId} LIMIT 1"));
    return is_array($row) ? $row : array();
}

function DOCUMENTS_csvFields($categoryId)
{
    global $_TABLES;
    $fields = array();
    $result = DB_query(
        "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id=" . (int) $categoryId
        . " ORDER BY f_order ASC, fid ASC"
    );
    while ($row = DB_fetchArray($result)) {
        if (is_array($row)) {
            $fields[] = $row;
        }
    }
    return $fields;
}

function DOCUMENTS_csvSelectGroup($groupId)
{
    global $_TABLES;
    $groupId = (int) $groupId;
    if ($groupId <= 0) {
        return array();
    }
    $row = DB_fetchArray(DB_query(
        "SELECT * FROM {$_TABLES['documents_selects_group']} WHERE gid={$groupId} LIMIT 1"
    ));
    return is_array($row) ? $row : array();
}

function DOCUMENTS_csvWriteExport($categoryId, $templateOnly)
{
    global $_TABLES;

    $category = DOCUMENTS_csvCategory($categoryId);
    $fields = DOCUMENTS_csvFields($categoryId);
    if (empty($category) || empty($fields)) {
        return false;
    }

    $out = fopen('php://output', 'w');
    if (!$out) {
        return false;
    }

    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('#documents-format', '1'), ';');
    fputcsv($out, array(
        '#category', $category['cat_name'], $category['cat_url'], $category['cat_order'],
        $category['css'], $category['map'], $category['template'], $category['list_index'],
        $category['submitable'], $category['cat_help'], $category['metadescription'],
        $category['custom_header'], $category['custom_footer'], DOCUMENTS_csvUserName($category['owner_id']),
        DOCUMENTS_csvGroupName($category['group_id']), $category['perm_owner'], $category['perm_group'],
        $category['perm_members'], $category['perm_anon']
    ), ';');

    $exportedGroups = array();
    foreach ($fields as $field) {
        $selectionName = '';
        $type = strtolower((string) $field['f_type']);
        if (in_array($type, array('select', 'radio'), true) && (int) $field['sel_id'] > 0) {
            $group = DOCUMENTS_csvSelectGroup($field['sel_id']);
            if (!empty($group)) {
                $selectionName = (string) $group['g_name'];
                $gid = (int) $group['gid'];
                if (empty($exportedGroups[$gid])) {
                    $exportedGroups[$gid] = true;
                    $options = DB_query(
                        "SELECT * FROM {$_TABLES['documents_selects']} WHERE s_group={$gid} "
                        . "ORDER BY s_order ASC, sid ASC"
                    );
                    while ($option = DB_fetchArray($options)) {
                        fputcsv($out, array(
                            '#select', $group['g_name'], $group['g_help'], $option['s_name'],
                            $option['s_value'], $option['s_order']
                        ), ';');
                    }
                }
            }
        } elseif ($type === 'text' && in_array((int) $field['sel_id'], array(1001, 1002, 1003, 1004), true)) {
            $selectionName = (string) (int) $field['sel_id'];
        }
        fputcsv($out, array(
            '#field', $field['var_name'], $field['f_name'], $field['f_order'], $field['f_type'],
            $selectionName, $field['f_help'], $field['f_required'], $field['f_on_list'], $field['display_empty']
        ), ';');
    }

    $header = array(
        'category_slug', 'doc_url', 'status', 'created', 'modified', 'owner_username', 'group_name',
        'perm_owner', 'perm_group', 'perm_members', 'perm_anon', 'hits'
    );
    foreach ($fields as $field) {
        $header[] = (string) $field['var_name'];
    }
    fputcsv($out, $header, ';');

    if ($templateOnly) {
        $row = array($category['cat_url'], '', '1', '', '', '', '', '3', '2', '2', '2', '0');
        foreach ($fields as $unused) {
            $row[] = '';
        }
        fputcsv($out, $row, ';');
        fclose($out);
        return true;
    }

    $documents = DB_query(
        "SELECT DISTINCT d.* FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.cat_id=" . (int) $categoryId . " ORDER BY d.did ASC"
    );
    while ($document = DB_fetchArray($documents)) {
        if (!is_array($document) || empty($document['doc_url'])) {
            continue;
        }
        $row = array(
            $category['cat_url'], $document['doc_url'], $document['active'], $document['created'],
            $document['modified'], DOCUMENTS_csvUserName($document['owner_id']),
            DOCUMENTS_csvGroupName($document['group_id']), $document['perm_owner'], $document['perm_group'],
            $document['perm_members'], $document['perm_anon'], $document['hits']
        );
        $safeDocument = DB_escapeString($document['doc_url']);
        foreach ($fields as $field) {
            $value = DB_getItem(
                $_TABLES['documents_values'], 'v_value',
                "doc_url='{$safeDocument}' AND field_id=" . (int) $field['fid']
            );
            $row[] = $value === false ? '' : (string) $value;
        }
        fputcsv($out, $row, ';');
    }

    fclose($out);
    return true;
}

function DOCUMENTS_csvParse($path)
{
    $parsed = array(
        'format' => '', 'category' => array(), 'fields' => array(), 'selects' => array(),
        'header' => array(), 'rows' => array(), 'errors' => array()
    );
    if (!is_file($path) || !is_readable($path)) {
        $parsed['errors'][] = 'CSV file is not readable.';
        return $parsed;
    }
    $handle = fopen($path, 'r');
    if (!$handle) {
        $parsed['errors'][] = 'Unable to open CSV file.';
        return $parsed;
    }

    $line = 0;
    while (($cells = fgetcsv($handle, 0, ';')) !== false) {
        $line++;
        if (!is_array($cells) || !$cells) {
            continue;
        }
        if ($line === 1 && isset($cells[0])) {
            $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);
        }
        $first = isset($cells[0]) ? trim((string) $cells[0]) : '';
        if ($first === '') {
            continue;
        }
        if ($first === '#documents-format') {
            $parsed['format'] = isset($cells[1]) ? trim((string) $cells[1]) : '';
            continue;
        }
        if ($first === '#category') {
            $keys = array(
                'cat_name', 'cat_url', 'cat_order', 'css', 'map', 'template', 'list_index', 'submitable',
                'cat_help', 'metadescription', 'custom_header', 'custom_footer', 'owner_username', 'group_name',
                'perm_owner', 'perm_group', 'perm_members', 'perm_anon'
            );
            foreach ($keys as $i => $key) {
                $parsed['category'][$key] = isset($cells[$i + 1]) ? (string) $cells[$i + 1] : '';
            }
            continue;
        }
        if ($first === '#field') {
            $keys = array(
                'var_name', 'f_name', 'f_order', 'f_type', 'select_group',
                'f_help', 'f_required', 'f_on_list', 'display_empty'
            );
            $field = array();
            foreach ($keys as $i => $key) {
                $field[$key] = isset($cells[$i + 1]) ? (string) $cells[$i + 1] : '';
            }
            if ($field['var_name'] !== '') {
                $parsed['fields'][$field['var_name']] = $field;
            }
            continue;
        }
        if ($first === '#select') {
            $parsed['selects'][] = array(
                'group_name' => isset($cells[1]) ? (string) $cells[1] : '',
                'group_help' => isset($cells[2]) ? (string) $cells[2] : '',
                's_name' => isset($cells[3]) ? (string) $cells[3] : '',
                's_value' => isset($cells[4]) ? (string) $cells[4] : '',
                's_order' => isset($cells[5]) ? (int) $cells[5] : 0
            );
            continue;
        }
        if (empty($parsed['header'])) {
            $parsed['header'] = array_map('trim', $cells);
            continue;
        }
        $row = array();
        foreach ($parsed['header'] as $i => $key) {
            $row[$key] = isset($cells[$i]) ? (string) $cells[$i] : '';
        }
        $row['_line'] = $line;
        $parsed['rows'][] = $row;
    }
    fclose($handle);

    if ($parsed['format'] !== '1') {
        $parsed['errors'][] = 'Unsupported or missing Documents CSV format version.';
    }
    if (empty($parsed['category']['cat_url'])) {
        $parsed['errors'][] = 'Category metadata is missing.';
    }
    if (empty($parsed['fields'])) {
        $parsed['errors'][] = 'Field metadata is missing.';
    }
    if (empty($parsed['header'])) {
        $parsed['errors'][] = 'CSV header is missing.';
    }
    return $parsed;
}

function DOCUMENTS_csvEnsureSelectGroups($parsed, &$messages, &$errors)
{
    global $_TABLES;

    $map = array();
    foreach ($parsed['selects'] as $select) {
        $name = DOCUMENTS_csvText($select['group_name'], 255);
        if ($name === '') {
            continue;
        }
        if (!isset($map[$name])) {
            $safeName = DB_escapeString($name);
            $gid = (int) DB_getItem($_TABLES['documents_selects_group'], 'gid', "g_name='{$safeName}'");
            if ($gid <= 0) {
                $safeHelp = DB_escapeString(DOCUMENTS_csvText($select['group_help'], 255));
                DB_query("INSERT INTO {$_TABLES['documents_selects_group']} SET g_name='{$safeName}', g_help='{$safeHelp}'");
                $gid = (int) DB_insertId();
                if ($gid <= 0 || DB_error()) {
                    $errors[] = 'Unable to create selection group: ' . $name;
                    continue;
                }
                $messages[] = 'Selection group created: ' . $name;
            }
            $map[$name] = $gid;
        }
        $gid = (int) $map[$name];
        $optionName = DOCUMENTS_csvText($select['s_name'], 255);
        if ($gid <= 0 || $optionName === '') {
            continue;
        }
        $safeOption = DB_escapeString($optionName);
        $sid = (int) DB_getItem(
            $_TABLES['documents_selects'], 'sid', "s_group={$gid} AND s_name='{$safeOption}'"
        );
        if ($sid <= 0) {
            $safeValue = DB_escapeString(DOCUMENTS_csvText($select['s_value'], 255));
            DB_query(
                "INSERT INTO {$_TABLES['documents_selects']} SET s_group={$gid}, s_name='{$safeOption}', "
                . "s_value='{$safeValue}', s_order=" . max(0, (int) $select['s_order'])
            );
            if (DB_error()) {
                $errors[] = 'Unable to create selection value: ' . $optionName;
            }
        }
    }
    return $map;
}

function DOCUMENTS_csvAllowedFieldType($type)
{
    $type = strtolower((string) $type);
    $allowed = array('text', 'textarea', 'decimal', 'date', 'image', 'checkbox', 'select', 'radio');
    if (function_exists('DOCUMENTS_hasMaps') && DOCUMENTS_hasMaps()) {
        $allowed[] = 'marker';
    }
    if (function_exists('DOCUMENTS_hasMediaGallery') && DOCUMENTS_hasMediaGallery()) {
        $allowed[] = 'album';
    }
    return in_array($type, $allowed, true);
}

function DOCUMENTS_csvEnsureStructure($parsed, $currentUid, &$messages, &$errors)
{
    global $_TABLES;

    $meta = $parsed['category'];
    $slug = DOCUMENTS_csvText($meta['cat_url'], 40);
    if (function_exists('DOCUMENTS_normalizeRouteSlug')) {
        $slug = DOCUMENTS_normalizeRouteSlug($slug);
    }
    if ($slug === '') {
        $errors[] = 'Invalid category slug.';
        return array(0, array());
    }

    $safeSlug = DB_escapeString($slug);
    $category = DB_fetchArray(DB_query(
        "SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}' LIMIT 1"
    ));
    $defaultGroup = DOCUMENTS_csvDefaultGroupId();

    if (is_array($category) && !empty($category['cid'])) {
        $categoryId = (int) $category['cid'];
    } else {
        $ownerId = DOCUMENTS_csvUserId($meta['owner_username'], $currentUid);
        $groupId = DOCUMENTS_csvGroupId($meta['group_name'], $defaultGroup);
        $name = DB_escapeString(DOCUMENTS_csvText($meta['cat_name'], 40));
        $help = DB_escapeString(DOCUMENTS_csvText($meta['cat_help'], 255));
        $description = DB_escapeString(DOCUMENTS_csvText($meta['metadescription'], 255));
        $header = DB_escapeString(DOCUMENTS_csvText($meta['custom_header'], 255));
        $footer = DB_escapeString(DOCUMENTS_csvText($meta['custom_footer'], 255));
        $permOwner = max(0, min(3, (int) $meta['perm_owner']));
        $permGroup = max(0, min(3, (int) $meta['perm_group']));
        $permMembers = max(0, min(3, (int) $meta['perm_members']));
        $permAnon = max(0, min(3, (int) $meta['perm_anon']));
        DB_query(
            "INSERT INTO {$_TABLES['documents_cat']} SET cat_name='{$name}', cat_url='{$safeSlug}', "
            . "cat_order=" . max(0, (int) $meta['cat_order']) . ", css='', map=" . max(0, (int) $meta['map'])
            . ", template='', list_index=" . (!empty($meta['list_index']) ? 1 : 0)
            . ", submitable=" . (!empty($meta['submitable']) ? 1 : 0)
            . ", cat_help='{$help}', metadescription='{$description}', custom_header='{$header}', custom_footer='{$footer}', "
            . "owner_id={$ownerId}, group_id={$groupId}, perm_owner={$permOwner}, perm_group={$permGroup}, "
            . "perm_members={$permMembers}, perm_anon={$permAnon}"
        );
        $categoryId = (int) DB_insertId();
        if ($categoryId <= 0 || DB_error()) {
            $errors[] = 'Unable to create category: ' . $slug;
            return array(0, array());
        }
        $messages[] = 'Category created: ' . $slug;
    }

    $selectionMap = DOCUMENTS_csvEnsureSelectGroups($parsed, $messages, $errors);
    $fieldMap = array();
    foreach ($parsed['fields'] as $variable => $field) {
        $variable = DOCUMENTS_csvText($variable, 18);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,17}$/', $variable)) {
            $errors[] = 'Invalid field variable: ' . $variable;
            continue;
        }
        $type = strtolower(trim((string) $field['f_type']));
        if (!DOCUMENTS_csvAllowedFieldType($type)) {
            $errors[] = 'Unsupported or unavailable field type for ' . $variable . ': ' . $type;
            continue;
        }

        $safeVariable = DB_escapeString($variable);
        $existing = DB_fetchArray(DB_query(
            "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id={$categoryId} "
            . "AND var_name='{$safeVariable}' LIMIT 1"
        ));
        $selId = 0;
        if ($type === 'select' || $type === 'radio') {
            $groupName = trim((string) $field['select_group']);
            if ($groupName === '' || !isset($selectionMap[$groupName])) {
                $errors[] = 'Missing selection group for field: ' . $variable;
                continue;
            }
            $selId = (int) $selectionMap[$groupName];
        } elseif ($type === 'text') {
            $format = (int) $field['select_group'];
            if (in_array($format, array(1001, 1002, 1003, 1004), true)) {
                $selId = $format;
            }
        }

        $safeName = DB_escapeString(DOCUMENTS_csvText($field['f_name'], 255));
        $safeHelp = DB_escapeString(DOCUMENTS_csvText($field['f_help'], 255));
        $set = "f_name='{$safeName}', f_order=" . max(0, (int) $field['f_order'])
            . ", f_type='" . DB_escapeString($type) . "', sel_id={$selId}, f_help='{$safeHelp}', "
            . "f_required=" . (!empty($field['f_required']) ? 1 : 0)
            . ", f_on_list=" . (!empty($field['f_on_list']) ? 1 : 0)
            . ", display_empty=" . (!empty($field['display_empty']) ? 1 : 0);

        if (is_array($existing) && !empty($existing['fid'])) {
            $fid = (int) $existing['fid'];
            if ((string) $existing['f_type'] !== $type
                && (int) DB_count($_TABLES['documents_values'], 'field_id', $fid) > 0) {
                $errors[] = 'Cannot change the type of field already in use: ' . $variable;
                continue;
            }
            DB_query("UPDATE {$_TABLES['documents_fields']} SET {$set} WHERE fid={$fid}");
        } else {
            DB_query(
                "INSERT INTO {$_TABLES['documents_fields']} SET cat_id={$categoryId}, "
                . "var_name='{$safeVariable}', {$set}, owner_id=" . (int) $currentUid
                . ", group_id={$defaultGroup}, perm_owner=3, perm_group=3, perm_members=2, perm_anon=2"
            );
            $fid = (int) DB_insertId();
            if ($fid <= 0 || DB_error()) {
                $errors[] = 'Unable to create field: ' . $variable;
                continue;
            }
            $messages[] = 'Field created: ' . $variable;
        }
        $fieldMap[$variable] = array('fid' => $fid, 'type' => $type, 'sel_id' => $selId);
    }

    return array($categoryId, $fieldMap);
}

function DOCUMENTS_csvDate($value, $fallback)
{
    $value = trim((string) $value);
    $timestamp = $value === '' ? false : strtotime($value);
    return $timestamp === false ? $fallback : date('Y-m-d H:i:s', $timestamp);
}

function DOCUMENTS_csvAnalyze($parsed)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $report = array(
        'new' => 0, 'existing' => 0, 'rows' => count($parsed['rows']),
        'missing_images' => array(), 'errors' => $parsed['errors']
    );
    foreach ($parsed['rows'] as $row) {
        $documentId = isset($row['doc_url']) ? trim((string) $row['doc_url']) : '';
        if ($documentId === '') {
            $report['new']++;
        } else {
            $safeDocument = DB_escapeString(DOCUMENTS_csvText($documentId, 40));
            if ((int) DB_count($_TABLES['documents_docs'], 'doc_url', $safeDocument) > 0) {
                $report['existing']++;
            } else {
                $report['new']++;
            }
        }
        foreach ($parsed['fields'] as $variable => $field) {
            if (strtolower((string) $field['f_type']) !== 'image' || empty($row[$variable])) {
                continue;
            }
            $filename = basename((string) $row[$variable]);
            $path = empty($_DOCUMENTS_CONF['path_images']) ? ''
                : rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR . $filename;
            if ($filename !== '' && ($path === '' || !is_file($path))) {
                $report['missing_images'][$filename] = $filename;
            }
        }
    }
    $report['missing_images'] = array_values($report['missing_images']);
    return $report;
}

function DOCUMENTS_csvSelectionIsValid($groupId, $value)
{
    global $_TABLES;
    $groupId = (int) $groupId;
    $value = trim((string) $value);
    if ($value === '') {
        return true;
    }
    if ($groupId <= 0) {
        return false;
    }
    $safeValue = DB_escapeString($value);
    $result = DB_query(
        "SELECT sid FROM {$_TABLES['documents_selects']} "
        . "WHERE s_group={$groupId} AND s_name='{$safeValue}' LIMIT 1"
    );
    return DB_numRows($result) > 0;
}

function DOCUMENTS_csvImport($parsed, $updateExisting, $importHits)
{
    global $_TABLES, $_USER;

    $messages = array();
    $errors = $parsed['errors'];
    $stats = array('created' => 0, 'updated' => 0, 'skipped' => 0);
    if (!empty($errors)) {
        return array(false, $stats, $messages, $errors);
    }

    $currentUid = isset($_USER['uid']) ? max(1, (int) $_USER['uid']) : 1;
    list($categoryId, $fieldMap) = DOCUMENTS_csvEnsureStructure(
        $parsed, $currentUid, $messages, $errors
    );
    if ($categoryId <= 0 || !empty($errors)) {
        return array(false, $stats, $messages, $errors);
    }

    $categorySlug = (string) $parsed['category']['cat_url'];
    $defaultGroup = DOCUMENTS_csvDefaultGroupId();
    $now = date('Y-m-d H:i:s');

    foreach ($parsed['rows'] as $row) {
        $line = isset($row['_line']) ? (int) $row['_line'] : 0;
        $rowSlug = isset($row['category_slug']) ? trim((string) $row['category_slug']) : '';
        if ($rowSlug !== '' && $rowSlug !== $categorySlug) {
            $errors[] = 'Line ' . $line . ': category_slug does not match the CSV metadata.';
            continue;
        }

        $documentId = isset($row['doc_url']) ? DOCUMENTS_csvText(trim((string) $row['doc_url']), 40) : '';
        if ($documentId === '') {
            reset($fieldMap);
            $firstVariable = key($fieldMap);
            $title = $firstVariable !== null && isset($row[$firstVariable]) ? (string) $row[$firstVariable] : 'document';
            if (function_exists('DOCUMENTS_documentMutationUniqueUrl')) {
                $documentId = DOCUMENTS_documentMutationUniqueUrl($title);
            } else {
                $documentId = substr(COM_makeSid(), 0, 40);
            }
        }
        if ($documentId === '') {
            $errors[] = 'Line ' . $line . ': unable to determine document URL.';
            continue;
        }

        $safeDocument = DB_escapeString($documentId);
        $existing = DB_fetchArray(DB_query(
            "SELECT * FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safeDocument}' LIMIT 1"
        ));
        $isExisting = is_array($existing) && !empty($existing['did']);
        if ($isExisting && !$updateExisting) {
            $stats['skipped']++;
            continue;
        }

        $ownerId = DOCUMENTS_csvUserId(
            isset($row['owner_username']) ? $row['owner_username'] : '', $currentUid
        );
        $groupId = DOCUMENTS_csvGroupId(
            isset($row['group_name']) ? $row['group_name'] : '', $defaultGroup
        );
        $status = isset($row['status']) ? max(0, min(3, (int) $row['status'])) : 1;
        $created = DOCUMENTS_csvDate(isset($row['created']) ? $row['created'] : '', $now);
        $modified = DOCUMENTS_csvDate(isset($row['modified']) ? $row['modified'] : '', $created);
        $hits = $importHits && isset($row['hits'])
            ? max(0, (int) $row['hits'])
            : ($isExisting ? (int) $existing['hits'] : 0);
        $permOwner = isset($row['perm_owner']) ? max(0, min(3, (int) $row['perm_owner'])) : 3;
        $permGroup = isset($row['perm_group']) ? max(0, min(3, (int) $row['perm_group'])) : 2;
        $permMembers = isset($row['perm_members']) ? max(0, min(3, (int) $row['perm_members'])) : 2;
        $permAnon = isset($row['perm_anon']) ? max(0, min(3, (int) $row['perm_anon'])) : 2;

        $rowHasError = false;
        foreach ($fieldMap as $variable => $definition) {
            if (($definition['type'] === 'select' || $definition['type'] === 'radio')
                && isset($row[$variable])
                && !DOCUMENTS_csvSelectionIsValid($definition['sel_id'], $row[$variable])) {
                $errors[] = 'Line ' . $line . ': invalid selection for ' . $variable . '.';
                $rowHasError = true;
            }
        }
        if ($rowHasError) {
            continue;
        }

        $safeCreated = DB_escapeString($created);
        $safeModified = DB_escapeString($modified);
        $set = "active={$status}, created='{$safeCreated}', modified='{$safeModified}', hits={$hits}, "
            . "owner_id={$ownerId}, group_id={$groupId}, perm_owner={$permOwner}, perm_group={$permGroup}, "
            . "perm_members={$permMembers}, perm_anon={$permAnon}";
        if ($isExisting) {
            DB_query("UPDATE {$_TABLES['documents_docs']} SET {$set} WHERE doc_url='{$safeDocument}'");
            if (DB_error()) {
                $errors[] = 'Line ' . $line . ': unable to update document ' . $documentId . '.';
                continue;
            }
            $stats['updated']++;
        } else {
            DB_query("INSERT INTO {$_TABLES['documents_docs']} SET doc_url='{$safeDocument}', {$set}");
            if (DB_error()) {
                $errors[] = 'Line ' . $line . ': unable to create document ' . $documentId . '.';
                continue;
            }
            $stats['created']++;
        }

        foreach ($fieldMap as $variable => $definition) {
            $fid = (int) $definition['fid'];
            $type = (string) $definition['type'];
            $value = isset($row[$variable]) ? (string) $row[$variable] : '';
            if ($type === 'image') {
                $value = basename($value);
                if ($isExisting && $value === '') {
                    continue;
                }
            } elseif ($type === 'checkbox') {
                $normalized = strtolower(trim($value));
                $value = ($normalized !== '' && !in_array($normalized, array('0', 'false', 'no', 'non'), true)) ? '1' : '0';
            }
            $safeValue = DB_escapeString($value);
            $vid = (int) DB_getItem(
                $_TABLES['documents_values'], 'vid', "doc_url='{$safeDocument}' AND field_id={$fid}"
            );
            if ($vid > 0) {
                DB_query(
                    "UPDATE {$_TABLES['documents_values']} SET v_value='{$safeValue}', owner_id={$ownerId}, "
                    . "group_id={$groupId}, perm_owner={$permOwner}, perm_group={$permGroup}, "
                    . "perm_members={$permMembers}, perm_anon={$permAnon} WHERE vid={$vid}"
                );
            } else {
                DB_query(
                    "INSERT INTO {$_TABLES['documents_values']} SET field_id={$fid}, v_value='{$safeValue}', "
                    . "doc_url='{$safeDocument}', owner_id={$ownerId}, group_id={$groupId}, "
                    . "perm_owner={$permOwner}, perm_group={$permGroup}, perm_members={$permMembers}, perm_anon={$permAnon}"
                );
            }
            if (DB_error()) {
                $errors[] = 'Line ' . $line . ': unable to save field ' . $variable . '.';
                break;
            }
        }

        if (function_exists('PLG_itemSaved')) {
            PLG_itemSaved($documentId, 'documents');
        }
    }

    return array(empty($errors), $stats, $messages, $errors);
}
