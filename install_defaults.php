<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.0                                                      |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | This file is used to hook into Geeklog's configuration UI                 |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012 by the following authors:                              |
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
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+

/**
* @package Documents
*/

if (strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die ('This file can not be used on its own.');
}

/**
* Documents default settings
*
* Initial Installation Defaults used when loading the online configuration
* records.  These settings are only used during the initial installation
* and not referenced any more once the plugin is installed
*/
global $_DOCUMENTS_DEFAULT;
$_DOCUMENTS_DEFAULT = array();

$_DOCUMENTS_DEFAULT['documents_folder'] = 'documents';
$_DOCUMENTS_DEFAULT['documents_main_header'] = '';
$_DOCUMENTS_DEFAULT['documents_main_footer'] = '';

// Set the default permissions
$_DOCUMENTS_DEFAULT['default_permissions'] =  array (3, 3, 2, 2);

/**
* Initialize Documents plugin configuration
*
* Creates the database entries for the configuation if they don't already
* exist.  Initial values will be taken from $_DOCUMENTS_DEFAULT.
*
* @return   boolean     TRUE: success; FALSE: an error occurred
*/
function plugin_initconfig_documents()
{
    global $_DOCUMENTS_CONF, $_DOCUMENTS_DEFAULT;

    if (is_array($_DOCUMENTS_CONF) && (count($_DOCUMENTS_CONF) > 1)) {
        $_DOCUMENTS_DEFAULT = array_merge($_DOCUMENTS_DEFAULT, $_DOCUMENTS_CONF);
    }

    $me = 'documents';

    $c = config::get_instance();
    if (!$c->group_exists('documents')) {
        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true, $me, 0);
        $c->add('tab_main', NULL, 'tab', 0, 0, NULL, 0, true, $me, 0);
        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true, $me, 0);

        $c->add('documents_folder', $_DOCUMENTS_DEFAULT['documents_folder'], 'text', 0, 0, null, 10, true, $me, 0);
        $c->add('documents_main_header', $_DOCUMENTS_DEFAULT['documents_main_header'], 'text', 0, 0, null, 20, true, $me, 0);
        $c->add('documents_main_footer', $_DOCUMENTS_DEFAULT['documents_main_footer'], 'text', 0, 0, null, 30, true, $me, 0);
		
		// Permissions
        $c->add('fs_permissions', NULL, 'fieldset', 0, 2, NULL, 0, true, $me, 0);
        $c->add('default_permissions', $_DOCUMENTS_DEFAULT['default_permissions'],
                '@select', 0, 2, 12, 100, true, $me, 0);
    }

    return true;
}
?>

