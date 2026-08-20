<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
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

/**
 * @package Documents
 */

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

/*
 * Always start with safe defaults. config::get_config() returns false when a
 * configuration group is not available in the current request, which must not
 * then be used as an array (notably on PHP 8.1).
 */
$documents_defaults = array(
    'documents_folder'      => 'documents',
    'documents_main_header' => '',
    'documents_main_footer' => '',
    'default_permissions'   => array(3, 3, 2, 2)
);

if (!isset($_DOCUMENTS_CONF) || !is_array($_DOCUMENTS_CONF)) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    $documents_config = config::get_instance();
    $documents_loaded_config = $documents_config->get_config('documents');

    if (!is_array($documents_loaded_config)) {
        $documents_loaded_config = array();
    }

    $_DOCUMENTS_CONF = array_merge($documents_defaults, $documents_loaded_config);
} else {
    $_DOCUMENTS_CONF = array_merge($documents_defaults, $_DOCUMENTS_CONF);
}

/**
 * Return the persistent Documents data directory for the current Geeklog site.
 *
 * The directory is derived from the site's own path_data so it is unique in a
 * multisite installation and remains valid in a normal single-site install.
 * Example: /path/data/ -> /path/data-documents/
 *
 * @return string
 */
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

/**
 * Return the legacy Documents data directory used before the multisite-safe
 * storage layout.
 *
 * @return string
 */
function DOCUMENTS_legacyDataDir()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return $base . DIRECTORY_SEPARATOR . 'data_documents' . DIRECTORY_SEPARATOR;
}

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
 * @return bool
 */
function DOCUMENTS_hasMaps()
{
    global $_CONF;

    return DOCUMENTS_isPluginActive('maps')
        && isset($_CONF['path'])
        && is_dir($_CONF['path'] . 'plugins/maps');
}

/**
 * Return whether the optional MediaGallery integration may be used.
 *
 * @return bool
 */
function DOCUMENTS_hasMediaGallery()
{
    global $_CONF;

    return DOCUMENTS_isPluginActive('mediagallery')
        && isset($_CONF['path'])
        && is_dir($_CONF['path'] . 'plugins/mediagallery');
}

/* Documents path configuration. */
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
$_DOCUMENTS_CONF['max_image_width'] = 3000;
$_DOCUMENTS_CONF['max_image_height'] = 3000;
$_DOCUMENTS_CONF['max_image_size'] = 4194304;
$_DOCUMENTS_CONF['date'] = 'd.m.Y';
$_DOCUMENTS_CONF['db_date'] = '%d %m %Y';
$_DOCUMENTS_CONF['album_date'] = 'd.m.Y';

/* Documents plugin tables. */
$_TABLES['documents_cat'] = $_DB_table_prefix . 'documents_cat';
$_TABLES['documents_docs'] = $_DB_table_prefix . 'documents_docs';
$_TABLES['documents_fields'] = $_DB_table_prefix . 'documents_fields';
$_TABLES['documents_values'] = $_DB_table_prefix . 'documents_values';
$_TABLES['documents_selects'] = $_DB_table_prefix . 'documents_selects';
$_TABLES['documents_selects_group'] = $_DB_table_prefix . 'documents_selects_group';
$_TABLES['documents_pics'] = $_DB_table_prefix . 'documents_pics';

// +---------------------------------------------------------------------------+
// | Geeklog Plugin API Implementation                                         |
// +---------------------------------------------------------------------------+

function plugin_getmenuitems_documents()
{
    global $_CONF, $LANG_DOCUMENTS_1, $_DOCUMENTS_CONF;

    $menuitems = array();
    $menuitems[$LANG_DOCUMENTS_1['plugin_name']] = $_CONF['site_url'] . '/'
        . $_DOCUMENTS_CONF['documents_folder'] . '/';

    return $menuitems;
}

function plugin_searchtypes_documents()
{
    global $LANG_DOCUMENTS_1;

    return array('documents' => $LANG_DOCUMENTS_1['plugin_name']);
}

function plugin_dopluginsearch_documents($query, $datestart, $dateend, $topic, $type, $author, $keyType, $page, $perpage)
{
    global $_TABLES, $LANG_DOCUMENTS_1, $_DOCUMENTS_CONF;

    $query = trim(addslashes($query));

    $sql = "SELECT DISTINCT(d.doc_url) AS id, v.v_value AS title, "
        . "UNIX_TIMESTAMP(d.created) AS date, d.owner_id AS uid, d.hits, "
        . "CONCAT('/" . $_DOCUMENTS_CONF['documents_folder']
        . "/', CONCAT(c.cat_url, CONCAT('/', d.doc_url))) AS url "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v "
        . "ON d.doc_url = v.doc_url AND v.vid = (SELECT MIN(vid) "
        . "FROM {$_TABLES['documents_values']} WHERE doc_url = d.doc_url) "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid = v.field_id "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON f.cat_id = c.cid "
        . "WHERE d.active = 1";

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
    $columns = array('d.doc_url', 'v.v_value');
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
