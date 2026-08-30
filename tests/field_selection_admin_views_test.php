<?php

$root = dirname(__DIR__);
$failures = array();

function documents_admin_views_read($root, $path, &$failures)
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

function documents_admin_views_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

$index = documents_admin_views_read($root, 'public_html/index.php', $failures);
$fieldEditor = documents_admin_views_read($root, 'public_html/field-editor.php', $failures);
$selects = documents_admin_views_read($root, 'public_html/admin-selects.php', $failures);
$selectEditor = documents_admin_views_read($root, 'public_html/select-editor.php', $failures);
$groupEditor = documents_admin_views_read($root, 'public_html/group-editor.php', $failures);
$fieldMutations = documents_admin_views_read($root, 'field_mutations.php', $failures);
$css = documents_admin_views_read($root, 'admin/modern-admin.css', $failures);

$routes = array(
    "'edit_field' => 'field-editor.php'" => 'edit_field is not routed to the modern field editor.',
    "'list_selects' => 'admin-selects.php'" => 'list_selects is not routed to the modern option list.',
    "'edit_select' => 'select-editor.php'" => 'edit_select is not routed to the modern option editor.',
    "'edit_group' => 'group-editor.php'" => 'edit_group is not routed to the modern group editor.'
);
foreach ($routes as $needle => $message) {
    documents_admin_views_require($index, $needle, $message, $failures);
}

documents_admin_views_require($fieldEditor, 'id="documents-variable-name"', 'Field editor is missing the editable variable-name control.', $failures);
documents_admin_views_require($fieldEditor, 'normalize(name.value)', 'Field editor does not generate the variable name from the field label.', $failures);
documents_admin_views_require($fieldEditor, 'data-existing=', 'Field editor does not protect existing variables from automatic replacement.', $failures);
documents_admin_views_require($fieldEditor, 'documents-selection-group-row', 'Field editor is missing contextual selection-group configuration.', $failures);
documents_admin_views_require($fieldEditor, 'documents-text-format-row', 'Field editor is missing contextual text-format configuration.', $failures);
documents_admin_views_require($fieldEditor, 'SEC_getPermissionsHTML(', 'Field editor no longer exposes Geeklog permissions.', $failures);

documents_admin_views_require($fieldMutations, 'function DOCUMENTS_fieldVariableFromLabel(', 'Server-side field variable generation is missing.', $failures);
documents_admin_views_require($fieldMutations, '$variable = DOCUMENTS_fieldVariableFromLabel($name);', 'Blank field variables are not generated server-side.', $failures);

documents_admin_views_require($selects, 'Valeur interne', 'Selection list lacks contextual internal-value guidance.', $failures);
documents_admin_views_require($selects, 'Libellé affiché', 'Selection list lacks displayed-label guidance.', $failures);
documents_admin_views_require($selectEditor, 'name="s_name"', 'Selection editor is missing the stored internal value.', $failures);
documents_admin_views_require($selectEditor, 'name="s_value"', 'Selection editor is missing the displayed label.', $failures);
documents_admin_views_require($selectEditor, 'name="s_order"', 'Selection editor is missing ordering.', $failures);
documents_admin_views_require($groupEditor, 'name="g_name"', 'Selection-group editor does not use the secure mutation field name.', $failures);
documents_admin_views_require($groupEditor, 'name="g_help"', 'Selection-group editor does not use the secure help field name.', $failures);

documents_admin_views_require($css, '.documents-admin-form__grid', 'Two-column modern editor grid styling is missing.', $failures);
documents_admin_views_require($css, '.documents-admin-variable-preview', 'Variable-name preview styling is missing.', $failures);
documents_admin_views_require($css, '.documents-admin-advanced', 'Collapsible advanced-options styling is missing.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents field/selection admin view checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents field/selection admin view checks: PASS\n";
