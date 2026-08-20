<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.1                                                    |
// +---------------------------------------------------------------------------+
// | functions.inc                                                             |
// |                                                                           |
// | This file does two things: 1) it implements the necessary Geeklog Plugin  |
// | API methods and 2) implements all the common code needed by this plugin.  |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+

/**
* @package Documents
*/

if (strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die('This file can not be used on its own.');
}

/**
 * Language file include
 */
$plugin_path = $_CONF['path'] . 'plugins/documents/';
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}

/**
 * Check and see if we need to load the plugin configuration.
 */
if (!isset($_DOCUMENTS_CONF)) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';

    $documents_config = config::get_instance();
    $_DOCUMENTS_CONF = $documents_config->get_config('documents');
}

/* Documents path configuration. */
$_DOCUMENTS_CONF['path_html'] = $_CONF['path_html'] . $_DOCUMENTS_CONF['documents_folder'] . '/';
$_DOCUMENTS_CONF['site_url'] = $_CONF['site_url'] . '/' . $_DOCUMENTS_CONF['documents_folder'];
$_DOCUMENTS_CONF['path_images'] = $_CONF['path_images'] . 'documents/items/';
$_DOCUMENTS_CONF['path_cat_images'] = $_CONF['path_images'] . 'documents/categories/';
$_DOCUMENTS_CONF['images_url'] = $_CONF['site_url'] . '/' . substr($_CONF['path_images'], strlen($_CONF['path_html']), -1) . '/documents/items/';
$_DOCUMENTS_CONF['images_cat_url'] = $_CONF['site_url'] . '/' . substr($_CONF['path_images'], strlen($_CONF['path_html']), -1) . '/documents/categories/';

/*
 * Persistent plugin data must not live in a cache-style data directory.
 * New writes will target site-documents. During the stabilization series the
 * legacy path is retained explicitly so the upgrade code can migrate existing
 * custom templates/data safely and idempotently.
 */
$_DOCUMENTS_CONF['path_data'] = rtrim($_CONF['path_data'], '/\\') . DIRECTORY_SEPARATOR . 'site-documents' . DIRECTORY_SEPARATOR;
$_DOCUMENTS_CONF['legacy_path_data'] = rtrim($_CONF['path_data'], '/\\') . DIRECTORY_SEPARATOR . 'data_documents' . DIRECTORY_SEPARATOR;

$_DOCUMENTS_CONF['max_image_width'] = 3000;
$_DOCUMENTS_CONF['max_image_height'] = 3000;
$_DOCUMENTS_CONF['max_image_size'] = 4194304; // 4 MB
$_DOCUMENTS_CONF['date'] = 'd.m.Y';
$_DOCUMENTS_CONF['db_date'] = '%d %m %Y';
$_DOCUMENTS_CONF['album_date'] = 'd.m.Y';

/**
 * Documents plugin table(s)
 */
$_TABLES['documents_cat'] = $_DB_table_prefix . 'documents_cat';
$_TABLES['documents_docs'] = $_DB_table_prefix . 'documents_docs';
$_TABLES['documents_fields'] = $_DB_table_prefix . 'documents_fields';
$_TABLES['documents_values'] = $_DB_table_prefix . 'documents_values';
$_TABLES['documents_selects'] = $_DB_table_prefix . 'documents_selects';
$_TABLES['documents_selects_group'] = $_DB_table_prefix . 'documents_selects_group';
$_TABLES['documents_pics'] = $_DB_table_prefix . 'documents_pics';

/**
 * Return whether another Geeklog plugin is currently active.
 *
 * @param string $plugin Plugin name
 * @return bool
 */
function DOCUMENTS_isPluginActive($plugin)
{
    global $_PLUGINS;

    return is_array($_PLUGINS) && in_array($plugin, $_PLUGINS, true);
}

/**
 * Return whether the optional Maps integration may be used.
 *
 * No Maps code should be loaded or rendered when this returns false.
 *
 * @return bool
 */
function DOCUMENTS_hasMaps()
{
    global $_CONF;

    if (!DOCUMENTS_isPluginActive('maps')) {
        return false;
    }

    return is_dir($_CONF['path'] . 'plugins/maps');
}

/**
 * Return whether the optional MediaGallery integration may be used.
 *
 * No MediaGallery code should be loaded or rendered when this returns false.
 *
 * @return bool
 */
function DOCUMENTS_hasMediaGallery()
{
    global $_CONF;

    if (!DOCUMENTS_isPluginActive('mediagallery')) {
        return false;
    }

    return is_dir($_CONF['path'] . 'plugins/mediagallery');
}

