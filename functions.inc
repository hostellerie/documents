<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | functions.inc                                                             |
// |                                                                           |
// | Common code and Geeklog Plugin API implementation.                        |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// +---------------------------------------------------------------------------+

/** @package Documents */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die('This file can not be used on its own.');
}

$plugin_path = $_CONF['path'] . 'plugins/documents/';
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}

if (!isset($_DOCUMENTS_CONF)) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    $documents_config = config::get_instance();
    $_DOCUMENTS_CONF = $documents_config->get_config('documents');
}

function DOCUMENTS_dataDir()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return dirname($base) . DIRECTORY_SEPARATOR
        . basename($base) . '-documents' . DIRECTORY_SEPARATOR;
}

function DOCUMENTS_legacyDataDir()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return $base . DIRECTORY_SEPARATOR . 'data_documents' . DIRECTORY_SEPARATOR;
}

function DOCUMENTS_isPluginActive($plugin)
{
    global $_PLUGINS;

    return is_array($_PLUGINS) && in_array($plugin, $_PLUGINS, true);
}

function DOCUMENTS_hasMaps()
{
    global $_CONF;

    return DOCUMENTS_isPluginActive('maps')
        && isset($_CONF['path'])
        && is_dir($_CONF['path'] . 'plugins/maps');
}

function DOCUMENTS_hasMediaGallery()
{
    global $_CONF;

    return DOCUMENTS_isPluginActive('mediagallery')
        && isset($_CONF['path'])
        && is_dir($_CONF['path'] . 'plugins/mediagallery');
}

$_DOCUMENTS_CONF['path_html'] = $_CONF['path_html'] . $_DOCUMENTS_CONF['documents_folder'] . '/';
$_DOCUMENTS_CONF['site_url'] = $_CONF['site_url'] . '/' . $_DOCUMENTS_CONF['documents_folder'];
$_DOCUMENTS_CONF['path_images'] = $_CONF['path_images'] . 'documents/items/';
$_DOCUMENTS_CONF['path_cat_images'] = $_CONF['path_images'] . 'documents/categories/';
$_DOCUMENTS_CONF['images_url'] = $_CONF['site_url'] . '/'
    . substr($_CONF['path_images'], strlen($_CONF['path_html']), -1)
    . '/documents/items/';
$_DOCUMENTS_CONF['images_cat_url'] = $_CONF['site_url'] . '/'
    . substr($_CONF['path_images'], strlen($_CONF['path_html']), -1)
    . '/documents/categories/';
$_DOCUMENTS_CONF['path_data'] = DOCUMENTS_dataDir();
$_DOCUMENTS_CONF['legacy_path_data'] = DOCUMENTS_legacyDataDir();

if (!isset($_DOCUMENTS_CONF['max_image_width']) || (int) $_DOCUMENTS_CONF['max_image_width'] <= 0) {
    $_DOCUMENTS_CONF['max_image_width'] = 3000;
}
if (!isset($_DOCUMENTS_CONF['max_image_height']) || (int) $_DOCUMENTS_CONF['max_image_height'] <= 0) {
    $_DOCUMENTS_CONF['max_image_height'] = 3000;
}
if (!isset($_DOCUMENTS_CONF['max_image_size']) || (int) $_DOCUMENTS_CONF['max_image_size'] <= 0) {
    $_DOCUMENTS_CONF['max_image_size'] = 4194304;
}
if (!isset($_DOCUMENTS_CONF['whatsnew_enabled'])) {
    $_DOCUMENTS_CONF['whatsnew_enabled'] = 1;
}
if (!isset($_DOCUMENTS_CONF['whatsnew_interval']) || (int) $_DOCUMENTS_CONF['whatsnew_interval'] <= 0) {
    $_DOCUMENTS_CONF['whatsnew_interval'] = 1209600;
}
if (!isset($_DOCUMENTS_CONF['whatsnew_limit']) || (int) $_DOCUMENTS_CONF['whatsnew_limit'] <= 0) {
    $_DOCUMENTS_CONF['whatsnew_limit'] = 10;
}
if (!isset($_DOCUMENTS_CONF['stats_visibility'])) {
    $_DOCUMENTS_CONF['stats_visibility'] = 1;
}

