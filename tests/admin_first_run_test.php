<?php

$root = dirname(__DIR__);
$failures = array();

function documents_first_run_read($root, $path, &$failures)
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

function documents_first_run_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

$admin = documents_first_run_read($root, 'admin/index.php', $failures);
$template = documents_first_run_read($root, 'templates/admin_home.thtml', $failures);
$categoryTemplate = documents_first_run_read($root, 'templates/cat_form.thtml', $failures);
$categoryEditor = documents_first_run_read($root, 'admin_category_editor.php', $failures);

documents_first_run_require($template, '{new_category_url}', 'Admin home does not expose a create-category action.', $failures);
documents_first_run_require($admin, "'/index.php?mode=edit_cat'", 'Admin home does not build the new-category URL.', $failures);
documents_first_run_require($admin, "'/index.php?mode=list_fields&cat='", 'Admin home does not expose field configuration per category.', $failures);
documents_first_run_require($admin, "'/index.php?mode=new&cat='", 'Admin home does not expose document creation for configured categories.', $failures);
documents_first_run_require($admin, 'COUNT(f.fid) AS field_count', 'Admin home does not detect whether a category has fields before offering document creation.', $failures);
documents_first_run_require($template, '<details class="documents-admin-help"', 'Admin home does not provide collapsible first-document help.', $failures);
documents_first_run_require($admin, "'help_step_category'", 'Admin first-document guidance is missing the category step.', $failures);
documents_first_run_require($admin, "'help_step_fields'", 'Admin first-document guidance is missing the fields step.', $failures);
documents_first_run_require($admin, "'help_step_document'", 'Admin first-document guidance is missing the document step.', $failures);
documents_first_run_require($categoryTemplate, '{general_legend}', 'Category editor has no general-information legend.', $failures);
documents_first_run_require($categoryTemplate, '{display_legend}', 'Category editor has no display/integration legend.', $failures);
documents_first_run_require($categoryTemplate, '{publication_legend}', 'Category editor has no publication legend.', $failures);
documents_first_run_require($categoryTemplate, '{permissions_legend}', 'Category editor has no permissions legend.', $failures);
documents_first_run_require($categoryTemplate, '{template_help}', 'Category template option has no explanation.', $failures);
documents_first_run_require($categoryTemplate, '{css_help}', 'Category CSS option has no explanation.', $failures);
documents_first_run_require($categoryTemplate, '{cat_order_help}', 'Category order option has no explanation.', $failures);
documents_first_run_require($categoryTemplate, '{submitable_help}', 'Category submission option has no explanation.', $failures);
documents_first_run_require($categoryEditor, "'permissions_help'", 'Category permission guidance is missing.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents admin first-run checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents admin first-run checks: PASS\n";
