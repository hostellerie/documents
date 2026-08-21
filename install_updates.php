<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | install_updates.php                                                       |
// |                                                                           |
// | Documents configuration upgrade helpers.                                  |
// +---------------------------------------------------------------------------+

/**
 * Add image-limit settings to an existing Documents configuration group.
 *
 * @return bool
 */
function DOCUMENTS_updateConfig_1_1_2()
{
    global $_CONF, $_DOCUMENTS_DEFAULT;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $_CONF['path'] . 'plugins/documents/install_defaults.php';

    $c = config::get_instance();
    $me = 'documents';

    if (!$c->group_exists($me)) {
        return plugin_initconfig_documents();
    }

    DOCUMENTS_addImageConfigItems($c, $me);

    return true;
}