// +---------------------------------------------------------------------------+
// | Geeklog Plugin API Implementation                                         |
// +---------------------------------------------------------------------------+

function plugin_getmenuitems_documents()
{
    global $_CONF, $LANG_DOCUMENTS_1, $_DOCUMENTS_CONF;

    $url = $_CONF['site_url'] . '/' . $_DOCUMENTS_CONF['documents_folder'] . '/';
    $menuitems = array();
    $menuitems[$LANG_DOCUMENTS_1['plugin_name']] = $url;

    return $menuitems;
}

function plugin_searchtypes_documents()
{
    global $LANG_DOCUMENTS_1;

    $tmp = array();
    $tmp['documents'] = $LANG_DOCUMENTS_1['plugin_name'];

    return $tmp;
}

function plugin_dopluginsearch_documents($query, $datestart, $dateend, $topic, $type, $author, $keyType, $page, $perpage)
{
    global $_TABLES, $LANG_DOCUMENTS_1, $_DOCUMENTS_CONF;

    $query = trim(addslashes($query));

    $sql = "SELECT distinct(d.doc_url) AS id, v.v_value AS title, UNIX_TIMESTAMP(d.created) AS date, d.owner_id AS uid, d.hits, ";
    $sql .= "CONCAT('/" . $_DOCUMENTS_CONF['documents_folder'] . "/', CONCAT(c.cat_url, CONCAT('/', d.doc_url))) AS url ";
    $sql .= " FROM {$_TABLES['documents_docs']} AS d
            LEFT JOIN {$_TABLES['documents_values']} AS v
              ON d.doc_url = v.doc_url AND v.vid = (SELECT MIN(vid) FROM {$_TABLES['documents_values']} WHERE doc_url = d.doc_url)
            LEFT JOIN {$_TABLES['documents_fields']} AS f
              ON f.fid = v.field_id
            LEFT JOIN {$_TABLES['documents_cat']} AS c
              ON f.cat_id = c.cid
            WHERE d.active = 1";

    $sql .= COM_getPermSQL('AND', 0, 2, 'd') . ' ';

    if (!empty($datestart) && !empty($dateend)) {
        $delim = substr($datestart, 4, 1);
        if (!empty($delim)) {
            $DS = explode($delim, $datestart);
            $DE = explode($delim, $dateend);
            $startdate = mktime(0, 0, 0, $DS[1], $DS[2], $DS[0]);
            $enddate = mktime(23, 59, 59, $DE[1], $DE[2], $DE[0]);
            $sql .= "AND (UNIX_TIMESTAMP(d.created) BETWEEN '$startdate' AND '$enddate') ";
        }
    }
    if (!empty($author)) {
        $sql .= "AND (d.owner_id = '$author') ";
    }

    $search = new SearchCriteria('documents', $LANG_DOCUMENTS_1['plugin_name']);
    $columns = array('d.doc_url', 'v.v_value');
    list($sql, $ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);
    $search->setSQL($sql);
    $search->setFTSQL($ftsql);
    $search->setRank(3);

    return $search;
}

/*
function plugin_autotags_documents($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    if ($op == 'tagname') {
        return 'documents';
    } elseif ($op == 'parse') {
        $did = COM_applyFilter($autotag['parm1']);
        if (!empty($did)) {
            $url = $_CONF['site_url'] . '/documents/index.php?item=' . $did;
            if (empty($autotag['parm2'])) {
                $linktext = 'Documents';
            } else {
                $linktext = $autotag['parm2'];
            }
            $link = COM_createLink($linktext, $url);
            $content = str_replace($autotag['tagstr'], $link, $content);
        }

        return $content;
    }
}
*/

function plugin_cclabel_documents()
{
    global $_CONF, $LANG_DOCUMENTS_1;

    $retval = array();
    if (SEC_hasRights('documents.admin')) {
        $retval = array(
            $LANG_DOCUMENTS_1['plugin_name'],
            $_CONF['site_admin_url'] . '/plugins/documents/index.php',
            plugin_geticon_documents()
        );
    }

    return $retval;
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

    $installed_version = DB_getItem(
        $_TABLES['plugins'],
        'pi_version',
        "pi_name = 'documents'"
    );
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
            // Fall through to the following historical migration.
        case '1.0.0':
            DB_query("ALTER TABLE {$_TABLES['documents_cat']} CHANGE COLUMN map map INT AFTER css");
            // Fall through.
        case '1.0.1':
        case '1.1.0':
        default:
            $inst_parms = plugin_autoinstall_documents('documents');
            $pi_gl_version = $inst_parms['info']['pi_gl_version'];
            DB_query(
                "UPDATE {$_TABLES['plugins']} SET pi_version = '" .
                DB_escapeString($code_version) . "', pi_gl_version = '" .
                DB_escapeString($pi_gl_version) . "' WHERE pi_name = 'documents'"
            );
            break;
    }

    return true;
}

