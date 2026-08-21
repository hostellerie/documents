<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.8                                                    |
// +---------------------------------------------------------------------------+
// | install_updates.php                                                       |
// |                                                                           |
// | Documents configuration upgrade helpers.                                  |
// +---------------------------------------------------------------------------+

/**
 * Add image-limit settings to an existing Documents configuration group.
 *
 * DOCUMENTS_addImageConfigItems() checks the currently loaded configuration
 * and calls config::add() only for missing keys. Customized administrator
 * values are therefore preserved and the upgrade remains idempotent.
 *
 * @return bool
 */
function DOCUMENTS_updateConfig_1_1_8()
{
    global $_CONF;

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

/**
 * Compatibility alias for development builds that already used the temporary
 * 1.1.2 helper name before the configuration work was assigned to 1.1.8.
 *
 * @return bool
 */
function DOCUMENTS_updateConfig_1_1_2()
{
    return DOCUMENTS_updateConfig_1_1_8();
}
