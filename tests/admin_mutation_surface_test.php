<?php

$root = dirname(__DIR__);
$failures = array();

function documents_mutation_read($root, $path, &$failures)
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        $failures[] = 'Missing file: ' . $path;
        return '';
    }
    $content = file_get_contents($file);
    if ($content === false) {
        $failures[] = 'Unable to read: ' . $path;
        return '';
    }
    return $content;
}

function documents_mutation_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_mutation_forbid($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

$admin = documents_mutation_read($root, 'admin_mutations.php', $failures);
$field = documents_mutation_read($root, 'field_mutations.php', $failures);
$dispatch = documents_mutation_read($root, 'admin_dispatch.php', $failures);
$editor = documents_mutation_read($root, 'admin_category_editor.php', $failures);
$messages = documents_mutation_read($root, 'admin_messages.php', $failures);
$rewrite = documents_mutation_read($root, 'rewrite.php', $failures);
$publicIndex = documents_mutation_read($root, 'public_html/index.php', $failures);
$adminIndex = documents_mutation_read($root, 'admin/index.php', $failures);
$adminEndpoint = documents_mutation_read($root, 'admin/admin-save.php', $failures);
$fieldEndpoint = documents_mutation_read($root, 'admin/admin-field-save.php', $failures);
$legacyAdminEndpoint = documents_mutation_read($root, 'public_html/admin-save.php', $failures);
$legacyFieldEndpoint = documents_mutation_read($root, 'public_html/admin-field-save.php', $failures);
$categoryEditorEndpoint = documents_mutation_read($root, 'public_html/category-editor.php', $failures);
$catTemplate = documents_mutation_read($root, 'templates/cat_form.thtml', $failures);
$fieldTemplate = documents_mutation_read($root, 'templates/field_form.thtml', $failures);
$groupTemplate = documents_mutation_read($root, 'templates/group_form.thtml', $failures);
$selectTemplate = documents_mutation_read($root, 'templates/select_form.thtml', $failures);

documents_mutation_require($adminIndex, '$adminSaveModes', 'Dedicated admin router does not identify secure save modes.', $failures);
documents_mutation_require($adminIndex, 'SEC_checkToken()', 'Dedicated admin router does not validate CSRF.', $failures);
documents_mutation_require($adminIndex, "SEC_hasRights('documents.admin')", 'Dedicated admin router does not require documents.admin.', $failures);
documents_mutation_require($adminIndex, 'DOCUMENTS_adminDispatchMutation(', 'Dedicated admin router does not use the secure mutation dispatcher.', $failures);
documents_mutation_require($adminEndpoint, "require __DIR__ . '/index.php';", 'General admin mutation endpoint does not delegate to the dedicated router.', $failures);
documents_mutation_require($fieldEndpoint, "require __DIR__ . '/index.php';", 'Field mutation endpoint does not delegate to the dedicated router.', $failures);

/* Historical public POST endpoints remain temporary compatibility shims and
 * must retain their established security while old installed templates exist. */
documents_mutation_require($legacyAdminEndpoint, 'SEC_checkToken()', 'Legacy general mutation endpoint lost CSRF validation.', $failures);
documents_mutation_require($legacyFieldEndpoint, 'SEC_checkToken()', 'Legacy field mutation endpoint lost CSRF validation.', $failures);
documents_mutation_require($legacyAdminEndpoint, "SEC_hasRights('documents.admin')", 'Legacy general mutation endpoint lost admin protection.', $failures);
documents_mutation_require($legacyFieldEndpoint, "SEC_hasRights('documents.admin')", 'Legacy field mutation endpoint lost admin protection.', $failures);
documents_mutation_require($messages, 'Catégorie enregistrée.', 'French category feedback is missing.', $failures);
documents_mutation_require($messages, 'Champ enregistré.', 'French field feedback is missing.', $failures);

documents_mutation_require($publicIndex, '$adminModes', 'Public router does not preserve historical admin URL compatibility.', $failures);
documents_mutation_require($publicIndex, 'DOCUMENTS_adminDispatchMutation(', 'Historical public POST bridge does not use the secure dispatcher.', $failures);
documents_mutation_require($publicIndex, '/plugins/documents/index.php', 'Historical admin GET URLs are not redirected to Geeklog administration.', $failures);
documents_mutation_require($dispatch, 'DOCUMENTS_adminPrepareCategoryRequest', 'Legacy category forms do not preserve an omitted metadescription.', $failures);
documents_mutation_require($dispatch, 'DOCUMENTS_adminDispatchSelectIsUsed', 'Select deletion does not protect used options.', $failures);
documents_mutation_forbid($dispatch, 'addslashes(', 'Secure dispatcher must not use addslashes().', $failures);

documents_mutation_require($admin, 'DB_escapeString($slug)', 'Category slug must be SQL escaped.', $failures);
documents_mutation_require($admin, 'metadescription', 'Category mutation must persist metadescription.', $failures);
documents_mutation_require($admin, 'DOCUMENTS_adminCategoryHasDocuments', 'Category deletion must protect non-empty categories.', $failures);
documents_mutation_require($admin, 'DOCUMENTS_adminSaveGroup', 'Secure group mutation is missing.', $failures);
documents_mutation_require($admin, 'DOCUMENTS_adminSaveSelect', 'Secure select mutation is missing.', $failures);
documents_mutation_forbid($admin, 'addslashes(', 'Admin mutation layer must not use addslashes().', $failures);

documents_mutation_require($field, 'DOCUMENTS_fieldAllowedTypes', 'Field type allowlist is missing.', $failures);
documents_mutation_require($field, 'DOCUMENTS_fieldVariableName', 'Variable-name validation is missing.', $failures);
documents_mutation_require($field, 'cannot be moved to another category', 'Used fields must not move category silently.', $failures);
documents_mutation_require($field, 'cannot change type directly', 'Used fields must not change type silently.', $failures);
documents_mutation_forbid($field, 'addslashes(', 'Field mutation layer must not use addslashes().', $failures);

/* Structural administration must no longer be captured by public rewrite rules. */
documents_mutation_forbid($rewrite, 'RewriteCond %{QUERY_STRING} (^|&)mode=edit_cat(&|$)', 'edit_cat is still intercepted by public rewrites.', $failures);
documents_mutation_forbid($rewrite, 'RewriteRule ^index\\.php$ category-editor.php', 'Category administration still has a public rewrite target.', $failures);
documents_mutation_require($categoryEditorEndpoint, "SEC_hasRights('documents.admin')", 'Compatibility category editor endpoint must remain protected.', $failures);
documents_mutation_require($editor, 'metadescription', 'Category editor does not load metadescription.', $failures);
documents_mutation_require($editor, 'DOCUMENTS_renderCategoryEditor', 'Category editor renderer is missing.', $failures);

/* Templates retain endpoint filenames; the admin router substitutes its own
 * base URL, so these resolve to /admin/plugins/documents/admin-*.php. */
documents_mutation_require($catTemplate, '/admin-save.php', 'Category form is not using the secure endpoint.', $failures);
documents_mutation_require($groupTemplate, '/admin-save.php', 'Group form is not using the secure endpoint.', $failures);
documents_mutation_require($selectTemplate, '/admin-save.php', 'Select form is not using the secure endpoint.', $failures);
documents_mutation_require($fieldTemplate, '/admin-field-save.php', 'Field form is not using the secure endpoint.', $failures);
documents_mutation_require($catTemplate, 'maxlength="40"', 'Category form does not reflect the schema slug limit.', $failures);
documents_mutation_require($fieldTemplate, 'maxlength="18"', 'Field variable form does not reflect the schema limit.', $failures);
documents_mutation_require($catTemplate, '>{metadescription}</textarea>', 'Metadescription is not rendered in the category form.', $failures);
documents_mutation_forbid($catTemplate, 'XMLHttpRequest', 'Category editor still depends on AJAX metadata preload.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents secure mutation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents secure mutation checks: PASS\n";
