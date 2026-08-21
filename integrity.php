<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | integrity.php                                                             |
// |                                                                           |
// | Data-integrity helpers for slugs, relations and local image references.   |
// +---------------------------------------------------------------------------+

function DOCUMENTS_normalizeRouteSlug($value)
{
    $value = trim(rawurldecode((string) $value));
    $value = strip_tags($value);

    if ($value === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }
    }

    $value = strtolower($value);
    $value = str_replace('_', '-', $value);
    $value = preg_replace('/[^a-z0-9-]+/', '-', $value);
    $value = preg_replace('/-+/', '-', $value);

    return trim($value, '-');
}

function DOCUMENTS_categorySlugExists($slug, $excludeCid = 0)
{
    global $_TABLES;

    $slug = DOCUMENTS_normalizeRouteSlug($slug);
    if ($slug === '') {
        return false;
    }

    $slugSql = DB_escapeString($slug);
    $where = "cat_url='{$slugSql}'";
    if ((int) $excludeCid > 0) {
        $where .= ' AND cid<>' . (int) $excludeCid;
    }

    return DB_getItem($_TABLES['documents_cat'], 'cid', $where) !== '';
}

function DOCUMENTS_documentUrlExists($docUrl)
{
    global $_TABLES;

    $docUrl = trim((string) $docUrl);
    if ($docUrl === '') {
        return false;
    }

    $docUrlSql = DB_escapeString($docUrl);

    return DB_getItem($_TABLES['documents_docs'], 'did', "doc_url='{$docUrlSql}'") !== '';
}

function DOCUMENTS_uniqueDocumentUrl($title)
{
    global $_TABLES;

    $slug = DOCUMENTS_normalizeRouteSlug($title);
    if ($slug === '') {
        $slug = 'document';
    }

    $next = (int) DB_getItem($_TABLES['documents_docs'], 'MAX(did)', '1=1') + 1;
    if ($next < 1) {
        $next = 1;
    }

    do {
        $candidate = $next . '-' . $slug;
        $next++;
    } while (DOCUMENTS_documentUrlExists($candidate));

    return $candidate;
}

function DOCUMENTS_integrityCount($sql)
{
    $result = DB_query($sql);
    if (!$result) {
        return 0;
    }

    $row = DB_fetchArray($result);

    return (is_array($row) && isset($row['total'])) ? (int) $row['total'] : 0;
}

function DOCUMENTS_duplicateSlugs($table, $column)
{
    $duplicates = array();
    $result = DB_query(
        "SELECT {$column} AS slug, COUNT(*) AS total FROM {$table} "
        . "WHERE {$column}<>'' GROUP BY {$column} HAVING COUNT(*)>1 ORDER BY {$column}"
    );

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || !isset($row['slug'], $row['total'])) {
            continue;
        }

        $duplicates[] = array(
            'slug' => (string) $row['slug'],
            'count' => (int) $row['total']
        );
    }

    return $duplicates;
}

function DOCUMENTS_documentImageReferences($docUrl)
{
    global $_TABLES;

    $images = array();
    $docUrl = DB_escapeString((string) $docUrl);
    if ($docUrl === '') {
        return $images;
    }

    $result = DB_query(
        "SELECT v.field_id, v.v_value FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE v.doc_url='{$docUrl}' AND f.f_type='image' AND v.v_value<>''"
    );

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['v_value'])) {
            continue;
        }

        $filename = basename((string) $row['v_value']);
        if ($filename !== '') {
            $images[(int) $row['field_id']] = $filename;
        }
    }

    return $images;
}

function DOCUMENTS_fieldImageReferences($fieldId)
{
    global $_TABLES;

    $images = array();
    $fieldId = (int) $fieldId;
    if ($fieldId <= 0) {
        return $images;
    }

    $fieldType = DB_getItem($_TABLES['documents_fields'], 'f_type', 'fid=' . $fieldId);
    if ($fieldType !== 'image') {
        return $images;
    }

    $result = DB_query(
        "SELECT v_value FROM {$_TABLES['documents_values']} "
        . "WHERE field_id={$fieldId} AND v_value<>''"
    );

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['v_value'])) {
            continue;
        }
        $filename = basename((string) $row['v_value']);
        if ($filename !== '') {
            $images[$filename] = true;
        }
    }

    return array_keys($images);
}

function DOCUMENTS_cleanupReplacedImages($before, $docUrl)
{
    global $_DOCUMENTS_CONF;

    if (!is_array($before) || empty($before) || empty($_DOCUMENTS_CONF['path_images'])) {
        return 0;
    }

    $after = DOCUMENTS_documentImageReferences($docUrl);
    $removed = 0;
    $base = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR;

    foreach ($before as $fieldId => $oldFilename) {
        $newFilename = isset($after[$fieldId]) ? $after[$fieldId] : '';
        if ($newFilename === '' || $newFilename === $oldFilename) {
            continue;
        }

        $oldFilename = basename((string) $oldFilename);
        if ($oldFilename === '') {
            continue;
        }

        $path = $base . $oldFilename;
        if (is_file($path) && @unlink($path)) {
            $removed++;
        }
    }

    return $removed;
}

