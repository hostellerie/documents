<?php

$root = dirname(__DIR__);
$failures = array();

function documents_admin_context_read($root, $path, &$failures)
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

$index = documents_admin_context_read($root, 'public_html/index.php', $failures);
$fields = documents_admin_context_read($root, 'public_html/admin-fields.php', $failures);
$groups = documents_admin_context_read($root, 'public_html/admin-groups.php', $failures);
$editor = documents_admin_context_read($root, 'public_html/group-editor.php', $failures);
$styles = documents_admin_context_read($root, 'admin_styles.php', $failures);
$css = documents_admin_context_read($root, 'admin/modern-admin.css', $failures);
$mutations = documents_admin_context_read($root, 'admin_mutations.php', $failures);

$requirements = array(
    array($index, "'list_fields' => 'admin-fields.php'", 'list_fields is not routed to the modern contextual view.'),
    array($index, "'list_groups' => 'admin-groups.php'", 'list_groups is not routed to the modern contextual view.'),
    array($index, "'edit_group' => 'group-editor.php'", 'edit_group is not routed to the modern contextual editor.'),
    array($fields, 'documents-admin-guide', 'Fields view has no contextual guide.'),
    array($fields, 'documents-admin-table', 'Fields view has no modern structured table.'),
    array($fields, '{variable}', 'Fields guidance does not explain template variables.'),
    array($groups, 'documents-admin-guide', 'Selection groups view has no contextual guide.'),
    array($groups, 'option_count', 'Selection groups view does not expose option counts.'),
    array($editor, 'name="g_name"', 'Group editor does not use the secure mutation field name g_name.'),
    array($editor, 'name="g_help"', 'Group editor does not use the secure mutation field name g_help.'),
    array($editor, 'SEC_createToken()', 'Group editor does not create a Geeklog CSRF token.'),
    array($styles, 'modern-admin.css?v=1.2.0-1', 'Modern admin stylesheet is not registered through the compatibility helper.'),
    array($css, '.documents-admin-page', 'Modern admin page styling is missing.'),
    array($css, '.documents-admin-form__help', 'Contextual form-help styling is missing.'),
    array($mutations, "\$_TABLES['documents_selects_group']", 'Selection group mutations do not use the declared Documents group table.')
);

foreach ($requirements as $requirement) {
    if (strpos($requirement[0], $requirement[1]) === false) {
        $failures[] = $requirement[2];
    }
}

if (strpos($mutations, "\$_TABLES['documents_groups']") !== false) {
    $failures[] = 'Obsolete undefined documents_groups table mapping remains in group mutations.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents contextual admin view checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents contextual admin view checks: PASS\n";
