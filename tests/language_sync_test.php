<?php

/* Documents language synchronization test. Compatible with PHP 5.6-8.1. */

$root = dirname(__DIR__);

$LANG32 = array();
$LANG32[9] = 'requires a newer version of Geeklog';

$LANG_DOCUMENTS_1 = array();
$LANG_configsections = array();
$LANG_confignames = array();
$LANG_configsubgroups = array();
$LANG_tab = array();
$LANG_fs = array();
$LANG_configselects = array();

require $root . '/language/english.php';

$english = array(
    'documents' => array_keys($LANG_DOCUMENTS_1),
    'confignames' => array_keys($LANG_confignames['documents']),
    'configsections' => array_keys($LANG_configsections['documents']),
    'configsubgroups' => array_keys($LANG_configsubgroups['documents']),
    'tabs' => array_keys($LANG_tab['documents']),
    'fieldsets' => array_keys($LANG_fs['documents']),
    'configselects' => array_keys($LANG_configselects['documents'])
);

$LANG_DOCUMENTS_1 = array();
$LANG_configsections = array();
$LANG_confignames = array();
$LANG_configsubgroups = array();
$LANG_tab = array();
$LANG_fs = array();
$LANG_configselects = array();

require $root . '/language/french_france_utf-8.php';

$french = array(
    'documents' => array_keys($LANG_DOCUMENTS_1),
    'confignames' => array_keys($LANG_confignames['documents']),
    'configsections' => array_keys($LANG_configsections['documents']),
    'configsubgroups' => array_keys($LANG_configsubgroups['documents']),
    'tabs' => array_keys($LANG_tab['documents']),
    'fieldsets' => array_keys($LANG_fs['documents']),
    'configselects' => array_keys($LANG_configselects['documents'])
);

$errors = array();

foreach ($english as $section => $englishKeys) {
    $frenchKeys = $french[$section];
    sort($englishKeys);
    sort($frenchKeys);
    if ($englishKeys !== $frenchKeys) {
        $errors[] = 'Language key mismatch in section: ' . $section;
    }
}

$requiredConfigNames = array(
    'documents_folder',
    'documents_main_header',
    'documents_main_footer',
    'whatsnew_enabled',
    'whatsnew_interval',
    'whatsnew_limit',
    'stats_visibility',
    'max_image_width',
    'max_image_height',
    'max_image_size',
    'default_permissions'
);

foreach ($requiredConfigNames as $configName) {
    if (!isset($LANG_confignames['documents'][$configName])) {
        $errors[] = 'Missing French configuration label: ' . $configName;
    }
}

$requiredDocumentLabels = array(
    'stats_title',
    'stats_documents',
    'stats_views',
    'whatsnew_title',
    'whatsnew_none'
);
foreach ($requiredDocumentLabels as $label) {
    if (!isset($LANG_DOCUMENTS_1[$label])) {
        $errors[] = 'Missing French Documents label: ' . $label;
    }
}

if (!isset($LANG_fs['documents']['fs_integrations'])) {
    $errors[] = 'Missing integration fieldset label.';
}
if (!isset($LANG_configselects['documents'][20])) {
    $errors[] = 'Missing statistics visibility selection labels.';
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "Documents language synchronization: PASS\n";
