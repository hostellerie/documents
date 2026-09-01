<?php

/* Portable CSV import/export helpers for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'import_export.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_csvSafeText($value, $maxLength)
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
    if ($uid <= 0) {
        return '';
    }
    $value = DB_getItem($_TABLES['users'], 'username', 'uid=' . $uid);
    return $value === false ? '' : (string) $value;
}

function DOCUMENTS_csvGroupName($gid)
{
    global $_TABLES;
    $gid = (int) $gid;
    if ($gid <= 0) {
        return '';
    }
    $value = DB_getItem($_TABLES['groups'], 'grp_name', 'grp_id=' . $gid);
    return $value === false ? '' : (string) $value;
}

function DOCUMENTS_csvUserId($username, $fallback)
{
    global $_TABLES;
    $username = trim((string) $username);
    if ($username !== '') {
        $safe = DB_escapeString($username);
        $row = DB_fetchArray(DB_query("SELECT uid FROM {$_TABLES['users']} WHERE username='{$safe}' LIMIT 1"));
        if (is_array($row) && !empty($row['uid'])) {
            return (int) $row['uid'];
        }
    }
    return max(1, (int) $fallback);
}

function DOCUMENTS_csvGroupId($groupName, $fallback)
{
    global $_TABLES;
    $groupName = trim((string) $groupName);
    if ($groupName !== '') {
        $safe = DB_escapeString($groupName);
        $row = DB_fetchArray(DB_query("SELECT grp_id FROM {$_TABLES['groups']} WHERE grp_name='{$safe}' LIMIT 1"));
        if (is_array($row) && !empty($row['grp_id'])) {
            return (int) $row['grp_id'];
        }
    }
    return max(1, (int) $fallback);
}

function DOCUMENTS_csvDefaultGroupId()
{
    global $_TABLES;
    $row = DB_fetchArray(DB_query("SELECT grp_id FROM {$_TABLES['groups']} WHERE grp_name='Documents Admin' LIMIT 1"));
    return is_array($row) && !empty($row['grp_id']) ? (int) $row['grp_id'] : 1;
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
    $categoryId = (int) $categoryId;
    $result = DB_query("SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id={$categoryId} ORDER BY f_order ASC, fid ASC");
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
    $row = DB_fetchArray(DB_query("SELECT * FROM {$_TABLES['documents_selects_group']} WHERE gid={$groupId} LIMIT 1"));
    return is_array($row) ? $row : array();
}

function DOCUMENTS_csvWriteExport($categoryId, $templateOnly)
{
    global $_TABLES;

    $category = DOCUMENTS_csvCategory($categoryId);
    if (empty($category)) {
        return false;
    }
    $fields = DOCUMENTS_csvFields($categoryId);
    if (empty($fields)) {
        return false;
    }

    $out = fopen('php://output', 'w');
    if (!$out) {
        return false;
    }

    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('#documents-format', '1'), ';');
    fputcsv($out, array(
        '#category',
        $category['cat_name'], $category['cat_url'], $category['cat_order'], $category['css'], $category['map'],
        $category['template'], $category['list_index'], $category['submitable'], $category['cat_help'],
        $category['metadescription'], $category['custom_header'], $category['custom_footer'],
        DOCUMENTS_csvUserName($category['owner_id']), DOCUMENTS_csvGroupName($category['group_id']),
        $category['perm_owner'], $category['perm_group'], $category['perm_members'], $category['perm_anon']
    ), ';');

    $selectGroupsDone = array();
    foreach ($fields as $field) {
        $selectGroupName = '';
        if (in_array(strtolower((string) $field['f_type']), array('select', 'radio'), true) && (int) $field['sel_id'] > 0) {
            $group = DOCUMENTS_csvSelectGroup($field['sel_id']);
            if (!empty($group)) {
                $selectGroupName = (string) $group['g_name'];
                $groupId = (int) $group['gid'];
                if (!isset($selectGroupsDone[$groupId])) {
                    $selectGroupsDone[$groupId] = true;
                    $options = DB_query("SELECT * FROM {$_TABLES['documents_selects']} WHERE s_group={$groupId} ORDER BY s_order ASC, sid ASC");
                    while ($option = DB_fetchArray($options)) {
                        fputcsv($out, array('#select', $group['g_name'], $group['g_help'], $option['s_name'], $option['s_value'], $option['s_order']), ';');
                    }
                }
            }
        }
        fputcsv($out, array(
            '#field', $field['var_name'], $field['f_name'], $field['f_order'], $field['f_type'], $selectGroupName,
            $field['f_help'], $field['f_required'], $field['f_on_list'], $field['display_empty']
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
        $sample = array($category['cat_url'], '', '1', '', '', '', '', '3', '2', '2', '2', '0');
        foreach ($fields as $field) {
            $sample[] = '';
        }
        fputcsv($out, $sample, ';');
        fclose($out);
        return true;
    }

    $sql = "SELECT DISTINCT d.* FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.cat_id=" . (int) $categoryId . " ORDER BY d.did ASC";
    $documents = DB_query($sql);
    while ($document = DB_fetchArray($documents)) {
        if (!is_array($document) || empty($document['doc_url'])) {
            continue;
        }
        $row = array(
            $category['cat_url'], $document['doc_url'], $document['active'], $document['created'], $document['modified'],
            DOCUMENTS_csvUserName($document['owner_id']), DOCUMENTS_csvGroupName($document['group_id']),
            $document['perm_owner'], $document['perm_group'], $document['perm_members'], $document['perm_anon'], $document['hits']
        );
        $safeDocument = DB_escapeString($document['doc_url']);
        foreach ($fields as $field) {
            $value = DB_getItem(
                $_TABLES['documents_values'],
                'v_value',
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
    $result = array(
        'format' => '', 'category' => array(), 'fields' => array(), 'selects' => array(),
        'header' => array(), 'rows' => array(), 'errors' => array()
    );
    if (!is_file($path) || !is_readable($path)) {
        $result['errors'][] = 'CSV file is not readable.';
        return $result;
    }

    $handle = fopen($path, 'r');
    if (!$handle) {
        $result['errors'][] = 'Unable to open CSV file.';
        return $result;
    }

    $line = 0;
    while (($cells = fgetcsv($handle, 0, ';')) !== false) {
        $line++;
        if (!is_array($cells) || count($cells) === 0) {
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
            $result['format'] = isset($cells[1]) ? trim((string) $cells[1]) : '';
            continue;
        }
        if ($first === '#category') {
            $keys = array('cat_name','cat_url','cat_order','css','map','template','list_index','submitable','cat_help','metadescription','custom_header','custom_footer','owner_username','group_name','perm_owner','perm_group','perm_members','perm_anon');
            foreach ($keys as $index => $key) {
                $result['category'][$key] = isset($cells[$index + 1]) ? (string) $cells[$index + 1] : '';
            }
            continue;
        }
        if ($first === '#field') {
            $keys = array('var_name','f_name','f_order','f_type','select_group','f_help','f_required','f_on_list','display_empty');
            $field = array();
            foreach ($keys as $index => $key) {
                $field[$key] = isset($cells[$index + 1]) ? (string) $cells[$index + 1] : '';
            }
            if ($field['var_name'] !== '') {
                $result['fields'][$field['var_name']] = $field;
            }
            continue;
        }
        if ($first === '#select') {
            $result['selects'][] = array(
                'group_name' => isset($cells[1]) ? (string) $cells[1] : '',
                'group_help' => isset($cells[2]) ? (string) $cells[2] : '',
                's_name' => isset($cells[3]) ? (string) $cells[3] : '',
                's_value' => isset($cells[4]) ? (string) $cells[4] : '',
                's_order' => isset($cells[5]) ? (int) $cells[5] : 0
            );
            continue;
        }
        if (empty($result['header'])) {
            $result['header'] = array_map('trim', $cells);
            continue;
        }
        $row = array();
        foreach ($result['header'] as $index => $key) {
            $row[$key] = isset($cells[$index]) ? (string) $cells[$index] : '';
        }
        $row['_line'] = $line;
        $result['rows'][] = $row;
    }
    fclose($handle);

    if ($result['format'] !== '1') {
        $result['errors'][] = 'Unsupported or missing Documents CSV format version.';
    }
    if (empty($result['category']['cat_url'])) {
        $result['errors'][] = 'Category metadata is missing.';
    }
    if (empty($result['fields'])) {
        $result['errors'][] = 'Field metadata is missing.';
    }
    if (empty($result['header'])) {
        $result['errors'][] = 'CSV header is missing.';
    }
    return $result;
}

function DOCUMENTS_csvEnsureSelectGroups($parsed, &$messages)
{
    global $_TABLES;
    $map = array();
    foreach ($parsed['selects'] as $select) {
        $name = DOCUMENTS_csvSafeText($select['group_name'], 255);
        if ($name === '') {
            continue;
        }
        if (isset($map[$name])) {
            $gid = $map[$name];
        } else {
            $safeName = DB_escapeString($name);
            $row = DB_fetchArray(DB_query("SELECT gid FROM {$_TABLES['documents_selects_group']} WHERE g_name='{$safeName}' LIMIT 1"));
            if (is_array($row) && !empty($row['gid'])) {
                $gid = (int) $row['gid'];
            } else {
                $safeHelp = DB_escapeString(DOCUMENTS_csvSafeText($select['group_help'], 255));
                DB_query("INSERT INTO {$_TABLES['documents_selects_group']} SET g_name='{$safeName}', g_help='{$safeHelp}'");
                $gid = (int) DB_insertId();
                $messages[] = 'Selection group created: ' . $name;
            }
            $map[$name] = $gid;
        }
        if ($gid <= 0 || trim((string) $select['s_name']) === '') {
            continue;
        }
        $safeOption = DB_escapeString(DOCUMENTS_csvSafeText($select['s_name'], 255));
        $exists = DB_query("SELECT sid FROM {$_TABLES['documents_selects']} WHERE s_group={$gid} AND s_name='{$safeOption}' LIMIT 1");
        if (DB_numRows($exists) === 0) {
            $safeValue = DB_escapeString(DOCUMENTS_csvSafeText($select['s_value'], 255));
            DB_query("INSERT INTO {$_TABLES['documents_selects']} SET s_group={$gid}, s_name='{$safeOption}', s_value='{$safeValue}', s_order=" . (int) $select['s_order']);
        }
    }
    return $map;
}

function DOCUMENTS_csvEnsureCategoryAndFields($parsed, $currentUid, &$messages, &$errors)
{
    global $_TABLES;

    $categoryMeta = $parsed['category'];
    $slug = DOCUMENTS_csvSafeText($categoryMeta['cat_url'], 40);
    if (function_exists('DOCUMENTS_normalizeRouteSlug')) {
        $slug = DOCUMENTS_normalizeRouteSlug($slug);
    }
    if ($slug === '') {
        $errors[] = 'Invalid category slug.';
        return array(0, array());
    }
    $safeSlug = DB_escapeString($slug);
    $row = DB_fetchArray(DB_query("SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}' LIMIT 1"));
    $defaultGroup = DOCUMENTS_csvDefaultGroupId();
    if (is_array($row) && !empty($row['cid'])) {
        $categoryId = (int) $row['cid'];
    } else {
        $ownerId = DOCUMENTS_csvUserId($categoryMeta['owner_username'], $currentUid);
        $groupId = DOCUMENTS_csvGroupId($categoryMeta['group_name'], $defaultGroup);
        $name = DB_escapeString(DOCUMENTS_csvSafeText($categoryMeta['cat_name'], 40));
        $help = DB_escapeString(DOCUMENTS_csvSafeText($categoryMeta['cat_help'], 255));
        $meta = DB_escapeString(DOCUMENTS_csvSafeText($categoryMeta['metadescription'], 255));
        $header = DB_escapeString(DOCUMENTS_csvSafeText($categoryMeta['custom_header'], 255));
        $footer = DB_escapeString(DOCUMENTS_csvSafeText($categoryMeta['custom_footer'], 255));
        DB_query(
            "INSERT INTO {$_TABLES['documents_cat']} SET cat_name='{$name}', cat_url='{$safeSlug}', cat_order=" . max(0, (int) $categoryMeta['cat_order'])
            . ", css='', map=" . max(0, (int) $categoryMeta['map']) . ", template='', list_index=" . (!empty($categoryMeta['list_index']) ? 1 : 0)
            . ", submitable=" . (!empty($categoryMeta['submitable']) ? 1 : 0) . ", cat_help='{$help}', metadescription='{$meta}', custom_header='{$header}', custom_footer='{$footer}', "
            . "owner_id={$ownerId}, group_id={$groupId}, perm_owner=" . max(0, min(3, (int) $categoryMeta['perm_owner']))
            . ", perm_group=" . max(0, min(3, (int) $categoryMeta['perm_group'])) . ", perm_members=" . max(0, min(3, (int) $categoryMeta['perm_members']))
            . ", perm_anon=" . max(0, min(3, (int) $categoryMeta['perm_anon'])
        );
        $categoryId = (int) DB_insertId();
        if ($categoryId <= 0 || DB_error()) {
            $errors[] = 'Unable to create category ' . $slug . '.';
            return array(0, array());
        }
        $messages[] = 'Category created: ' . $slug;
    }

    $selectMap = DOCUMENTS_csvEnsureSelectGroups($parsed, $messages);
    $fieldMap = array();
    foreach ($parsed['fields'] as $variable => $field) {
        $variable = DOCUMENTS_csvSafeText($variable, 18);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,17}$/', $variable)) {
            $errors[] = 'Invalid field variable: ' . $variable;
            continue;
        }
        $safeVariable = DB_escapeString($variable);
        $existing = DB_fetchArray(DB_query("SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id={$categoryId} AND var_name='{$safeVariable}' LIMIT 1"));
        $type = strtolower(trim((string) $field['f_type']));
        $allowed = array('text','textarea','decimal','date','image','checkbox','select','radio','marker','album');
        if (!in_array($type, $allowed, true)) {
            $errors[] = 'Unsupported field type for ' . $variable . ': ' . $type;
            continue;
        }
        $selId = 0;
        if ($type === 'select' || $type === 'radio') {
            $groupName = trim((string) $field['select_group']);
            if ($groupName === '' || !isset($selectMap[$groupName])) {
                $errors[] = 'Missing selection group for field ' . $variable . '.';
                continue;
            }
            $selId = (int) $selectMap[$groupName];
        } elseif ($type === 'text') {
            $candidate = (int) $field['select_group'];
            if (in_array($candidate, array(1001,1002,1003,1004), true)) {
                $selId = $candidate;
            }
        }
        $safeName = DB_escapeString(DOCUMENTS_csvSafeText($field['f_name'], 255));
        $safeHelp = DB_escapeString(DOCUMENTS_csvSafeText($field['f_help'], 255));
        $set = "f_name='{$safeName}', f_order=" . max(0, (int) $field['f_order']) . ", f_type='" . DB_escapeString($type) . "', sel_id={$selId}, "
            . "f_help='{$safeHelp}', f_required=" . (!empty($field['f_required']) ? 1 : 0) . ", f_on_list=" . (!empty($field['f_on_list']) ? 1 : 0)
            . ", display_empty=" . (!empty($field['display_empty']) ? 1 : 0);
        if (is_array($existing) && !empty($existing['fid'])) {
            $fid = (int) $existing['fid'];
            if ((string) $existing['f_type'] !== $type && (int) DB_count($_TABLES['documents_values'], 'field_id', $fid) > 0) {
                $errors[] = 'Cannot change field type already used: ' . $variable;
                continue;
            }
            DB_query("UPDATE {$_TABLES['documents_fields']} SET {$set} WHERE fid={$fid}");
        } else {
            DB_query("INSERT INTO {$_TABLES['documents_fields']} SET cat_id={$categoryId}, var_name='{$safeVariable}', {$set}, owner_id=" . (int) $currentUid . ", group_id={$defaultGroup}, perm_owner=3, perm_group=3, perm_members=2, perm_anon=2");
            $fid = (int) DB_insertId();
            $messages[] = 'Field created: ' . $variable;
        }
        if ($fid > 0) {
            $fieldMap[$variable] = array('fid' => $fid, 'type' => $type);
        }
    }
    return array($categoryId, $fieldMap);
}

function DOCUMENTS_csvValidDate($value, $fallback)
{
    $value = trim((string) $value);
    if ($value !== '' && strtotime($value) !== false) {
        return date('Y-m-d H:i:s', strtotime($value));
    }
    return $fallback;
}

function DOCUMENTS_csvAnalyze($parsed)
{
    global $_TABLES, $_DOCUMENTS_CONF;
    $report = array('new' => 0, 'existing' => 0, 'rows' => count($parsed['rows']), 'missing_images' => array(), 'errors' => $parsed['errors']);
    foreach ($parsed['rows'] as $row) {
        $id = isset($row['doc_url']) ? trim((string) $row['doc_url']) : '';
        if ($id !== '') {
            $safe = DB_escapeString($id);
            if (DB_numRows(DB_query("SELECT did FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safe}' LIMIT 1")) > 0) {
                $report['existing']++;
            } else {
                $report['new']++;
            }
        } else {
            $report['new']++;
        }
        foreach ($parsed['fields'] as $variable => $field) {
            if (strtolower((string) $field['f_type']) !== 'image' || empty($row[$variable])) {
                continue;
            }
            $filename = basename((string) $row[$variable]);
            $path = isset($_DOCUMENTS_CONF['path_images']) ? rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR . $filename : '';
            if ($filename !== '' && ($path === '' || !is_file($path))) {
                $report['missing_images'][$filename] = $filename;
            }
        }
    }
    $report['missing_images'] = array_values($report['missing_images']);
    return $report;
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
    list($categoryId, $fieldMap) = DOCUMENTS_csvEnsureCategoryAndFields($parsed, $currentUid, $messages, $errors);
    if ($categoryId <= 0 || !empty($errors)) {
        return array(false, $stats, $messages, $errors);
    }

    $categorySlug = (string) $parsed['category']['cat_url'];
    $now = date('Y-m-d H:i:s');
    $defaultGroup = DOCUMENTS_csvDefaultGroupId();

    foreach ($parsed['rows'] as $row) {
        $rowSlug = isset($row['category_slug']) ? trim((string) $row['category_slug']) : '';
        if ($rowSlug !== '' && $rowSlug !== $categorySlug) {
            $errors[] = 'Line ' . (int) $row['_line'] . ': category_slug does not match export metadata.';
            continue;
        }
        $documentId = isset($row['doc_url']) ? trim((string) $row['doc_url']) : '';
        if ($documentId !== '') {
            $documentId = DOCUMENTS_csvSafeText($documentId, 40);
        }
        if ($documentId === '') {
            $firstVariable = key($fieldMap);
            $title = $firstVariable !== null && isset($row[$firstVariable]) ? (string) $row[$firstVariable] : 'document';
            if (function_exists('DOCUMENTS_documentMutationUniqueUrl')) {
                $documentId = DOCUMENTS_documentMutationUniqueUrl($title);
            } else {
                $documentId = substr(COM_makeSid(), 0, 40);
            }
        }
        $safeDocument = DB_escapeString($documentId);
        $existing = DB_fetchArray(DB_query("SELECT * FROM {$_TABLES['documents_docs']} WHERE doc_url='{$safeDocument}' LIMIT 1"));
        $isExisting = is_array($existing) && !empty($existing['did']);
        if ($isExisting && !$updateExisting) {
            $stats['skipped']++;
            continue;
        }

        $ownerId = DOCUMENTS_csvUserId(isset($row['owner_username']) ? $row['owner_username'] : '', $currentUid);
        $groupId = DOCUMENTS_csvGroupId(isset($row['group_name']) ? $row['group_name'] : '', $defaultGroup);
        $status = isset($row['status']) ? max(0, min(3, (int) $row['status'])) : 1;
        $created = DOCUMENTS_csvValidDate(isset($row['created']) ? $row['created'] : '', $now);
        $modified = DOCUMENTS_csvValidDate(isset($row['modified']) ? $row['modified'] : '', $created);
        $permOwner = isset($row['perm_owner']) ? max(0, min(3, (int) $row['perm_owner'])) : 3;
        $permGroup = isset($row['perm_group']) ? max(0, min(3, (int) $row['perm_group'])) : 2;
        $permMembers = isset($row['perm_members']) ? max(0, min(3, (int) $row['perm_members'])) : 2;
        $permAnon = isset($row['perm_anon']) ? max(0, min(3, (int) $row['perm_anon'])) : 2;
        $hits = $importHits && isset($row['hits']) ? max(0, (int) $row['hits']) : ($isExisting ? (int) $existing['hits'] : 0);
        $safeCreated = DB_escapeString($created);
        $safeModified = DB_escapeString($modified);
        $set = "active={$status}, created='{$safeCreated}', modified='{$safeModified}', hits={$hits}, owner_id={$ownerId}, group_id={$groupId}, "
            . "perm_owner={$permOwner}, perm_group={$permGroup}, perm_members={$permMembers}, perm_anon={$permAnon}";
        if ($isExisting) {
            DB_query("UPDATE {$_TABLES['documents_docs']} SET {$set} WHERE doc_url='{$safeDocument}'");
            $stats['updated']++;
        } else {
            DB_query("INSERT INTO {$_TABLES['documents_docs']} SET doc_url='{$safeDocument}', {$set}");
            if (DB_error()) {
                $errors[] = 'Line ' . (int) $row['_line'] . ': unable to create document ' . $documentId . '.';
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
            }
            if ($type === 'checkbox') {
                $value = !empty($value) && !in_array(strtolower(trim($value)), array('0','false','no','non'), true) ? '1' : '0';
            }
            if (($type === 'select' || $type === 'radio') && $value !== '') {
                $fieldRow = DB_fetchArray(DB_query("SELECT sel_id FROM {$_TABLES['documents_fields']} WHERE fid={$fid} LIMIT 1"));
                $groupIdForField = is_array($fieldRow) ? (int) $fieldRow['sel_id'] : 0;
                $safeValueCheck = DB_escapeString($value);
                if ($groupIdForField <= 0 || DB_numRows(DB_query("SELECT sid FROM {$_TABLES['documents_selects']} WHERE s_group={$groupIdForField} AND s_name='{$safeValueCheck}' LIMIT 1")) === 0) {
                    $errors[] = 'Line ' . (int) $row['_line'] . ': invalid selection for ' . $variable . '.';
                    continue;
                }
            }
            $safeValue = DB_escapeString($value);
            $valueRow = DB_fetchArray(DB_query("SELECT vid FROM {$_TABLES['documents_values']} WHERE doc_url='{$safeDocument}' AND field_id={$fid} LIMIT 1"));
            if (is_array($valueRow) && !empty($valueRow['vid'])) {
                DB_query("UPDATE {$_TABLES['documents_values']} SET v_value='{$safeValue}', owner_id={$ownerId}, group_id={$groupId}, perm_owner={$permOwner}, perm_group={$permGroup}, perm_members={$permMembers}, perm_anon={$permAnon} WHERE vid=" . (int) $valueRow['vid']);
            } else {
                DB_query("INSERT INTO {$_TABLES['documents_values']} SET field_id={$fid}, v_value='{$safeValue}', doc_url='{$safeDocument}', owner_id={$ownerId}, group_id={$groupId}, perm_owner={$permOwner}, perm_group={$permGroup}, perm_members={$permMembers}, perm_anon={$permAnon}");
            }
        }

        if (function_exists('PLG_itemSaved')) {
            PLG_itemSaved($documentId, 'documents');
        }
    }

    return array(empty($errors), $stats, $messages, $errors);
}
