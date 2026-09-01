<?php

$root = dirname(__DIR__);
$failures = array();

function documents_defaults_read($root, $path, &$failures)
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

function documents_defaults_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

$fieldEditor = documents_defaults_read($root, 'public_html/field-editor.php', $failures);
$mutations = documents_defaults_read($root, 'admin_mutations.php', $failures);

documents_defaults_require(
    $fieldEditor,
    "DB_getItem(\$_TABLES['documents_fields'], 'MAX(f_order)', 'cat_id=' . \$cid)",
    'New-field editor does not calculate the category maximum order.',
    $failures
);
documents_defaults_require(
    $fieldEditor,
    '$nextOrders[$cid] = $maxOrder + 10;',
    'New-field editor does not propose the next order in steps of ten.',
    $failures
);
documents_defaults_require(
    $fieldEditor,
    '$field[\'cat_id\'] = (int) $categories[0][\'cid\'];',
    'New-field editor does not initialize the visibly selected first category.',
    $failures
);
documents_defaults_require(
    $fieldEditor,
    "category.addEventListener('change'",
    'Changing category does not refresh the proposed field order.',
    $failures
);

documents_defaults_require(
    $mutations,
    'function DOCUMENTS_adminCreateDefaultCategoryFields(',
    'Default category-field creation helper is missing.',
    $failures
);
documents_defaults_require(
    $mutations,
    "'variable' => 'name'",
    'New categories do not define the standard name field.',
    $failures
);
documents_defaults_require(
    $mutations,
    "'variable' => 'metadescription'",
    'New categories do not define the standard metadescription field.',
    $failures
);
documents_defaults_require(
    $mutations,
    "'order' => 10",
    'Default name field is not ordered first.',
    $failures
);
documents_defaults_require(
    $mutations,
    "'order' => 20",
    'Default metadescription field is not ordered second.',
    $failures
);
documents_defaults_require(
    $mutations,
    '$newCategory = ($cid <= 0);',
    'Category save path does not distinguish new categories from edits.',
    $failures
);
documents_defaults_require(
    $mutations,
    '$newCategory && !DOCUMENTS_adminCreateDefaultCategoryFields(',
    'Default fields are not restricted to newly created categories.',
    $failures
);
documents_defaults_require(
    $mutations,
    'var_name=\'{$safeVariable}\'',
    'Default field creation does not guard against duplicate variables.',
    $failures
);

if (!empty($failures)) {
    fwrite(STDERR, "Documents category/default-field checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents category/default-field checks: PASS\n";