$_DOCUMENTS_CONF['date'] = 'd.m.Y';
$_DOCUMENTS_CONF['db_date'] = '%d %m %Y';
$_DOCUMENTS_CONF['album_date'] = 'd.m.Y';

$_TABLES['documents_cat'] = $_DB_table_prefix . 'documents_cat';
$_TABLES['documents_docs'] = $_DB_table_prefix . 'documents_docs';
$_TABLES['documents_fields'] = $_DB_table_prefix . 'documents_fields';
$_TABLES['documents_values'] = $_DB_table_prefix . 'documents_values';
$_TABLES['documents_selects'] = $_DB_table_prefix . 'documents_selects';
$_TABLES['documents_selects_group'] = $_DB_table_prefix . 'documents_selects_group';
$_TABLES['documents_pics'] = $_DB_table_prefix . 'documents_pics';

function plugin_getmenuitems_documents()
{
    global $_CONF, $LANG_DOCUMENTS_1, $_DOCUMENTS_CONF;

    $menuitems = array();
    $menuitems[$LANG_DOCUMENTS_1['plugin_name']] = $_CONF['site_url'] . '/'
        . $_DOCUMENTS_CONF['documents_folder'] . '/';

    return $menuitems;
}

function plugin_whatsnewsupported_documents()
{
    global $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $LANG_WHATSNEW;

    if (empty($_DOCUMENTS_CONF['whatsnew_enabled'])) {
        return false;
    }

    $title = isset($LANG_DOCUMENTS_1['whatsnew_title'])
        ? $LANG_DOCUMENTS_1['whatsnew_title'] : $LANG_DOCUMENTS_1['plugin_name'];
    $interval = (int) $_DOCUMENTS_CONF['whatsnew_interval'];
    $byline = isset($LANG_WHATSNEW['new_last'])
        ? COM_formatTimeString($LANG_WHATSNEW['new_last'], $interval) : '';

    return array($title, $byline);
}

function plugin_getwhatsnew_documents()
{
    global $_TABLES, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1;

    if (empty($_DOCUMENTS_CONF['whatsnew_enabled'])) {
        return '';
    }

    $interval = max(60, (int) $_DOCUMENTS_CONF['whatsnew_interval']);
    $limit = max(1, min(50, (int) $_DOCUMENTS_CONF['whatsnew_limit']));
    $cutoff = date('Y-m-d H:i:s', time() - $interval);
    $cutoffSql = DB_escapeString($cutoff);

    $sql = "SELECT d.doc_url, c.cat_url, v.v_value AS title, "
        . "COALESCE(d.modified,d.created) AS changed_at "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v ON d.doc_url=v.doc_url "
        . "AND v.vid=(SELECT MIN(vt.vid) FROM {$_TABLES['documents_values']} AS vt "
        . "WHERE vt.doc_url=d.doc_url) "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.active=1 AND COALESCE(d.modified,d.created)>='{$cutoffSql}'"
        . COM_getPermSQL('AND', 0, 2, 'd')
        . " ORDER BY COALESCE(d.modified,d.created) DESC";

    $result = DB_query($sql);
    $items = array();
    while (count($items) < $limit && ($row = DB_fetchArray($result))) {
        if (!is_array($row) || empty($row['doc_url']) || empty($row['cat_url'])) {
            continue;
        }
        $title = trim(stripslashes(isset($row['title']) ? $row['title'] : ''));
        if ($title === '') {
            $title = $row['doc_url'];
        }
        $url = rtrim($_DOCUMENTS_CONF['site_url'], '/') . '/'
            . $row['cat_url'] . '/' . $row['doc_url'];
        $items[] = COM_createLink(
            COM_truncate($title, 60, '...'),
            COM_buildUrl($url),
            array('title' => $title)
        ) . LB;
    }

    if (empty($items)) {
        return isset($LANG_DOCUMENTS_1['whatsnew_none'])
            ? $LANG_DOCUMENTS_1['whatsnew_none'] . '<br' . XHTML . '>' : '';
    }

    if (function_exists('PLG_getThemeItem')) {
        return COM_makeList($items, PLG_getThemeItem('core-css-list-new', 'core'));
    }

    return COM_makeList($items);
}

