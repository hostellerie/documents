<?php

/* Documents 1.2.0 CSV import/export administration. PHP 5.6+. */

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

require_once $_CONF['path'] . 'plugins/documents/import_export.php';
require_once $_CONF['path'] . 'plugins/documents/document_mutations.php';

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$adminBase = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents/index.php?mode=import_export';

function DOCUMENTS_csvAdminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function DOCUMENTS_csvAdminCategories()
{
    global $_TABLES;
    $items = array();
    $result = DB_query("SELECT cid, cat_name, cat_url FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC, cat_name ASC");
    while ($row = DB_fetchArray($result)) {
        if (is_array($row) && !empty($row['cid'])) {
            $items[] = $row;
        }
    }
    return $items;
}

$action = isset($_REQUEST['csv_action']) && !is_array($_REQUEST['csv_action'])
    ? trim((string) $_REQUEST['csv_action']) : '';

if ($action === 'export' || $action === 'template') {
    $categoryId = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
    if ($categoryId <= 0) {
        echo COM_refresh($adminBase);
        exit;
    }
    $category = DOCUMENTS_csvCategory($categoryId);
    if (empty($category)) {
        echo COM_refresh($adminBase);
        exit;
    }
    $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $category['cat_url']);
    $filename = 'documents-' . ($action === 'template' ? 'template-' : '') . $slug . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    DOCUMENTS_csvWriteExport($categoryId, $action === 'template');
    exit;
}

