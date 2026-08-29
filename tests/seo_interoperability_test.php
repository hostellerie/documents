<?php

$root = dirname(__DIR__);
$files = array(
    'autoinstall' => file_get_contents($root . '/autoinstall.php'),
    'install' => file_get_contents($root . '/sql/mysql_install.php'),
    'updates' => file_get_contents($root . '/install_updates.php'),
    'functions' => file_get_contents($root . '/functions.inc'),
    'interop' => file_get_contents($root . '/interoperability.php'),
    'indexability' => file_get_contents($root . '/indexability.php'),
    'embeds' => file_get_contents($root . '/embeds.php'),
    'distribution' => file_get_contents($root . '/distribution.php'),
    'seo' => file_get_contents($root . '/seo.php'),
    'runtime' => file_get_contents($root . '/runtime.php'),
    'security' => file_get_contents($root . '/security.php'),
    'public' => file_get_contents($root . '/public_html/index.php'),
    'document' => file_get_contents($root . '/public_html/document.php'),
    'document_save' => file_get_contents($root . '/public_html/document-save.php'),
    'template' => file_get_contents($root . '/templates/document.thtml'),
    'category_template' => file_get_contents($root . '/templates/cat_form.thtml')
);

$failures = array();

function documents_test_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_test_forbid($content, $needle, $message, &$failures)
{
    if ($content !== false && strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

documents_test_require($files['autoinstall'], "'pi_version' => '1.2.0'", 'Plugin metadata is not 1.2.0.', $failures);
documents_test_require($files['autoinstall'], "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')", 'Geeklog 2.1.1 minimum target is missing.', $failures);
documents_test_require($files['autoinstall'], "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')", 'PHP 5.6 minimum target is missing.', $failures);
documents_test_require($files['autoinstall'], "define('DOCUMENTS_SUPPORTED_DBMS', 'mysql')", 'MySQL/MariaDB-only database policy is missing.', $failures);
documents_test_require($files['autoinstall'], '$_DB_dbms !== DOCUMENTS_SUPPORTED_DBMS', 'Unsupported database backends are not explicitly rejected.', $failures);

if (is_file($root . '/sql/mssql_install.php')) {
    $failures[] = 'Obsolete MSSQL installation support is still present.';
}
if (is_file($root . '/public_html/category-meta.php')) {
    $failures[] = 'Obsolete category metadata AJAX endpoint is still present.';
}

documents_test_require($files['install'], 'metadescription varchar(255)', 'Fresh-install category metadescription column is missing.', $failures);
documents_test_require($files['updates'], 'function DOCUMENTS_updateSchema_1_2_0()', '1.2.0 schema upgrade is missing.', $failures);
documents_test_require($files['updates'], "LIKE 'metadescription'", 'Metadescription migration is not idempotence-aware.', $failures);

documents_test_require($files['functions'], 'require_once $plugin_path . \'interoperability.php\'', 'Interoperability callbacks are not loaded by functions.inc.', $failures);
documents_test_require($files['interop'], "'embeds.php'", 'Autotag/block layer is not loaded by interoperability.', $failures);
documents_test_require($files['embeds'], "'distribution.php'", 'Feed/statistics callbacks are not loaded through the interoperability chain.', $failures);
documents_test_require($files['interop'], 'function plugin_getiteminfo_documents(', 'Item Info callback is missing.', $failures);
documents_test_require($files['interop'], '(string) $id === \'*\'', 'Item Info collection support is missing.', $failures);
documents_test_require($files['interop'], "'since'", 'Collection since filtering is missing.', $failures);
documents_test_require($files['interop'], "'date-created'", 'Core-style date-created filtering is missing.', $failures);
documents_test_require($files['interop'], 'function plugin_idtourl_documents(', 'ID-to-URL resolver is missing.', $failures);
documents_test_require($files['interop'], 'function plugin_urltoid_documents(', 'URL-to-ID resolver is missing.', $failures);
documents_test_require($files['interop'], 'function plugin_collectSitemapItems_documents(', 'Native sitemap collector is missing.', $failures);
documents_test_require($files['interop'], "'date-modified'", 'Sitemap/ItemInfo modification date is missing.', $failures);
documents_test_require($files['interop'], 'COM_getPermSQL(\'AND\', $uid, 2, \'d\')', 'Item Info document permission guard is missing.', $failures);
documents_test_require($files['interop'], 'COM_getPermSQL(\'AND\', $uid, 2, \'c\')', 'Item Info category permission guard is missing.', $failures);

documents_test_require($files['indexability'], 'function DOCUMENTS_isPubliclyIndexable(', 'Anonymous indexability helper is missing.', $failures);
documents_test_require($files['indexability'], '$anonymousUid = 1;', 'Indexability does not explicitly use Geeklog anonymous uid 1.', $failures);
documents_test_require($files['indexability'], 'function DOCUMENTS_notifyPublicTransition(', 'Public lifecycle transition helper is missing.', $failures);
documents_test_require($files['indexability'], 'PLG_itemSaved($documentId, \'documents\')', 'Public saved lifecycle event is missing.', $failures);
documents_test_require($files['indexability'], 'PLG_itemDeleted($documentId, \'documents\')', 'Public deleted lifecycle event is missing.', $failures);
documents_test_require($files['runtime'], 'DOCUMENTS_isPubliclyIndexable($id)', 'Legacy lifecycle does not test anonymous indexability.', $failures);
documents_test_require($files['runtime'], 'DOCUMENTS_notifyPublicTransition($id, $wasPublic, $isPublic)', 'Legacy lifecycle does not use public transitions.', $failures);
documents_test_require($files['document_save'], 'DOCUMENTS_isPubliclyIndexable($documentId)', 'Secure standard save does not snapshot previous public visibility.', $failures);
documents_test_require($files['document_save'], 'DOCUMENTS_notifyPublicTransition($savedId, $wasPublic, $isPublic)', 'Secure standard save does not use public lifecycle transitions.', $failures);
documents_test_forbid($files['runtime'], 'DOCUMENTS_runtimeSaveCategoryMetaDescription', 'Obsolete deferred category metadata persistence remains.', $failures);

documents_test_require($files['embeds'], 'function plugin_autotags_documents(', 'Documents autotags are missing.', $failures);
documents_test_require($files['embeds'], "array('document', 'documents')", 'Expected autotag names are missing.', $failures);
documents_test_require($files['embeds'], 'function phpblock_documents_recent()', 'Recent Documents PHP block is missing.', $failures);
documents_test_require($files['embeds'], 'function phpblock_documents_popular()', 'Popular Documents PHP block is missing.', $failures);

documents_test_require($files['distribution'], 'function plugin_getfeednames_documents()', 'Native feed names callback is missing.', $failures);
documents_test_require($files['distribution'], 'function plugin_getfeedcontent_documents(', 'Native feed content callback is missing.', $failures);
documents_test_require($files['distribution'], 'function plugin_feedupdatecheck_documents(', 'Native feed update callback is missing.', $failures);
documents_test_require($files['distribution'], 'function plugin_statssummary_documents()', 'Native statistics summary callback is missing.', $failures);
documents_test_require($files['distribution'], 'function plugin_showstats_documents()', 'Native detailed statistics callback is missing.', $failures);

documents_test_require($files['seo'], 'rel="canonical"', 'Canonical link generation is missing.', $failures);
documents_test_require($files['seo'], 'name="description"', 'SEO meta description generation is missing.', $failures);
documents_test_require($files['seo'], 'property="og:title"', 'OpenGraph metadata is missing.', $failures);
documents_test_require($files['seo'], 'name="twitter:card"', 'Twitter card metadata is missing.', $failures);
documents_test_require($files['seo'], 'application/ld+json', 'JSON-LD output is missing.', $failures);
documents_test_require($files['seo'], "'schema_type' => 'CreativeWork'", 'Document CreativeWork schema is missing.', $failures);
documents_test_require($files['seo'], "'schema_type' => 'CollectionPage'", 'CollectionPage schema is missing.', $failures);
documents_test_require($files['seo'], "['metadescription']", 'Category SEO is not using the dedicated metadescription field.', $failures);

documents_test_require($files['category_template'], 'name="metadescription"', 'Category metadescription editor is missing.', $failures);
documents_test_require($files['category_template'], 'name="cat_help"', 'cat_help must remain a separate category field.', $failures);
documents_test_forbid($files['category_template'], 'XMLHttpRequest', 'Category editor still contains AJAX metadata preload.', $failures);

documents_test_require($files['public'], 'SEC_checkToken()', 'Mutating legacy routes do not validate CSRF.', $failures);
documents_test_require($files['document'], '$categoryAccess = SEC_hasAccess(', 'Public document category permission guard is missing.', $failures);
documents_test_require($files['document'], 'if ($categoryAccess < 2)', 'Public document category access is not enforced.', $failures);
documents_test_require($files['security'], 'DOCUMENTS_plainTextInput', 'Plain-text input normalization is missing.', $failures);
documents_test_require($files['security'], '($type === \'select\' || $type === \'radio\')', 'Select/radio forged-value validation is missing.', $failures);

documents_test_require($files['template'], '<article', 'Default document template is not semantic article markup.', $failures);
documents_test_forbid($files['template'], 'plusone.js', 'Obsolete Google+ script remains in default template.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents 1.2.0 SEO/interoperability checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents 1.2.0 SEO/interoperability checks: PASS\n";