function plugin_searchtypes_documents()
{
    global $LANG_DOCUMENTS_1;

    return array('documents' => $LANG_DOCUMENTS_1['plugin_name']);
}

function plugin_dopluginsearch_documents($query, $datestart, $dateend, $topic, $type, $author, $keyType, $page, $perpage)
{
    global $_TABLES, $LANG_DOCUMENTS_1, $_DOCUMENTS_CONF;

    $query = trim(DB_escapeString($query));

    $descriptionSql = "(SELECT vd.v_value FROM {$_TABLES['documents_values']} AS vd "
        . "INNER JOIN {$_TABLES['documents_fields']} AS fd ON fd.fid=vd.field_id "
        . "WHERE vd.doc_url=d.doc_url AND fd.f_type='text' AND vd.vid<>v.vid "
        . "AND vd.v_value<>'' ORDER BY fd.f_order ASC, vd.vid ASC LIMIT 1)";

    $sql = "SELECT DISTINCT d.doc_url AS id, v.v_value AS title, "
        . $descriptionSql . " AS description, UNIX_TIMESTAMP(d.created) AS date, "
        . "d.owner_id AS uid, d.hits, CONCAT('/" . $_DOCUMENTS_CONF['documents_folder']
        . "/', CONCAT(c.cat_url, CONCAT('/', d.doc_url))) AS url "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v ON d.doc_url=v.doc_url "
        . "AND v.vid=(SELECT MIN(vt.vid) FROM {$_TABLES['documents_values']} AS vt "
        . "WHERE vt.doc_url=d.doc_url) "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON f.cat_id=c.cid "
        . "WHERE d.active=1";

    $sql .= COM_getPermSQL('AND', 0, 2, 'd') . ' ';

    if (!empty($datestart) && !empty($dateend)) {
        $delim = substr($datestart, 4, 1);
        if (!empty($delim)) {
            $DS = explode($delim, $datestart);
            $DE = explode($delim, $dateend);
            if (count($DS) >= 3 && count($DE) >= 3) {
                $startdate = mktime(0, 0, 0, $DS[1], $DS[2], $DS[0]);
                $enddate = mktime(23, 59, 59, $DE[1], $DE[2], $DE[0]);
                $sql .= "AND (UNIX_TIMESTAMP(d.created) BETWEEN '"
                    . (int) $startdate . "' AND '" . (int) $enddate . "') ";
            }
        }
    }

    if (!empty($author)) {
        $sql .= 'AND (d.owner_id = ' . (int) $author . ') ';
    }

    $search = new SearchCriteria('documents', $LANG_DOCUMENTS_1['plugin_name']);
    $columns = array('d.doc_url', 'v.v_value', $descriptionSql);
    list($sql, $ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);
    $search->setSQL($sql);
    $search->setFTSQL($ftsql);
    $search->setRank(3);

    return $search;
}

function plugin_cclabel_documents()
{
    global $_CONF, $LANG_DOCUMENTS_1;

    if (!SEC_hasRights('documents.admin')) {
        return array();
    }

    return array(
        $LANG_DOCUMENTS_1['plugin_name'],
        $_CONF['site_admin_url'] . '/plugins/documents/index.php',
        plugin_geticon_documents()
    );
}

