<?php

/* Secure administration mutations for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'admin_mutations.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_adminPlainText($value, $maxLength)
{
    if (is_array($value) || is_object($value) || is_resource($value)) {
        return '';
    }

    $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
    $value = trim(str_replace("\0", '', $value));
    $maxLength = max(1, (int) $maxLength);

    if (function_exists('MBYTE_strlen') && function_exists('MBYTE_substr')) {
        return MBYTE_strlen($value) > $maxLength
            ? MBYTE_substr($value, 0, $maxLength)
            : $value;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $maxLength
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : $value;
    }

    return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
}

function DOCUMENTS_adminHtml($value, $maxLength)
{
    if (is_array($value) || is_object($value) || is_resource($value)) {
        return '';
    }

    $value = COM_checkHTML((string) $value, 'documents.admin');
    $maxLength = max(1, (int) $maxLength);
    if (function_exists('MBYTE_strlen') && function_exists('MBYTE_substr')) {
        return MBYTE_strlen($value) > $maxLength ? MBYTE_substr($value, 0, $maxLength) : $value;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $maxLength
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : $value;
    }

    return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
}

function DOCUMENTS_adminSlug($value, $maxLength)
{
    $value = DOCUMENTS_normalizeRouteSlug((string) $value);
    return DOCUMENTS_adminPlainText($value, $maxLength);
}

function DOCUMENTS_adminPermissions($request)
{
    if (function_exists('DOCUMENTS_requestPermissions')) {
        return DOCUMENTS_requestPermissions($request, array(3, 3, 2, 2));
    }

    $defaults = array(3, 3, 2, 2);
    $keys = array('perm_owner', 'perm_group', 'perm_members', 'perm_anon');
    $result = array();
    foreach ($keys as $index => $key) {
        $value = isset($request[$key]) ? (int) $request[$key] : $defaults[$index];
        $result[] = max(0, min(3, $value));
    }
    return $result;
}

function DOCUMENTS_adminReorderCategories()
{
    global $_TABLES;

    $result = DB_query("SELECT cid, cat_order FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC, cid ASC");
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        if ((int) $row['cat_order'] !== $order) {
            DB_query("UPDATE {$_TABLES['documents_cat']} SET cat_order={$order} WHERE cid=" . (int) $row['cid']);
        }
        $order += 10;
    }
}

function DOCUMENTS_adminReorderSelects($groupId)
{
    global $_TABLES;

    $groupId = (int) $groupId;
    if ($groupId <= 0) {
        return;
    }

    $result = DB_query(
        "SELECT sid, s_order FROM {$_TABLES['documents_selects']} "
        . "WHERE s_group={$groupId} ORDER BY s_order ASC, sid ASC"
    );
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        if ((int) $row['s_order'] !== $order) {
            DB_query("UPDATE {$_TABLES['documents_selects']} SET s_order={$order} WHERE sid=" . (int) $row['sid']);
        }
        $order += 10;
    }
}

function DOCUMENTS_adminCategoryHasDocuments($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    if ($categoryId <= 0) {
        return false;
    }

    $sql = "SELECT d.did FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.cat_id={$categoryId} LIMIT 1";

    return DB_numRows(DB_query($sql)) > 0;
}

function DOCUMENTS_adminSaveCategory($request)
{
    global $_CONF, $_TABLES;

    $cid = isset($request['cid']) ? (int) $request['cid'] : 0;
    $operation = isset($request['op']) ? (string) $request['op'] : 'save';

    if ($operation === 'delete') {
        if ($cid <= 0) {
            return array(false, 'Invalid category.');
        }
        if (DOCUMENTS_adminCategoryHasDocuments($cid)) {
            return array(false, 'This category still contains documents and cannot be deleted.');
        }
        DB_query("DELETE FROM {$_TABLES['documents_fields']} WHERE cat_id={$cid}");
        DB_query("DELETE FROM {$_TABLES['documents_cat']} WHERE cid={$cid}");
        DOCUMENTS_adminReorderCategories();
        return array(!DB_error(), DB_error() ? 'Unable to delete category.' : 'Category deleted.');
    }

    $name = DOCUMENTS_adminPlainText(isset($request['cat_name']) ? $request['cat_name'] : '', 40);
    $slugInput = isset($request['cat_url']) ? $request['cat_url'] : '';
    if (!is_array($slugInput) && trim((string) $slugInput) === '') {
        $slugInput = $name;
    }
    $slug = DOCUMENTS_adminSlug($slugInput, 40);
    if ($name === '' || $slug === '') {
        return array(false, 'Category name and URL are required.');
    }

    $safeSlug = DB_escapeString($slug);
    $duplicateSql = "SELECT cid FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}'";
    if ($cid > 0) {
        $duplicateSql .= " AND cid<>{$cid}";
    }
    $duplicateSql .= ' LIMIT 1';
    if (DB_numRows(DB_query($duplicateSql)) > 0) {
        return array(false, 'This category URL already exists.');
    }

    $assetsFile = isset($_CONF['path'])
        ? $_CONF['path'] . 'plugins/documents/custom_assets.php'
        : '';
    if ($assetsFile !== '' && is_file($assetsFile)) {
        require_once $assetsFile;
    }
    if (function_exists('DOCUMENTS_ensureCustomAssetDirectories')) {
        DOCUMENTS_ensureCustomAssetDirectories();
    }

    $cssInput = DOCUMENTS_adminPlainText(isset($request['css']) ? $request['css'] : '', 18);
    $templateInput = DOCUMENTS_adminPlainText(isset($request['template']) ? $request['template'] : '', 18);

    $css = $cssInput;
    if ($cssInput !== '') {
        if (!function_exists('DOCUMENTS_customStyleName')
            || DOCUMENTS_customStyleName($cssInput) === ''
            || DOCUMENTS_customStylePath($cssInput) === '') {
            return array(false, 'CSS must be the filename of an existing .css file in the persistent Documents styles directory.');
        }
        $css = DOCUMENTS_customStyleName($cssInput);
    }

    $template = $templateInput;
    if ($templateInput !== '') {
        if (!function_exists('DOCUMENTS_templateName')
            || DOCUMENTS_templateName($templateInput) === ''
            || !function_exists('DOCUMENTS_customTemplateIsReady')
            || !DOCUMENTS_customTemplateIsReady($templateInput)) {
            return array(false, 'Template must name an existing persistent Documents template containing document.thtml and doccomments.thtml.');
        }
        $template = DOCUMENTS_templateName($templateInput);
    }

    $help = DOCUMENTS_adminPlainText(isset($request['cat_help']) ? $request['cat_help'] : '', 255);
    $meta = DOCUMENTS_adminPlainText(isset($request['metadescription']) ? $request['metadescription'] : '', 255);
    $header = DOCUMENTS_adminHtml(isset($request['custom_header']) ? $request['custom_header'] : '', 255);
    $footer = DOCUMENTS_adminHtml(isset($request['custom_footer']) ? $request['custom_footer'] : '', 255);

    $catOrder = isset($request['cat_order']) ? max(0, (int) $request['cat_order']) : 0;
    $listIndex = !empty($request['list_index']) ? 1 : 0;
    $submitable = !empty($request['submitable']) ? 1 : 0;
    $ownerId = isset($request['owner_id']) ? max(1, (int) $request['owner_id']) : 1;
    $groupId = isset($request['group_id']) ? max(1, (int) $request['group_id']) : 1;
    list($permOwner, $permGroup, $permMembers, $permAnon) = DOCUMENTS_adminPermissions($request);

    if (DOCUMENTS_hasMaps()) {
        $map = isset($request['map']) ? max(0, (int) $request['map']) : 0;
    } elseif ($cid > 0) {
        $map = (int) DB_getItem($_TABLES['documents_cat'], 'map', 'cid=' . $cid);
    } else {
        $map = 0;
    }

    $values = array(
        'cat_name' => DB_escapeString($name),
        'cat_url' => DB_escapeString($slug),
        'css' => DB_escapeString($css),
        'template' => DB_escapeString($template),
        'cat_help' => DB_escapeString($help),
        'metadescription' => DB_escapeString($meta),
        'custom_header' => DB_escapeString($header),
        'custom_footer' => DB_escapeString($footer)
    );

    $set = "cat_name='{$values['cat_name']}', cat_url='{$values['cat_url']}', "
        . "cat_order={$catOrder}, css='{$values['css']}', map={$map}, "
        . "template='{$values['template']}', list_index={$listIndex}, submitable={$submitable}, "
        . "cat_help='{$values['cat_help']}', metadescription='{$values['metadescription']}', "
        . "custom_header='{$values['custom_header']}', custom_footer='{$values['custom_footer']}', "
        . "owner_id={$ownerId}, group_id={$groupId}, perm_owner={$permOwner}, "
        . "perm_group={$permGroup}, perm_members={$permMembers}, perm_anon={$permAnon}";

    if ($cid > 0) {
        DB_query("UPDATE {$_TABLES['documents_cat']} SET {$set} WHERE cid={$cid}");
    } else {
        DB_query("INSERT INTO {$_TABLES['documents_cat']} SET {$set}");
    }

    if (DB_error()) {
        return array(false, 'Unable to save category.');
    }

    DOCUMENTS_adminReorderCategories();
    return array(true, 'Category saved.');
}

function DOCUMENTS_adminSaveGroup($request)
{
    global $_TABLES;

    $gid = isset($request['gid']) ? (int) $request['gid'] : 0;
    $operation = isset($request['op']) ? (string) $request['op'] : 'save';

    if ($operation === 'delete') {
        if ($gid <= 0) {
            return array(false, 'Invalid group.');
        }
        DB_query("DELETE FROM {$_TABLES['documents_selects']} WHERE s_group={$gid}");
        DB_query("DELETE FROM {$_TABLES['documents_groups']} WHERE gid={$gid}");
        return array(!DB_error(), DB_error() ? 'Unable to delete group.' : 'Group deleted.');
    }

    $name = DOCUMENTS_adminPlainText(isset($request['g_name']) ? $request['g_name'] : '', 255);
    $help = DOCUMENTS_adminPlainText(isset($request['g_help']) ? $request['g_help'] : '', 255);
    if ($name === '') {
        return array(false, 'Group name is required.');
    }

    $safeName = DB_escapeString($name);
    $safeHelp = DB_escapeString($help);
    if ($gid > 0) {
        DB_query("UPDATE {$_TABLES['documents_groups']} SET g_name='{$safeName}', g_help='{$safeHelp}' WHERE gid={$gid}");
    } else {
        DB_query("INSERT INTO {$_TABLES['documents_groups']} SET g_name='{$safeName}', g_help='{$safeHelp}'");
    }

    return array(!DB_error(), DB_error() ? 'Unable to save group.' : 'Group saved.');
}

function DOCUMENTS_adminSaveSelect($request)
{
    global $_TABLES;

    $sid = isset($request['sid']) ? (int) $request['sid'] : 0;
    $operation = isset($request['op']) ? (string) $request['op'] : 'save';

    if ($operation === 'delete') {
        if ($sid <= 0) {
            return array(false, 'Invalid selection.');
        }
        $groupId = (int) DB_getItem($_TABLES['documents_selects'], 's_group', 'sid=' . $sid);
        DB_query("DELETE FROM {$_TABLES['documents_selects']} WHERE sid={$sid}");
        if ($groupId > 0) {
            DOCUMENTS_adminReorderSelects($groupId);
        }
        return array(!DB_error(), DB_error() ? 'Unable to delete selection.' : 'Selection deleted.');
    }

    $groupId = isset($request['s_group']) ? max(0, (int) $request['s_group']) : 0;
    $name = DOCUMENTS_adminPlainText(isset($request['s_name']) ? $request['s_name'] : '', 255);
    $value = DOCUMENTS_adminPlainText(isset($request['s_value']) ? $request['s_value'] : '', 255);
    $order = isset($request['s_order']) ? max(0, (int) $request['s_order']) : 0;

    if ($groupId <= 0 || $name === '') {
        return array(false, 'Selection group and name are required.');
    }

    $safeName = DB_escapeString($name);
    $safeValue = DB_escapeString($value);
    if ($sid > 0) {
        DB_query(
            "UPDATE {$_TABLES['documents_selects']} SET s_group={$groupId}, s_name='{$safeName}', "
            . "s_value='{$safeValue}', s_order={$order} WHERE sid={$sid}"
        );
    } else {
        DB_query(
            "INSERT INTO {$_TABLES['documents_selects']} SET s_group={$groupId}, s_name='{$safeName}', "
            . "s_value='{$safeValue}', s_order={$order}"
        );
    }

    if (!DB_error()) {
        DOCUMENTS_adminReorderSelects($groupId);
    }
    return array(!DB_error(), DB_error() ? 'Unable to save selection.' : 'Selection saved.');
}