function plugin_autouninstall_documents()
{
    $out = array(
        'tables' => array(
            'documents_cat',
            'documents_docs',
            'documents_fields',
            'documents_values',
            'documents_selects',
            'documents_selects_group',
            'documents_pics'
        ),
        'groups' => array('Documents Admin'),
        'features' => array('documents.admin', 'documents.publish'),
        'php_blocks' => array(),
        'vars' => array()
    );

    return $out;
}

function plugin_displaycomment_documents($id, $cid, $title, $order, $format, $page, $view)
{
    global $_TABLES, $LANG_ACCESS, $LANG_STATIC;

    $retval = '';

    if (!defined('DOC_URL')) {
        define('DOC_URL', $id);
    }

    $sql = "SELECT c.cat_url
            FROM {$_TABLES['documents_values']} AS v
            LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid = v.field_id
            LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid = f.cat_id
            LEFT JOIN {$_TABLES['documents_selects']} AS s ON s.s_name = v.v_value
            LEFT JOIN {$_TABLES['documents_docs']} AS d ON d.doc_url = v.doc_url
            WHERE v.doc_url = '{$id}' LIMIT 1";

    $res = DB_query($sql);
    $A = DB_fetchArray($res);

    if (!defined('CAT_URL')) {
        define('CAT_URL', $A['cat_url']);
    }

    $sql = "SELECT COUNT(*) AS count, owner_id, group_id, perm_owner, "
        . "perm_group, perm_members, perm_anon "
        . "FROM {$_TABLES['documents_docs']} "
        . "WHERE (doc_url = '$id') AND (active = 1)" . COM_getPermSQL('AND')
        . ' GROUP BY doc_url';
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    $allowed = $A['count'];
    $A['commentcode'] = 0;
    $comment_page = 1;
    $delete_option = (
        SEC_hasRights('documents.admin') &&
        SEC_hasAccess(
            $A['owner_id'],
            $A['group_id'],
            $A['perm_owner'],
            $A['perm_group'],
            $A['perm_members'],
            $A['perm_anon']
        ) == 3
    );

    if ($allowed == 1) {
        $delete_option = (
            SEC_hasRights('documents.edit') &&
            SEC_hasAccess(
                $A['owner_id'],
                $A['group_id'],
                $A['perm_owner'],
                $A['perm_group'],
                $A['perm_members'],
                $A['perm_anon']
            ) == 3
        );
        $retval .= CMT_userComments(
            $id,
            $title,
            'documents',
            $order,
            $format,
            $cid,
            $comment_page,
            $view,
            $delete_option,
            $A['commentcode']
        );
    } else {
        $retval .= COM_startBlock(
            $LANG_ACCESS['accessdenied'],
            '',
            COM_getBlockTemplate('_msg_block', 'header')
        )
            . $LANG_STATIC['deny_msg']
            . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    }

    return $retval;
}