function plugin_getadminoption_documents()
{
    global $_CONF, $LANG_DOCUMENTS_1;

    if (SEC_hasRights('documents.admin')) {
        return array(
            $LANG_DOCUMENTS_1['plugin_name'],
            $_CONF['site_admin_url'] . '/plugins/documents/index.php',
            0
        );
    }

    return null;
}

function plugin_geticon_documents()
{
    global $_CONF;

    return $_CONF['site_admin_url'] . '/plugins/documents/images/documents.png';
}

function plugin_chkVersion_documents()
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/documents/autoinstall.php';
    $inst_parms = plugin_autoinstall_documents('documents');

    return $inst_parms['info']['pi_version'];
}

function plugin_upgrade_documents()
{
    global $_CONF, $_TABLES;

    $installed_version = DB_getItem($_TABLES['plugins'], 'pi_version', "pi_name = 'documents'");
    $code_version = plugin_chkVersion_documents();

    if ($installed_version == $code_version) {
        return true;
    }

    require_once $_CONF['path'] . 'plugins/documents/autoinstall.php';
    if (!plugin_compatible_with_this_version_documents('documents')) {
        return 3002;
    }

    switch ($installed_version) {
        case '1.0':
            DB_query("ALTER TABLE {$_TABLES['documents_cat']} ADD map INT AFTER css");
        case '1.0.0':
            DB_query("ALTER TABLE {$_TABLES['documents_cat']} CHANGE COLUMN map map INT AFTER css");
        case '1.0.1':
        case '1.1.0':
        default:
            break;
    }

    if (!DOCUMENTS_runUpgradeSteps($installed_version)) {
        COM_errorLog('Documents upgrade failed while upgrading from version '
            . $installed_version . ' to ' . $code_version);
        return false;
    }

    $inst_parms = plugin_autoinstall_documents('documents');
    $pi_gl_version = $inst_parms['info']['pi_gl_version'];
    DB_query("UPDATE {$_TABLES['plugins']} SET pi_version = '"
        . DB_escapeString($code_version) . "', pi_gl_version = '"
        . DB_escapeString($pi_gl_version) . "' WHERE pi_name = 'documents'");

    return true;
}

function plugin_autouninstall_documents()
{
    return array(
        'tables' => array(
            'documents_cat', 'documents_docs', 'documents_fields', 'documents_values',
            'documents_selects', 'documents_selects_group', 'documents_pics'
        ),
        'groups' => array('Documents Admin'),
        'features' => array('documents.admin', 'documents.publish'),
        'php_blocks' => array(),
        'vars' => array()
    );
}

function plugin_displaycomment_documents($id, $cid, $title, $order, $format, $page, $view)
{
    global $_TABLES, $LANG_ACCESS, $LANG_STATIC;

    $retval = '';
    $safeId = DB_escapeString($id);

    if (!defined('DOC_URL')) {
        define('DOC_URL', $id);
    }

    $sql = "SELECT c.cat_url FROM {$_TABLES['documents_values']} AS v "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid = v.field_id "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid = f.cat_id "
        . "LEFT JOIN {$_TABLES['documents_docs']} AS d ON d.doc_url = v.doc_url "
        . "WHERE v.doc_url = '{$safeId}' LIMIT 1";
    $res = DB_query($sql);
    $cat = DB_fetchArray($res);

    if (!defined('CAT_URL')) {
        define('CAT_URL', isset($cat['cat_url']) ? $cat['cat_url'] : '');
    }

    $sql = "SELECT COUNT(*) AS count, owner_id, group_id, perm_owner, "
        . "perm_group, perm_members, perm_anon FROM {$_TABLES['documents_docs']} "
        . "WHERE (doc_url = '{$safeId}') AND (active = 1)"
        . COM_getPermSQL('AND') . ' GROUP BY doc_url';
    $result = DB_query($sql);
    $A = DB_fetchArray($result);

    if (!is_array($A) || empty($A['count'])) {
        return COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
            . $LANG_STATIC['deny_msg']
            . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    }

    $A['commentcode'] = 0;
    $comment_page = 1;
    $delete_option = SEC_hasRights('documents.admin')
        && SEC_hasAccess(
            $A['owner_id'], $A['group_id'], $A['perm_owner'], $A['perm_group'],
            $A['perm_members'], $A['perm_anon']
        ) == 3;

    $retval .= CMT_userComments(
        $id, $title, 'documents', $order, $format, $cid,
        $comment_page, $view, $delete_option, $A['commentcode']
    );

    return $retval;
}