function DOCUMENTS_cleanupDeletedDocumentImages($docUrl, $images)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $docUrl = (string) $docUrl;
    if ($docUrl === '' || !is_array($images) || empty($images)
        || empty($_DOCUMENTS_CONF['path_images'])) {
        return 0;
    }

    $docUrlSql = DB_escapeString($docUrl);
    if (DB_getItem($_TABLES['documents_docs'], 'did', "doc_url='{$docUrlSql}'") !== '') {
        return 0;
    }
    if ((int) DB_count($_TABLES['documents_values'], 'doc_url', $docUrl) > 0) {
        return 0;
    }

    $base = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR;
    $removed = 0;
    foreach ($images as $filename) {
        $filename = basename((string) $filename);
        if ($filename === '') {
            continue;
        }
        $path = $base . $filename;
        if (is_file($path) && @unlink($path)) {
            $removed++;
        }
    }

    return $removed;
}

function DOCUMENTS_cleanupDeletedFieldImages($fieldId, $filenames)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $fieldId = (int) $fieldId;
    if ($fieldId <= 0 || !is_array($filenames) || empty($filenames)
        || empty($_DOCUMENTS_CONF['path_images'])) {
        return 0;
    }

    if (DB_getItem($_TABLES['documents_fields'], 'fid', 'fid=' . $fieldId) !== '') {
        return 0;
    }

    if ((int) DB_count($_TABLES['documents_values'], 'field_id', $fieldId) > 0) {
        return 0;
    }

    $base = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR;
    $removed = 0;
    foreach ($filenames as $filename) {
        $filename = basename((string) $filename);
        if ($filename === '') {
            continue;
        }
        $path = $base . $filename;
        if (is_file($path) && @unlink($path)) {
            $removed++;
        }
    }

    return $removed;
}

function DOCUMENTS_integrityReport()
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $report = array(
        'duplicate_category_slugs' => array(),
        'duplicate_document_slugs' => array(),
        'orphan_documents_without_values' => 0,
        'orphan_values_without_document' => 0,
        'orphan_values_without_field' => 0,
        'orphan_fields_without_category' => 0,
        'missing_image_files' => array(),
        'unreferenced_image_files' => array()
    );

    $report['duplicate_category_slugs'] = DOCUMENTS_duplicateSlugs(
        $_TABLES['documents_cat'],
        'cat_url'
    );
    $report['duplicate_document_slugs'] = DOCUMENTS_duplicateSlugs(
        $_TABLES['documents_docs'],
        'doc_url'
    );

    $report['orphan_documents_without_values'] = DOCUMENTS_integrityCount(
        "SELECT COUNT(*) AS total FROM {$_TABLES['documents_docs']} AS d "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "WHERE v.doc_url IS NULL"
    );

    $report['orphan_values_without_document'] = DOCUMENTS_integrityCount(
        "SELECT COUNT(*) AS total FROM {$_TABLES['documents_values']} AS v "
        . "LEFT JOIN {$_TABLES['documents_docs']} AS d ON d.doc_url=v.doc_url "
        . "WHERE d.did IS NULL"
    );

    $report['orphan_values_without_field'] = DOCUMENTS_integrityCount(
        "SELECT COUNT(*) AS total FROM {$_TABLES['documents_values']} AS v "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.fid IS NULL"
    );

    $report['orphan_fields_without_category'] = DOCUMENTS_integrityCount(
        "SELECT COUNT(*) AS total FROM {$_TABLES['documents_fields']} AS f "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE c.cid IS NULL"
    );

    $referencedImages = array();
    $result = DB_query(
        "SELECT v.v_value FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.f_type='image' AND v.v_value<>''"
    );

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['v_value'])) {
            continue;
        }

        $filename = basename((string) $row['v_value']);
        if ($filename === '') {
            continue;
        }

        $referencedImages[$filename] = true;

        if (isset($_DOCUMENTS_CONF['path_images'])) {
            $path = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\")
                . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($path)) {
                $report['missing_image_files'][] = $filename;
            }
        }
    }

    if (isset($_DOCUMENTS_CONF['path_images']) && is_dir($_DOCUMENTS_CONF['path_images'])) {
        $items = @scandir($_DOCUMENTS_CONF['path_images']);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || isset($referencedImages[$item])) {
                    continue;
                }

                $path = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\")
                    . DIRECTORY_SEPARATOR . $item;
                if (!is_file($path)) {
                    continue;
                }

                $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
                    $report['unreferenced_image_files'][] = $item;
                }
            }
        }
    }

    $report['missing_image_files'] = array_values(array_unique($report['missing_image_files']));
    sort($report['missing_image_files']);
    sort($report['unreferenced_image_files']);

    return $report;
}
