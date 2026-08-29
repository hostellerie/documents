<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | install_updates.php                                                       |
// |                                                                           |
// | Documents configuration and schema upgrade helpers.                       |
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

/** Add the dedicated category SEO meta-description column. */
function DOCUMENTS_updateSchema_1_2_0()
{
    global $_TABLES, $_DB_dbms;

    if (!isset($_TABLES['documents_cat'])) {
        return false;
    }

    if (isset($_DB_dbms) && $_DB_dbms !== 'mysql') {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents 1.2.0 schema upgrade: automatic metadescription migration is currently implemented for MySQL/MariaDB.');
        }
        return false;
    }

    $table = $_TABLES['documents_cat'];
    $result = DB_query("SHOW COLUMNS FROM {$table} LIKE 'metadescription'");
    if (DB_numRows($result) > 0) {
        return true;
    }

    DB_query("ALTER TABLE {$table} ADD metadescription VARCHAR(255) NOT NULL DEFAULT '' AFTER cat_help");

    $verify = DB_query("SHOW COLUMNS FROM {$table} LIKE 'metadescription'");
    return DB_numRows($verify) > 0;
}

function DOCUMENTS_updateConfig_1_1_2()
{
    return DOCUMENTS_updateConfig_1_1_8();
}