function plugin_savecomment_documents($title, $comment, $id, $pid, $postmode)
{
    global $_CONF, $_TABLES, $LANG03, $_DOCUMENTS_CONF;

    $retval = '';
    $safeId = DB_escapeString($id);

    $docResult = DB_query(
        "SELECT active, owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
        . "FROM {$_TABLES['documents_docs']} WHERE doc_url = '{$safeId}' LIMIT 1"
    );
    $doc = DB_fetchArray($docResult);
    $canComment = is_array($doc)
        && isset($doc['active'])
        && (int) $doc['active'] === 1
        && SEC_hasAccess(
            (int) $doc['owner_id'], (int) $doc['group_id'],
            (int) $doc['perm_owner'], (int) $doc['perm_group'],
            (int) $doc['perm_members'], (int) $doc['perm_anon']
        ) >= 2;

    if (!$canComment) {
        return COM_refresh($_CONF['site_url'] . '/index.php');
    }

    $sql = "SELECT c.cat_url FROM {$_TABLES['documents_values']} AS v "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid = v.field_id "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid = f.cat_id "
        . "WHERE v.doc_url = '{$safeId}' LIMIT 1";
    $res = DB_query($sql);
    $A = DB_fetchArray($res);
    $catUrl = is_array($A) && isset($A['cat_url']) ? $A['cat_url'] : '';

    $ret = CMT_saveComment($title, $comment, $id, $pid, 'documents', $postmode);
    if ($ret > 0) {
        $retval .= COM_siteHeader('menu', $LANG03[1])
            . CMT_commentForm($title, $comment, $id, $pid, 'documents', $LANG03[14], $postmode)
            . COM_siteFooter();
    } else {
        $cid = DB_insertId();
        $url = $url2 = COM_buildUrl($_DOCUMENTS_CONF['site_url'] . '/' . $catUrl . '/' . $id);

        if (($_CONF['commentsubmission'] == 1) && !SEC_hasRights('comment.submit')) {
            $url .= ($_CONF['url_rewrite'] ? '?' : '&') . 'msg=15';
        } else {
            $uid = (int) DB_getItem($_TABLES['documents_docs'], 'owner_id', "doc_url = '{$safeId}'");
            $username = DB_getItem($_TABLES['comments'], 'name', 'cid = ' . (int) $cid);
            DOCUMENTS_sendNotification($title, $comment, $uid, $username, $url2);
        }

        $retval = COM_refresh($url);
    }

    return $retval;
}

function plugin_deletecomment_documents($cid, $id)
{
    global $_CONF, $_TABLES, $_USER, $_DOCUMENTS_CONF;

    $retval = '';
    $safeId = DB_escapeString($id);
    $result = DB_query(
        "SELECT owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
        . "FROM {$_TABLES['documents_docs']} WHERE doc_url = '{$safeId}'"
    );
    $A = DB_fetchArray($result);

    $allowed = is_array($A)
        && SEC_hasRights('documents.admin')
        && SEC_hasAccess(
            $A['owner_id'], $A['group_id'], $A['perm_owner'], $A['perm_group'],
            $A['perm_members'], $A['perm_anon']
        ) == 3;

    if ($allowed) {
        CMT_deleteComment((int) $cid, $id, 'documents');
        $catUrl = defined('CAT_URL') ? CAT_URL : '';
        $retval .= COM_refresh(COM_buildUrl($_DOCUMENTS_CONF['site_url'] . '/' . $catUrl . '/' . $id));
    } else {
        $username = isset($_USER['username']) ? $_USER['username'] : 'unknown';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        COM_errorLog("User {$username} (IP: {$ip}) tried to illegally delete comment "
            . (int) $cid . " from documents {$id}");
        $retval .= COM_refresh($_CONF['site_url'] . '/index.php');
    }

    return $retval;
}

