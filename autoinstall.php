<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | autoinstall.php                                                           |
// |                                                                           |
// | This file provides helper functions for the automatic plugin install.     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// +---------------------------------------------------------------------------+

/** @package Documents */

define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1');
define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3');
define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0');
define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0');

function DOCUMENTS_runStorageMigration()
{
    global $_CONF;

    if (!function_exists('DOCUMENTS_dataDir') || !function_exists('DOCUMENTS_legacyDataDir')) {
        require_once $_CONF['path'] . 'plugins/documents/functions.inc';
    }

    require_once $_CONF['path'] . 'plugins/documents/storage.php';
    $migration = DOCUMENTS_migrateLegacyData();

    if (!empty($migration['errors'])) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents storage migration completed with '
                . (int) $migration['errors'] . ' error(s).');
        }
        return false;
    }

    return true;
}

function DOCUMENTS_runUpgradeSteps($installedVersion)
{
    global $_CONF;

    $installedVersion = (string) $installedVersion;

    if (version_compare($installedVersion, '1.1.1', '<')) {
        require_once $_CONF['path'] . 'plugins/documents/rewrite.php';
        if (!DOCUMENTS_writeHtaccess(true) || !DOCUMENTS_runStorageMigration()) {
            return false;
        }
    }

    if (version_compare($installedVersion, '1.1.2', '<')) {
        require_once $_CONF['path'] . 'plugins/documents/rewrite.php';
        if (!DOCUMENTS_writeHtaccess(true) || !DOCUMENTS_runStorageMigration()) {
            return false;
        }
    }

    if (version_compare($installedVersion, '1.1.7', '<')) {
        if (!DOCUMENTS_runStorageMigration()) {
            return false;
        }
    }

    if (version_compare($installedVersion, '1.1.8', '<')) {
        require_once $_CONF['path'] . 'plugins/documents/install_updates.php';
        if (!DOCUMENTS_updateConfig_1_1_8()) {
            return false;
        }
    }

    if (version_compare($installedVersion, '1.1.9', '<')) {
        require_once $_CONF['path'] . 'plugins/documents/install_updates.php';
        if (!DOCUMENTS_updateConfig_1_1_9()) {
            return false;
        }
    }

    return true;
}

function plugin_autoinstall_documents($pi_name)
{
    $pi_name = 'documents';
    $pi_display_name = 'Documents';
    $pi_admin = $pi_display_name . ' Admin';

    $info = array(
        'pi_name' => $pi_name,
        'pi_display_name' => $pi_display_name,
        'pi_version' => '1.1.9',
        'pi_gl_version' => DOCUMENTS_MIN_GEEKLOG_VERSION,
        'pi_homepage' => 'https://github.com/Geeklog-Plugins/documents'
    );

    $groups = array(
        $pi_admin => 'Users in this group can administer the ' . $pi_display_name . ' plugin'
    );

    $features = array(
        $pi_name . '.admin' => 'Full access to ' . $pi_display_name . ' plugin',
        $pi_name . '.publish' => 'Can publish ' . $pi_display_name . ' (skip submission queue)'
    );

    $mappings = array(
        $pi_name . '.admin' => array($pi_admin),
        $pi_name . '.publish' => array($pi_admin)
    );

    $tables = array(
        'documents_cat',
        'documents_docs',
        'documents_fields',
        'documents_values',
        'documents_selects',
        'documents_selects_group',
        'documents_pics'
    );

    return array(
        'info' => $info,
        'groups' => $groups,
        'features' => $features,
        'mappings' => $mappings,
        'tables' => $tables
    );
}

function plugin_load_configuration_documents($pi_name)
{
    global $_CONF;

    $base_path = $_CONF['path'] . 'plugins/' . $pi_name . '/';
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $base_path . 'install_defaults.php';

    return plugin_initconfig_documents();
}

function plugin_compatible_with_this_version_documents($pi_name)
{
    global $_CONF, $_DB_dbms;

    $dbFile = $_CONF['path'] . 'plugins/' . $pi_name . '/sql/' . $_DB_dbms . '_install.php';
    if (!file_exists($dbFile)) {
        return false;
    }

    if (version_compare(PHP_VERSION, DOCUMENTS_MIN_PHP_VERSION, '<')
        || version_compare(PHP_VERSION, DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE, '>=')) {
        return false;
    }

    if (defined('VERSION')) {
        if (version_compare(VERSION, DOCUMENTS_MIN_GEEKLOG_VERSION, '<')
            || version_compare(VERSION, DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE, '>=')) {
            return false;
        }
    }

    return true;
}

function plugin_postinstall_documents($pi_name)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/documents/rewrite.php';
    if (!DOCUMENTS_writeHtaccess(true)) {
        return false;
    }

    return DOCUMENTS_runStorageMigration();
}
