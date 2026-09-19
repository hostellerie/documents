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
$adminStyles = documents_first_run_read($root, 'admin_styles.php', $failures);
$categoryTemplate = documents_first_run_read($root, 'templates/cat_form.thtml', $failures);
$categoryEditor = documents_first_run_read($root, 'admin_category_editor.php', $failures);

documents_first_run_require($admin, "'/index.php?mode=edit_cat'", 'Admin home does not expose category creation.', $failures);
documents_first_run_require($adminStyles, "'list_fields'", 'Admin navigation does not expose field configuration.', $failures);
documents_first_run_require($adminStyles, "'list_groups'", 'Admin navigation does not expose selection-group configuration.', $failures);
documents_first_run_require($admin, "'/index.php?mode=new&cat='", 'Admin home does not expose document creation for configured categories.', $failures);
documents_first_run_require($admin, 'COUNT(f.fid) AS field_count', 'Admin home does not detect whether a category has fields before offering document creation.', $failures);
documents_first_run_require($admin, '$publicUrl', 'Admin home does not keep public document URLs separate from administration URLs.', $failures);
documents_first_run_require($admin, '$adminUrl', 'Admin home does not use a dedicated administration base URL.', $failures);
documents_first_run_require($admin, 'Nouvelle catégorie', 'Admin dashboard is missing the first category step.', $failures);
documents_first_run_require($admin, "'Champs'", 'Admin dashboard is missing the fields step.', $failures);
documents_first_run_require($admin, 'Créer un document', 'Admin dashboard is missing the document-creation step.', $failures);
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
