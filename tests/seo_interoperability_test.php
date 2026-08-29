<?php

$root = dirname(__DIR__);
$files = array(
    'autoinstall' => file_get_contents($root . '/autoinstall.php'),
    'install' => file_get_contents($root . '/sql/mysql_install.php'),
    'updates' => file_get_contents($root . '/install_updates.php'),
    'functions' => file_get_contents($root . '/functions.inc'),
    'interop' => file_get_contents($root . '/interoperability.php'),
    'embeds' => file_get_contents($root . '/embeds.php'),
    'seo' => file_get_contents($root . '/seo.php'),
    'runtime' => file_get_contents($root . '/runtime.php'),
    'security' => file_get_contents($root . '/security.php'),
    'public' => file_get_contents($root . '/public_html/index.php'),
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
documents_test_require($files['autoinstall'], "DOCUMENTS_MIN_GEEKLOG_VERSION, '2.1.1'", 'Geeklog 2.1.1 minimum target is missing.', $failures);
documents_test_require($files['autoinstall'], "DOCUMENTS_MIN_PHP_VERSION, '5.6.0'", 'PHP 5.6 minimum target is missing.', $failures);

documents_test_require($files['install'], 'metadescription varchar(255)', 'Fresh-install category metadescription column is missing.', $failures);
documents_test_require($files['updates'], 'function DOCUMENTS_updateSchema_1_2_0()', '1.2.0 schema upgrade is missing.', $failures);
documents_test_require($files['updates'], "LIKE 'metadescription'", 'Metadescription migration is not idempotence-aware.', $failures);

documents_test_require($files['functions'], "require_once $plugin_path . 'interoperability.php'", 'Interoperability callbacks are not loaded by functions.inc.', $failures);
documents_test_require($files['interop'], 'function plugin_getiteminfo_documents(', 'Item Info callback is missing.', $failures);
documents_test_require($files['interop'], "(string) $id === '*'", 'Item Info collection support is missing.', $failures);
documents_test_require($files['interop'], "'since'", 'Collection since filtering is missing.', $failures);
documents_test_require($files['interop'], "'date-created'", 'Core-style date-created filtering is missing.', $failures);
documents_test_require($files['interop'], 'function plugin_idtourl_documents(', 'ID-to-URL resolver is missing.', $failures);
documents_test_require($files['interop'], 'function plugin_urltoid_documents(', 'URL-to-ID resolver is missing.', $failures);
documents_test_require($files['interop'], 'function plugin_collectSitemapItems_documents(', 'Native sitemap collector is missing.', $failures);
documents_test_require($files['interop'], "'date-modified'", 'Sitemap/ItemInfo modification date is missing.', $failures);
documents_test_require($files['interop'], "PLG_itemSaved((string) $id, 'documents')", 'Saved lifecycle event is missing.', $failures);
documents_test_require($files['interop'], "PLG_itemDeleted((string) $id, 'documents')", 'Deleted lifecycle event is missing.', $failures);

documents_test_require($files['embeds'], 'function plugin_autotags_documents(', 'Documents autotags are missing.', $failures);
documents_test_require($files['embeds'], "array('document', 'documents')", 'Expected autotag names are missing.', $failures);
documents_test_require($files['embeds'], 'function phpblock_documents_recent()', 'Recent Documents PHP block is missing.', $failures);
documents_test_require($files['embeds'], 'function phpblock_documents_popular()', 'Popular Documents PHP block is missing.', $failures);

documents_test_require($files['seo'], 'rel=\"canonical\"', 'Canonical link generation is missing.', $failures);
documents_test_require($files['seo'], 'name=\"description\"', 'SEO meta description generation is missing.', $failures);
documents_test_require($files['seo'], 'property=\"og:title\"', 'OpenGraph metadata is missing.', $failures);
documents_test_require($files['seo'], 'name=\"twitter:card\"', 'Twitter card metadata is missing.', $failures);
documents_test_require($files['seo'], 'application/ld+json', 'JSON-LD output is missing.', $failures);
documents_test_require($files['seo'], "'schema_type' => 'CreativeWork'", 'Document CreativeWork schema is missing.', $failures);
documents_test_require($files['seo'], "'schema_type' => 'CollectionPage'", 'CollectionPage schema is missing.', $failures);
documents_test_require($files['seo'], "['metadescription']", 'Category SEO is not using the dedicated metadescription field.', $failures);

documents_test_require($files['category_template'], 'name="metadescription"', 'Category metadescription editor is missing.', $failures);
documents_test_require($files['category_template'], 'name="cat_help"', 'cat_help must remain a separate category field.', $failures);

documents_test_require($files['public'], 'SEC_checkToken()', 'Mutating public document routes do not validate CSRF.', $failures);
documents_test_require($files['public'], 'DOCUMENTS_CSRF_VALIDATED', 'Validated CSRF state is not exposed to deferred persistence.', $failures);
documents_test_require($files['runtime'], 'DOCUMENTS_runtimeLifecycleAfterSave', 'Runtime lifecycle integration is missing.', $failures);
documents_test_require($files['runtime'], 'DOCUMENTS_runtimeSaveCategoryMetaDescription', 'Category metadescription persistence is missing.', $failures);

documents_test_require($files['security'], 'DOCUMENTS_plainTextInput', 'Plain-text input normalization is missing.', $failures);
documents_test_require($files['security'], "($type === 'select' || $type === 'radio')", 'Select/radio forged-value validation is missing.', $failures);

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