$messages = array();
$errors = array();
$report = null;
$parsed = null;
$uploadToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'analyze') {
    if (!SEC_checkToken()) {
        $errors[] = $isFrench ? 'Jeton de sécurité invalide.' : 'Invalid security token.';
    } elseif (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file']) || empty($_FILES['csv_file']['tmp_name'])) {
        $errors[] = $isFrench ? 'Sélectionnez un fichier CSV.' : 'Select a CSV file.';
    } else {
        $name = isset($_FILES['csv_file']['name']) ? (string) $_FILES['csv_file']['name'] : '';
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $errors[] = $isFrench ? 'Le fichier doit avoir l’extension .csv.' : 'The file must use the .csv extension.';
        } elseif (!is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $errors[] = $isFrench ? 'Téléversement CSV invalide.' : 'Invalid CSV upload.';
        } else {
            $parsed = DOCUMENTS_csvParse($_FILES['csv_file']['tmp_name']);
            $report = DOCUMENTS_csvAnalyze($parsed);
            $errors = array_merge($errors, $report['errors']);
            if (empty($errors)) {
                $dataDir = isset($_DOCUMENTS_CONF['path_data']) ? rtrim((string) $_DOCUMENTS_CONF['path_data'], "/\\") . DIRECTORY_SEPARATOR : '';
                if ($dataDir !== '' && DOCUMENTS_ensureWritableDirectory($dataDir, 'data')) {
                    $uploadToken = sha1(uniqid('', true) . mt_rand());
                    $target = $dataDir . 'import-' . $uploadToken . '.csv';
                    if (!@move_uploaded_file($_FILES['csv_file']['tmp_name'], $target)) {
                        $errors[] = $isFrench ? 'Impossible de préparer le fichier pour l’import.' : 'Unable to prepare the file for import.';
                        $uploadToken = '';
                    }
                } else {
                    $errors[] = $isFrench ? 'Le dossier de données Documents n’est pas accessible en écriture.' : 'Documents data directory is not writable.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'import') {
    if (!SEC_checkToken()) {
        $errors[] = $isFrench ? 'Jeton de sécurité invalide.' : 'Invalid security token.';
    } else {
        $token = isset($_POST['upload_token']) && !is_array($_POST['upload_token']) ? preg_replace('/[^a-f0-9]/', '', (string) $_POST['upload_token']) : '';
        $dataDir = isset($_DOCUMENTS_CONF['path_data']) ? rtrim((string) $_DOCUMENTS_CONF['path_data'], "/\\") . DIRECTORY_SEPARATOR : '';
        $path = ($token !== '' && $dataDir !== '') ? $dataDir . 'import-' . $token . '.csv' : '';
        if ($path === '' || !is_file($path)) {
            $errors[] = $isFrench ? 'Le fichier analysé n’est plus disponible.' : 'The analyzed file is no longer available.';
        } else {
            $parsed = DOCUMENTS_csvParse($path);
            $updateExisting = !empty($_POST['update_existing']);
            $importHits = !empty($_POST['import_hits']);
            list($ok, $stats, $importMessages, $importErrors) = DOCUMENTS_csvImport($parsed, $updateExisting, $importHits);
            $messages = array_merge($messages, $importMessages);
            $errors = array_merge($errors, $importErrors);
            $messages[] = ($isFrench ? 'Créés : ' : 'Created: ') . (int) $stats['created']
                . ' · ' . ($isFrench ? 'mis à jour : ' : 'updated: ') . (int) $stats['updated']
                . ' · ' . ($isFrench ? 'ignorés : ' : 'skipped: ') . (int) $stats['skipped'];
            @unlink($path);
        }
    }
}

$categories = DOCUMENTS_csvAdminCategories();
$pageTitle = $isFrench ? 'Import / Export CSV' : 'CSV Import / Export';
$content = '<main class="documents-admin-page">'
    . DOCUMENTS_adminNavigation('import_export')
    . '<header class="documents-admin-page__header"><h1>' . DOCUMENTS_csvAdminEscape($pageTitle) . '</h1>'
    . '<p class="documents-admin-page__lead">'
    . DOCUMENTS_csvAdminEscape($isFrench
        ? 'Exportez les documents d’une catégorie vers un tableur, modifiez-les puis réimportez le CSV sur ce site ou sur une installation Documents vierge.'
        : 'Export a category to a spreadsheet, edit it, then import the CSV back into this site or into a fresh Documents installation.')
    . '</p></header><div class="documents-admin-primary-content">';

if (!empty($messages)) {
    $content .= '<div class="documents-admin-notice"><ul>';
    foreach ($messages as $message) {
        $content .= '<li>' . DOCUMENTS_csvAdminEscape($message) . '</li>';
    }
    $content .= '</ul></div>';
}
if (!empty($errors)) {
    $content .= '<div class="documents-admin-notice documents-admin-notice--error"><ul>';
    foreach ($errors as $error) {
        $content .= '<li>' . DOCUMENTS_csvAdminEscape($error) . '</li>';
    }
    $content .= '</ul></div>';
}

$content .= '<section class="documents-admin-section"><h2>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Exporter une catégorie' : 'Export a category') . '</h2>';
if (empty($categories)) {
    $content .= '<p>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Aucune catégorie disponible.' : 'No categories available.') . '</p>';
} else {
    $content .= '<div class="documents-admin-table-wrap"><table class="documents-admin-table"><thead><tr><th>'
        . DOCUMENTS_csvAdminEscape($isFrench ? 'Catégorie' : 'Category') . '</th><th>'
        . DOCUMENTS_csvAdminEscape($isFrench ? 'Actions' : 'Actions') . '</th></tr></thead><tbody>';
    foreach ($categories as $category) {
        $cid = (int) $category['cid'];
        $content .= '<tr><td><strong>' . DOCUMENTS_csvAdminEscape(stripslashes((string) $category['cat_name'])) . '</strong><div class="documents-admin-muted">/'
            . DOCUMENTS_csvAdminEscape($category['cat_url']) . '</div></td><td>'
            . '<a class="documents-admin-button documents-admin-button--primary" href="' . DOCUMENTS_csvAdminEscape($adminBase . '&csv_action=export&cid=' . $cid) . '">'
            . DOCUMENTS_csvAdminEscape($isFrench ? 'Exporter le CSV' : 'Export CSV') . '</a> '
            . '<a class="documents-admin-button" href="' . DOCUMENTS_csvAdminEscape($adminBase . '&csv_action=template&cid=' . $cid) . '">'
            . DOCUMENTS_csvAdminEscape($isFrench ? 'Modèle vide' : 'Blank template') . '</a></td></tr>';
    }
    $content .= '</tbody></table></div>';
}
$content .= '</section>';

$content .= '<section class="documents-admin-section"><h2>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Importer un CSV' : 'Import a CSV') . '</h2>'
    . '<form method="post" enctype="multipart/form-data" action="' . DOCUMENTS_csvAdminEscape($adminBase) . '">'
    . '<input type="hidden" name="csv_action" value="analyze">'
    . '<input type="hidden" name="' . CSRF_TOKEN . '" value="' . SEC_createToken() . '">'
    . '<p><label><strong>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Fichier CSV' : 'CSV file') . '</strong><br>'
    . '<input type="file" name="csv_file" accept=".csv,text/csv" required></label></p>'
    . '<p><button class="documents-admin-button documents-admin-button--primary" type="submit">'
    . DOCUMENTS_csvAdminEscape($isFrench ? 'Analyser le fichier' : 'Analyze file') . '</button></p></form>';

