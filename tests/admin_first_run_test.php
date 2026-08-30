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

documents_first_run_require($template, '{new_category_url}', 'Admin home does not expose a create-category action.', $failures);
documents_first_run_require($admin, "'/index.php?mode=edit_cat'", 'Admin home does not build the new-category URL.', $failures);
documents_first_run_require($admin, "'/index.php?mode=list_fields&cat='", 'Admin home does not expose field configuration per category.', $failures);
documents_first_run_require($admin, "'/index.php?mode=new&cat='", 'Admin home does not expose document creation for configured categories.', $failures);
documents_first_run_require($admin, 'COUNT(f.fid) AS field_count', 'Admin home does not detect whether a category has fields before offering document creation.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents admin first-run checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents admin first-run checks: PASS\n";