function plugin_getcommenturlid_documents()
{
    global $_DOCUMENTS_CONF;

    $catUrl = defined('CAT_URL') ? CAT_URL : '';
    $docUrl = defined('DOC_URL') ? DOC_URL : '';

    return array($_DOCUMENTS_CONF['site_url'] . '/' . $catUrl . '/' . $docUrl, '');
}

function DOCUMENTS_filterVars($vars, &$arr)
{
    foreach ($vars as $var => $type) {
        if (!isset($arr[$var])) {
            $arr[$var] = '';
        }

        switch ($type) {
            case 'number':
                $arr[$var] = COM_applyFilter($arr[$var], true);
                break;
            case 'html':
                $arr[$var] = COM_checkHTML($arr[$var], 'documents.admin');
                break;
            case 'text':
                $arr[$var] = htmlspecialchars(
                    strip_tags(COM_stripslashes($arr[$var])), ENT_QUOTES, 'UTF-8'
                );
                break;
            case 'alpha':
            default:
                $arr[$var] = COM_applyFilter($arr[$var], false);
                break;
        }
    }
}

function DOCUMENTS_menuCat()
{
    global $_TABLES, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1;

    $result = DB_query("SELECT * FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC");
    $retval = '<ul>';

    while ($A = DB_fetchArray($result)) {
        $retval .= '<li><a href="' . $_DOCUMENTS_CONF['site_url'] . '/'
            . htmlspecialchars($A['cat_url'], ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($A['cat_name'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }

    $retval .= '<li><a href="' . $_DOCUMENTS_CONF['site_url'] . '">'
        . $LANG_DOCUMENTS_1['see_all_docs'] . '</a></li></ul>';

    return $retval;
}

function MAPS_ReadMore_documents()
{
    global $LANG_DOCUMENTS_1;
    return $LANG_DOCUMENTS_1['read_more_marker'];
}

function DOCUMENTS_sendNotification($title, $comment, $uid, $username, $url)
{
    global $_CONF, $LANG03, $LANG08, $LANG_DOCUMENTS_1;

    $title = stripslashes($title);
    $comment = stripslashes($comment);

    if (preg_match('/<.*>/', $comment) != 0) {
        $comment = strip_tags($comment);
    }

    $uid = (int) $uid;
    if ($uid < 1) {
        $uid = 1;
    }

    if (($uid == 1) && !empty($username)) {
        $author = $username;
    } else {
        $author = COM_getDisplayName($uid);
    }

    $mailbody = $LANG03[16] . ': ' . $title . "\n" . $LANG03[5] . ': ' . $author . "\n";

    if ($_CONF['emailstorieslength'] > 0) {
        if ($_CONF['emailstorieslength'] > 1) {
            $comment = MBYTE_substr($comment, 0, $_CONF['emailstorieslength']) . '...';
        }
        $mailbody .= $comment . "\n\n";
    }

    $mailsubject = $LANG_DOCUMENTS_1['new_comment'] . ' ' . $_CONF['site_name'];
    $mailbody .= $LANG03[39] . ' <' . $url . ">\n\n";
    $mailbody .= "\n------------------------------\n";
    $mailbody .= "\n" . $LANG08[34] . "\n";
    $mailbody .= "\n------------------------------\n";

    return COM_mail($_CONF['site_mail'], $mailsubject, $mailbody);
}

?>