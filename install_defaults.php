<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | This file is used to hook into Geeklog's configuration UI.                |
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

if (strpos(strtolower($_SERVER['PHP_SELF']), 'install_defaults.php') !== false) {
    die('This file can not be used on its own.');
}

global $_DOCUMENTS_DEFAULT;
$_DOCUMENTS_DEFAULT = array();

$_DOCUMENTS_DEFAULT['documents_folder'] = 'documents';
$_DOCUMENTS_DEFAULT['documents_main_header'] = '';
$_DOCUMENTS_DEFAULT['documents_main_footer'] = '';
$_DOCUMENTS_DEFAULT['whatsnew_enabled'] = 1;
$_DOCUMENTS_DEFAULT['whatsnew_interval'] = 1209600;
$_DOCUMENTS_DEFAULT['whatsnew_limit'] = 10;
$_DOCUMENTS_DEFAULT['stats_visibility'] = 1;
$_DOCUMENTS_DEFAULT['max_image_width'] = 3000;
$_DOCUMENTS_DEFAULT['max_image_height'] = 3000;
$_DOCUMENTS_DEFAULT['max_image_size'] = 4194304;
$_DOCUMENTS_DEFAULT['default_permissions'] = array(3, 3, 2, 2);

function DOCUMENTS_configItems($c, $me)
{
    $existing = $c->get_config($me);
    return is_array($existing) ? $existing : array();
}

function DOCUMENTS_addImageConfigItems($c, $me)
{
    global $_DOCUMENTS_DEFAULT;

    $existing = DOCUMENTS_configItems($c, $me);

    if (!array_key_exists('fs_images', $existing)) {
        $c->add('fs_images', null, 'fieldset', 0, 1, null, 0, true, $me, 0);
        $existing['fs_images'] = null;
    }

    if (!array_key_exists('max_image_width', $existing)) {
        $c->add('max_image_width', $_DOCUMENTS_DEFAULT['max_image_width'], 'text', 0, 1, null, 40, true, $me, 0);
        $existing['max_image_width'] = $_DOCUMENTS_DEFAULT['max_image_width'];
    }
    if (!array_key_exists('max_image_height', $existing)) {
        $c->add('max_image_height', $_DOCUMENTS_DEFAULT['max_image_height'], 'text', 0, 1, null, 50, true, $me, 0);
        $existing['max_image_height'] = $_DOCUMENTS_DEFAULT['max_image_height'];
    }
    if (!array_key_exists('max_image_size', $existing)) {
        $c->add('max_image_size', $_DOCUMENTS_DEFAULT['max_image_size'], 'text', 0, 1, null, 60, true, $me, 0);
    }
}

/** Add integration/display settings without resetting existing values. */
function DOCUMENTS_addIntegrationConfigItems($c, $me)
{
    global $_DOCUMENTS_DEFAULT;

    $existing = DOCUMENTS_configItems($c, $me);

    if (!array_key_exists('fs_integrations', $existing)) {
        $c->add('fs_integrations', null, 'fieldset', 0, 0, null, 40, true, $me, 0);
        $existing['fs_integrations'] = null;
    }
    if (!array_key_exists('whatsnew_enabled', $existing)) {
        $c->add('whatsnew_enabled', $_DOCUMENTS_DEFAULT['whatsnew_enabled'], 'select', 0, 0, 0, 50, true, $me, 0);
        $existing['whatsnew_enabled'] = $_DOCUMENTS_DEFAULT['whatsnew_enabled'];
    }
    if (!array_key_exists('whatsnew_interval', $existing)) {
        $c->add('whatsnew_interval', $_DOCUMENTS_DEFAULT['whatsnew_interval'], 'text', 0, 0, null, 60, true, $me, 0);
        $existing['whatsnew_interval'] = $_DOCUMENTS_DEFAULT['whatsnew_interval'];
    }
    if (!array_key_exists('whatsnew_limit', $existing)) {
        $c->add('whatsnew_limit', $_DOCUMENTS_DEFAULT['whatsnew_limit'], 'text', 0, 0, null, 70, true, $me, 0);
        $existing['whatsnew_limit'] = $_DOCUMENTS_DEFAULT['whatsnew_limit'];
    }
    if (!array_key_exists('stats_visibility', $existing)) {
        $c->add('stats_visibility', $_DOCUMENTS_DEFAULT['stats_visibility'], 'select', 0, 0, 20, 80, true, $me, 0);
    }
}

function plugin_initconfig_documents()
{
    global $_DOCUMENTS_CONF, $_DOCUMENTS_DEFAULT;

    if (isset($_DOCUMENTS_CONF) && is_array($_DOCUMENTS_CONF) && count($_DOCUMENTS_CONF) > 1) {
        $_DOCUMENTS_DEFAULT = array_merge($_DOCUMENTS_DEFAULT, $_DOCUMENTS_CONF);
    }

    $me = 'documents';
    $c = config::get_instance();

    if (!$c->group_exists($me)) {
        $c->add('sg_main', null, 'subgroup', 0, 0, null, 0, true, $me, 0);
        $c->add('tab_main', null, 'tab', 0, 0, null, 0, true, $me, 0);
        $c->add('fs_main', null, 'fieldset', 0, 0, null, 0, true, $me, 0);

        $c->add('documents_folder', $_DOCUMENTS_DEFAULT['documents_folder'], 'text', 0, 0, null, 10, true, $me, 0);
        $c->add('documents_main_header', $_DOCUMENTS_DEFAULT['documents_main_header'], 'text', 0, 0, null, 20, true, $me, 0);
        $c->add('documents_main_footer', $_DOCUMENTS_DEFAULT['documents_main_footer'], 'text', 0, 0, null, 30, true, $me, 0);

        DOCUMENTS_addIntegrationConfigItems($c, $me);
        DOCUMENTS_addImageConfigItems($c, $me);

        $c->add('fs_permissions', null, 'fieldset', 0, 2, null, 0, true, $me, 0);
        $c->add('default_permissions', $_DOCUMENTS_DEFAULT['default_permissions'], '@select', 0, 2, 12, 100, true, $me, 0);
    }

    return true;
}
