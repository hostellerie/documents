<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | install_updates.php                                                       |
// |                                                                           |
// | Documents configuration upgrade helpers.                                  |
// +---------------------------------------------------------------------------+

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

/** Add 1.1.9 integration/display settings without resetting configuration. */
function DOCUMENTS_updateConfig_1_1_9()
{
    global $_CONF;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $_CONF['path'] . 'plugins/documents/install_defaults.php';

    $c = config::get_instance();
    $me = 'documents';

    if (!$c->group_exists($me)) {
        return plugin_initconfig_documents();
    }

    DOCUMENTS_addIntegrationConfigItems($c, $me);

    return true;
}

/**
 * Ensure the settings introduced during the 1.1.9 development cycle are
 * actually registered when an already-installed 1.1.9 site moves to 1.1.10.
 * The helper is idempotent and preserves all existing customized values.
 */
function DOCUMENTS_updateConfig_1_1_10()
{
    global $_CONF;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $_CONF['path'] . 'plugins/documents/install_defaults.php';

    $c = config::get_instance();
    $me = 'documents';

    if (!$c->group_exists($me)) {
        return plugin_initconfig_documents();
    }

    DOCUMENTS_addIntegrationConfigItems($c, $me);

    return true;
}

function DOCUMENTS_updateConfig_1_1_2()
{
    return DOCUMENTS_updateConfig_1_1_8();
}
