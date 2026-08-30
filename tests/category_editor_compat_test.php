<?php

$root = dirname(__DIR__);
$failures = array();

function documents_category_editor_read($root, $path, &$failures)
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

function documents_category_editor_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_category_editor_forbid($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

$editor = documents_category_editor_read($root, 'admin_category_editor.php', $failures);
$template = documents_category_editor_read($root, 'templates/cat_form.thtml', $failures);
$mutations = documents_category_editor_read($root, 'admin_mutations.php', $failures);
$css = documents_category_editor_read($root, 'admin/documents.css', $failures);

documents_category_editor_forbid(
    $editor,
    'DB_fetchArray(DB_query(',
    'Category editor contains a DB_fetchArray(DB_query()) call that is incompatible with PHP 5.6 by-reference behavior.',
    $failures
);
documents_category_editor_require($editor, '$categoryResult = DB_query(', 'Category query result is not stored in a variable.', $failures);
documents_category_editor_forbid(
    $editor,
    'COM_createHTMLDocument($content, array(',
    'Category editor passes a temporary array to COM_createHTMLDocument().',
    $failures
);
documents_category_editor_require($editor, '$pageOptions = array(', 'Page options are not stored in a variable.', $failures);
documents_category_editor_require($editor, '$errorOptions = array(', 'Error page options are not stored in a variable.', $failures);
documents_category_editor_require($editor, '$groupId = (int) $category[\'group_id\'];', 'Group id is not stored in a variable.', $failures);
documents_category_editor_require($editor, '$permissionsEditor = SEC_getPermissionsHTML(', 'Permission values are not isolated before rendering.', $failures);

documents_category_editor_require($editor, '$documentsLanguageFile', 'Category editor does not explicitly load the Documents language file when needed.', $failures);
documents_category_editor_require($editor, 'language/english.php', 'Category editor does not provide an English language fallback.', $failures);
documents_category_editor_require($editor, '$lang = function', 'Category editor does not provide safe language-key fallbacks.', $failures);

documents_category_editor_require($template, 'id="documents-cat-name"', 'Category name field is not identified for automatic URL generation.', $failures);
documents_category_editor_require($template, 'id="documents-cat-url"', 'Category URL field is not identified for automatic URL generation.', $failures);
documents_category_editor_require($template, 'function slugify(value)', 'Category URL automatic slug generation is missing.', $failures);
documents_category_editor_require($template, 'manuallyEdited', 'Category URL manual override protection is missing.', $failures);
documents_category_editor_require($mutations, '$slugInput = $name;', 'Server-side category URL fallback from category name is missing.', $failures);

documents_category_editor_forbid($template, '<details class="documents-form-help"', 'Category form still contains the misleading collapsible help arrow.', $failures);
documents_category_editor_require($template, 'class="documents-form-intro"', 'Category form does not display a permanent introduction.', $failures);
documents_category_editor_require($template, '{metadescription_label}', 'Meta description does not have a visible label.', $failures);
documents_category_editor_require($template, '{metadescription_intro}', 'Meta description does not explain what to enter above the textarea.', $failures);
documents_category_editor_require($template, 'placeholder="{metadescription_placeholder}"', 'Meta description textarea does not contain a localized example.', $failures);
documents_category_editor_require($template, 'name="custom_header" rows="4"', 'Custom header is not rendered as a consistent multiline field.', $failures);
documents_category_editor_require($template, 'name="custom_footer" rows="4"', 'Custom footer is not rendered as a consistent multiline field.', $failures);

$helpVariables = array(
    'category_help',
    'cat_url_help',
    'metadescription_help',
    'cat_help_explanation',
    'template_help',
    'css_help',
    'custom_header_help',
    'custom_footer_help',
    'cat_order_help',
    'list_index_help',
    'submitable_help',
    'owner_help',
    'group_help',
    'permissions_editor_help',
    'action_help'
);
foreach ($helpVariables as $helpVariable) {
    documents_category_editor_require($template, '{' . $helpVariable . '}', 'Category form does not display help for: ' . $helpVariable, $failures);
    documents_category_editor_require($editor, "'" . $helpVariable . "' =>", 'Category editor does not define help for: ' . $helpVariable, $failures);
}

documents_category_editor_require($editor, '135 à 160 caractères', 'French meta description guidance is incomplete.', $failures);
documents_category_editor_require($editor, '135–160 characters', 'English meta description guidance is incomplete.', $failures);
documents_category_editor_require($css, '.documents-field-help', 'Admin stylesheet does not make field help consistently visible.', $failures);
documents_category_editor_require($css, '.documents-form-label', 'Admin stylesheet does not provide visible field labels.', $failures);
documents_category_editor_require($css, '.documents-form-textarea', 'Admin stylesheet does not normalize textareas.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents category editor compatibility/guidance checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents category editor compatibility/guidance checks: PASS\n";
