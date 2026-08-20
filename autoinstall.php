<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.1                                                    |
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
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// +---------------------------------------------------------------------------+

/**
 * @package Documents
 */

/**
 * Plugin autoinstall function.
 *
 * @param  string $pi_name Plugin name
 * @return array           Plugin information
 */
function plugin_autoinstall_documents($pi_name)
{
    $pi_name         = 'documents';
    $pi_display_name = 'Documents';
    $pi_admin        = $pi_display_name . ' Admin';

    $info = array(
        'pi_name'         => $pi_name,
        'pi_display_name' => $pi_display_name,
        'pi_version'      => '1.1.1',
        'pi_gl_version'   => '2.1.1',
        'pi_homepage'     => 'https://github.com/Geeklog-Plugins/documents'
    );

    $groups = array(
        $pi_admin => 'Users in this group can administer the '
                     . $pi_display_name . ' plugin'
    );

    $features = array(
        $pi_name . '.admin'   => 'Full access to ' . $pi_display_name
                                 . ' plugin',
        $pi_name . '.publish' => 'Can publish ' . $pi_display_name
                                 . ' (skip submission queue)'
    );

    $mappings = array(
        $pi_name . '.admin'   => array($pi_admin),
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
        'info'     => $info,
        'groups'   => $groups,
        'features' => $features,
        'mappings' => $mappings,
        'tables'   => $tables
    );
}

/**
 * Create the initial configuration for the plugin.
 *
 * @param  string $pi_name Plugin name
 * @return boolean
 */
function plugin_load_configuration_documents($pi_name)
{
    global $_CONF;

    $base_path = $_CONF['path'] . 'plugins/' . $pi_name . '/';

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $base_path . 'install_defaults.php';

    return plugin_initconfig_documents();
}

/**
 * Check whether this plugin supports the current Geeklog/PHP environment.
 *
 * Supported target:
 * - Geeklog 2.1.1 through 2.2.2
 * - PHP 5.6 through 8.1
 *
 * @param  string $pi_name Plugin name
 * @return boolean
 */
function plugin_compatible_with_this_version_documents($pi_name)
{
    global $_CONF, $_DB_dbms;

    $dbFile = $_CONF['path'] . 'plugins/' . $pi_name . '/sql/'
            . $_DB_dbms . '_install.php';
    if (!file_exists($dbFile)) {
        return false;
    }

    if (version_compare(PHP_VERSION, '5.6.0', '<')
        || version_compare(PHP_VERSION, '8.2.0', '>=')) {
        return false;
    }

    if (defined('VERSION')) {
        if (version_compare(VERSION, '2.1.1', '<')
            || version_compare(VERSION, '2.2.3', '>=')) {
            return false;
        }
    }

    return true;
}

/**
 * Post-install hook.
 *
 * No installation telemetry or unsolicited email is sent.
 *
 * @param  string $pi_name Plugin name
 * @return boolean
 */
function plugin_postinstall_documents($pi_name)
{
    return true;
}