if ($report !== null && empty($errors) && $uploadToken !== '') {
    $content .= '<div class="documents-admin-card"><h3>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Résultat de l’analyse' : 'Analysis result') . '</h3><ul>'
        . '<li>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Lignes de documents : ' : 'Document rows: ') . (int) $report['rows'] . '</li>'
        . '<li>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Nouveaux : ' : 'New: ') . (int) $report['new'] . '</li>'
        . '<li>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Déjà présents : ' : 'Existing: ') . (int) $report['existing'] . '</li>'
        . '<li>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Images absentes localement : ' : 'Images missing locally: ') . count($report['missing_images']) . '</li></ul>';
    if (!empty($report['missing_images'])) {
        $content .= '<p class="documents-admin-muted">' . DOCUMENTS_csvAdminEscape(implode(', ', $report['missing_images'])) . '</p>';
    }
    $content .= '<form method="post" action="' . DOCUMENTS_csvAdminEscape($adminBase) . '">'
        . '<input type="hidden" name="csv_action" value="import">'
        . '<input type="hidden" name="upload_token" value="' . DOCUMENTS_csvAdminEscape($uploadToken) . '">'
        . '<input type="hidden" name="' . CSRF_TOKEN . '" value="' . SEC_createToken() . '">'
        . '<p><label><input type="checkbox" name="update_existing" value="1" checked> '
        . DOCUMENTS_csvAdminEscape($isFrench ? 'Mettre à jour les documents existants ayant le même doc_url' : 'Update existing documents with the same doc_url') . '</label></p>'
        . '<p><label><input type="checkbox" name="import_hits" value="1"> '
        . DOCUMENTS_csvAdminEscape($isFrench ? 'Importer aussi les compteurs de consultations' : 'Also import hit counters') . '</label></p>'
        . '<p><button class="documents-admin-button documents-admin-button--primary" type="submit">'
        . DOCUMENTS_csvAdminEscape($isFrench ? 'Importer les documents' : 'Import documents') . '</button></p></form></div>';
}
$content .= '</section>';

