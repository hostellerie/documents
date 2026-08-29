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
$adminEndpoint = documents_mutation_read($root, 'public_html/admin-save.php', $failures);
$fieldEndpoint = documents_mutation_read($root, 'public_html/admin-field-save.php', $failures);
$catTemplate = documents_mutation_read($root, 'templates/cat_form.thtml', $failures);
$fieldTemplate = documents_mutation_read($root, 'templates/field_form.thtml', $failures);
$groupTemplate = documents_mutation_read($root, 'templates/group_form.thtml', $failures);
$selectTemplate = documents_mutation_read($root, 'templates/select_form.thtml', $failures);

documents_mutation_require($adminEndpoint, 'SEC_checkToken()', 'General admin mutation endpoint must validate CSRF.', $failures);
documents_mutation_require($fieldEndpoint, 'SEC_checkToken()', 'Field mutation endpoint must validate CSRF.', $failures);
documents_mutation_require($adminEndpoint, "SEC_hasRights('documents.admin')", 'General mutation endpoint must require documents.admin.', $failures);
documents_mutation_require($fieldEndpoint, "SEC_hasRights('documents.admin')", 'Field mutation endpoint must require documents.admin.', $failures);

documents_mutation_require($admin, 'DB_escapeString($slug)', 'Category slug must be SQL escaped.', $failures);
documents_mutation_require($admin, 'metadescription', 'Category mutation must persist metadescription.', $failures);
documents_mutation_require($admin, 'DOCUMENTS_adminCategoryHasDocuments', 'Category deletion must protect non-empty categories.', $failures);
documents_mutation_require($admin, 'DOCUMENTS_adminSaveGroup', 'Secure group mutation is missing.', $failures);
documents_mutation_require($admin, 'DOCUMENTS_adminSaveSelect', 'Secure select mutation is missing.', $failures);
documents_mutation_forbid($admin, 'addslashes(', 'New admin mutation layer must not use addslashes().', $failures);

documents_mutation_require($field, 'DOCUMENTS_fieldAllowedTypes', 'Field type allowlist is missing.', $failures);
documents_mutation_require($field, "array('text', 'textarea', 'decimal', 'date', 'image', 'checkbox', 'select', 'category')", 'Core field types are not explicitly allowlisted.', $failures);
documents_mutation_require($field, 'DOCUMENTS_fieldVariableName', 'Variable-name validation is missing.', $failures);
documents_mutation_require($field, 'cannot be moved to another category', 'Used fields must not move category silently.', $failures);
documents_mutation_require($field, 'cannot change type directly', 'Used fields must not change type silently.', $failures);
documents_mutation_forbid($field, 'addslashes(', 'New field mutation layer must not use addslashes().', $failures);

documents_mutation_require($catTemplate, '/admin-save.php', 'Category form is not using the secure endpoint.', $failures);
documents_mutation_require($groupTemplate, '/admin-save.php', 'Group form is not using the secure endpoint.', $failures);
documents_mutation_require($selectTemplate, '/admin-save.php', 'Select form is not using the secure endpoint.', $failures);
documents_mutation_require($fieldTemplate, '/admin-field-save.php', 'Field form is not using the secure endpoint.', $failures);
documents_mutation_require($catTemplate, 'maxlength="40"', 'Category form does not reflect the 40-character schema limit.', $failures);
documents_mutation_require($fieldTemplate, 'maxlength="18"', 'Field variable form does not reflect the 18-character schema limit.', $failures);
documents_mutation_require($catTemplate, 'submit.disabled = true', 'Existing category metadata preload is not fail-safe.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents secure mutation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents secure mutation checks: PASS\n";