function plugin_savecomment_documents($title, $comment, $id, $pid, $postmode)
{
    global $_CONF, $_TABLES, $LANG03, $_DOCUMENTS_CONF;

    $retval = '';

    $sql = "SELECT c.cat_url
            FROM {$_TABLES['documents_values']} AS v
            LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid = v.field_id
            LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid = f.cat_id
            LEFT JOIN {$_TABLES['documents_selects']} AS s ON s.s_name = v.v_value
            LEFT JOIN {$_TABLES['documents_docs']} AS d ON d.doc_url = v.doc_url
            WHERE v.doc_url = '{$id}' LIMIT 1";

    $res = DB_query($sql);
    $A = DB_fetchArray($res);

    $ret = CMT_saveComment($title, $comment, $id, $pid, 'documents', $postmode);
    if ($ret > 0) {
        $retval .= COM_siteHeader('menu', $LANG03[1])
            . CMT_commentForm(
                $title,
                $comment,
                $id,
                $pid,
                'documents',
                $LANG03[14],
                $postmode
            )
            . COM_siteFooter();
    } else {
        $cid = DB_insertId();
        $url = $url2 = COM_buildUrl(
            $_DOCUMENTS_CONF['site_url'] . '/' . $A['cat_url'] . '/' . $id
        );
        if (($_CONF['commentsubmission'] == 1) && !SEC_hasRights('comment.submit')) {
            $url .= ($_CONF['url_rewrite'] ? '?' : '&') . 'msg=15';
        } else {
            $uid = DB_getItem($_TABLES['documents_docs'], 'owner_id', "doc_url = '$id'");
            $username = DB_getItem($_TABLES['comments'], 'name', "cid = '$cid'");
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

    $has_editPermissions = SEC_hasRights('documents.admin');
    $result = DB_query(
        "SELECT owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon " .
        "FROM {$_TABLES['documents_docs']} WHERE doc_url = '{$id}'"
    );
    $A = DB_fetchArray($result);

    if (
        $has_editPermissions &&
        SEC_hasAccess(
            $A['owner_id'],
            $A['group_id'],
            $A['perm_owner'],
            $A['perm_group'],
            $A['perm_members'],
            $A['perm_anon']
        ) == 3
    ) {
        CMT_deleteComment($cid, $id, 'documents');
        $url = COM_buildUrl($_DOCUMENTS_CONF['site_url'] . '/' . CAT_URL . '/' . $id);
        $retval .= COM_refresh($url);
    } else {
        COM_errorLog(
            "User {$_USER['username']} (IP: {$_SERVER['REMOTE_ADDR']}) " .
            "tried to illegally delete comment $cid from documents $id"
        );
        $retval .= COM_refresh($_CONF['site_url'] . '/index.php');
    }

    return $retval;
}

function plugin_getcommenturlid_documents()
{
    global $_DOCUMENTS_CONF;

    $retval = array();
    $retval[0] = $_DOCUMENTS_CONF['site_url'] . '/' . CAT_URL . '/' . DOC_URL;
    $retval[1] = '';

    return $retval;
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
                    strip_tags(COM_stripslashes($arr[$var])),
                    ENT_QUOTES,
                    'UTF-8'
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

    $sql = "SELECT * FROM {$_TABLES['documents_cat']} WHERE 1=1 ORDER BY cat_order ASC";
    $result = DB_query($sql);

    $retval = '<ul>';
    while ($A = DB_fetchArray($result)) {
        $retval .= '<li><a href="' . $_DOCUMENTS_CONF['site_url'] . '/' .
            $A['cat_url'] . '">' . $A['cat_name'] . '</a></li>';
    }
    $retval .= '<li><a href="' . $_DOCUMENTS_CONF['site_url'] . '">' .
        $LANG_DOCUMENTS_1['see_all_docs'] . '</a></li>';
    $retval .= '</ul>';

    return $retval;
}

function MAPS_ReadMore_documents()
{
    global $LANG_DOCUMENTS_1;

    return $LANG_DOCUMENTS_1['read_more_marker'];
}

function DOCUMENTS_sendNotification($title, $comment, $uid, $username, $url)
{
    global $_CONF, $_TABLES, $LANG01, $LANG03, $LANG08, $LANG09, $LANG29, $LANG_DOCUMENTS_1;

    $title = stripslashes($title);
    $comment = stripslashes($comment);

    if (preg_match('/<.*>/', $comment) != 0) {
        $comment = strip_tags($comment);
    }

    if ($uid < 1) {
        $uid = 1;
    }
    if (($uid == 1) && !empty($username)) {
        $author = $username;
    } else {
        $author = COM_getDisplayName($uid);
    }

    $mailbody = "$LANG03[16]: $title\n"
        . "$LANG03[5]: $author\n";

    if ($_CONF['emailstorieslength'] > 0) {
        if ($_CONF['emailstorieslength'] > 1) {
            $comment = MBYTE_substr($comment, 0, $_CONF['emailstorieslength']) . '...';
        }
        $mailbody .= $comment . "\n\n";
    }

    $mailsubject = $LANG_DOCUMENTS_1['new_comment'] . ' ' . $_CONF['site_name'];
    $mailbody .= $LANG03[39] . ' <' . $url . ">\n\n";
    $mailbody .= "\n------------------------------\n";
    $mailbody .= "\n$LANG08[34]\n";
    $mailbody .= "\n------------------------------\n";

    return COM_mail($_CONF['site_mail'], $mailsubject, $mailbody);
}
?>