$content .= '<section class="documents-admin-section"><h2>' . DOCUMENTS_csvAdminEscape($isFrench ? 'Mode d’emploi' : 'How to use it') . '</h2>';
if ($isFrench) {
    $content .= '<ol>'
        . '<li><strong>Choisissez une catégorie et exportez son CSV.</strong> Le fichier contient les métadonnées de la catégorie, la définition des champs, les groupes de choix et les documents.</li>'
        . '<li><strong>Ouvrez le fichier dans LibreOffice Calc, Excel ou un autre tableur.</strong> Le séparateur est le point-virgule et l’encodage est UTF-8.</li>'
        . '<li><strong>Modifiez uniquement les lignes de documents.</strong> Les lignes commençant par <code>#documents-format</code>, <code>#category</code>, <code>#field</code> et <code>#select</code> décrivent la structure nécessaire pour un import sur un site vierge.</li>'
        . '<li><strong>Conservez les noms de colonnes.</strong> Les champs sont identifiés par leur <code>var_name</code>, la catégorie par <code>cat_url</code> et les documents par <code>doc_url</code>. Les identifiants SQL locaux ne sont pas utilisés.</li>'
        . '<li><strong>Pour créer un document dans le tableur, ajoutez une ligne.</strong> Vous pouvez laisser <code>doc_url</code> vide : Documents en générera un à partir du premier champ de la catégorie.</li>'
        . '<li><strong>Pour réimporter sur le site d’origine,</strong> gardez <code>doc_url</code> afin que les lignes existantes soient reconnues et mises à jour.</li>'
        . '<li><strong>Pour importer sur un site vierge,</strong> le plugin recrée automatiquement la catégorie, les champs et les listes de sélection absents avant de créer les documents.</li>'
        . '<li><strong>Les images ne sont pas transportées par le CSV.</strong> Le CSV conserve leur nom de fichier. Sur le site d’origine, une cellule image vide conserve l’image actuelle. Sur un site vierge, copiez séparément les fichiers image dans le dossier Documents ; l’analyse signale les fichiers absents sans bloquer les autres données.</li>'
        . '<li><strong>Les utilisateurs et groupes sont recherchés par nom.</strong> S’ils n’existent pas sur le site cible, le propriétaire devient l’administrateur qui réalise l’import et le groupe Documents Admin est utilisé.</li>'
        . '<li><strong>Analysez toujours le CSV avant l’import.</strong> Aucun document n’est modifié pendant cette étape. Vous voyez le nombre de créations, de mises à jour potentielles et les images manquantes.</li>'
        . '</ol>'
        . '<p><strong>Conseil :</strong> utilisez « Modèle vide » pour préparer de nouveaux documents dans Calc sans avoir à construire les colonnes manuellement.</p>';
} else {
    $content .= '<ol>'
        . '<li><strong>Choose a category and export its CSV.</strong> The file contains category metadata, field definitions, selection groups and documents.</li>'
        . '<li><strong>Open it in LibreOffice Calc, Excel or another spreadsheet.</strong> The delimiter is a semicolon and encoding is UTF-8.</li>'
        . '<li><strong>Edit document rows only.</strong> Lines starting with <code>#documents-format</code>, <code>#category</code>, <code>#field</code> and <code>#select</code> describe the portable structure.</li>'
        . '<li><strong>Keep column names unchanged.</strong> Fields use <code>var_name</code>, categories use <code>cat_url</code> and documents use <code>doc_url</code>.</li>'
        . '<li><strong>Add a row to create a document.</strong> Leave <code>doc_url</code> empty to generate one automatically.</li>'
        . '<li><strong>Keep doc_url when reimporting into the original site</strong> so existing documents are updated.</li>'
        . '<li><strong>On a fresh site,</strong> missing categories, fields and selection groups are created automatically.</li>'
        . '<li><strong>Images are not physically transported by CSV.</strong> Only filenames are stored. Missing image files are reported during analysis.</li>'
        . '<li><strong>Users and groups are matched by name.</strong> Missing owners fall back to the importing administrator.</li>'
        . '<li><strong>Always analyze before importing.</strong> The analysis step does not modify Documents data.</li>'
        . '</ol>';
}
$content .= '</section></div></main>';

$content = DOCUMENTS_wrapBlock($content, 'admin', 'import_export');
COM_output(COM_createHTMLDocument($content, array('pagetitle' => $pageTitle)));
