<?php

/* Secure document image upload helpers for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'document_images.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_imageUploadRequestPresent($fieldId)
{
    $key = 'file' . (int) $fieldId;
    if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
        return false;
    }

    $file = $_FILES[$key];
    if (empty($file['name'])) {
        return false;
    }
    if (isset($file['error']) && (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    return true;
}

function DOCUMENTS_imageExistingValue($documentId, $fieldId)
{
    global $_TABLES;

    $documentId = trim((string) $documentId);
    $fieldId = (int) $fieldId;
    if ($documentId === '' || $fieldId <= 0) {
        return '';
    }

    $safeId = DB_escapeString($documentId);
    $value = DB_getItem(
        $_TABLES['documents_values'],
        'v_value',
        "doc_url='{$safeId}' AND field_id={$fieldId}"
    );

    return $value === '' ? '' : basename((string) $value);
}

function DOCUMENTS_imageDeleteFiles($filenames)
{
    global $_DOCUMENTS_CONF;

    if (!is_array($filenames) || empty($filenames) || empty($_DOCUMENTS_CONF['path_images'])) {
        return;
    }

    $base = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR;
    foreach ($filenames as $filename) {
        $filename = basename((string) $filename);
        if ($filename === '') {
            continue;
        }
        $path = $base . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
        if (function_exists('DOCUMENTS_removeImagePreviews')) {
            DOCUMENTS_removeImagePreviews($filename);
        }
    }
}

/**
 * Upload image fields without mutating database rows.
 *
 * @return array array(success, fieldId=>filename map, error message)
 */
function DOCUMENTS_uploadDocumentImages($documentId, $fields)
{
    global $_CONF, $_DOCUMENTS_CONF;

    $documentId = trim((string) $documentId);
    if ($documentId === '' || !is_array($fields)) {
        return array(false, array(), 'Invalid image upload request.');
    }

    $imageFields = array();
    $filenames = array();
    $inputNames = array();

    foreach ($fields as $field) {
        if (!is_array($field)
            || !isset($field['f_type'])
            || strtolower((string) $field['f_type']) !== 'image') {
            continue;
        }

        $fieldId = isset($field['fid']) ? (int) $field['fid'] : 0;
        if ($fieldId <= 0 || !DOCUMENTS_imageUploadRequestPresent($fieldId)) {
            continue;
        }

        $input = 'file' . $fieldId;
        $file = $_FILES[$input];
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
            return array(false, array(), 'Unsupported image extension.');
        }

        $baseName = preg_replace('/[^A-Za-z0-9._-]/', '-', $documentId . '-' . $fieldId);
        $baseName = trim((string) $baseName, '.-');
        if ($baseName === '') {
            return array(false, array(), 'Unable to create a safe image filename.');
        }

        $imageFields[] = $fieldId;
        $inputNames[] = $input;
        $filenames[] = $baseName . '.' . $extension;
    }

    if (empty($filenames)) {
        return array(true, array(), '');
    }

    if (function_exists('DOCUMENTS_ensureImageDirectory') && !DOCUMENTS_ensureImageDirectory()) {
        return array(false, array(), 'Documents image directory is unavailable.');
    }

    require_once $_CONF['path_system'] . 'classes/upload.class.php';
    $upload = new upload();

    if (isset($_CONF['debug_image_upload']) && $_CONF['debug_image_upload']) {
        $upload->setLogFile($_CONF['path'] . 'logs/error.log');
        $upload->setDebug(true);
    }

    $upload->setMaxFileUploads(20);
    if (!empty($_CONF['image_lib'])) {
        if ($_CONF['image_lib'] === 'imagemagick') {
            $upload->setMogrifyPath($_CONF['path_to_mogrify']);
        } elseif ($_CONF['image_lib'] === 'netpbm') {
            $upload->setNetPBM($_CONF['path_to_netpbm']);
        } elseif ($_CONF['image_lib'] === 'gdlib') {
            $upload->setGDLib();
        }
        $upload->setAutomaticResize(true);
        $upload->keepOriginalImage(false);
        if (isset($_CONF['jpeg_quality'])) {
            $upload->setJpegQuality($_CONF['jpeg_quality']);
        }
    }

    $upload->setAllowedMimeTypes(array(
        'image/gif' => '.gif',
        'image/jpeg' => '.jpg,.jpeg',
        'image/pjpeg' => '.jpg,.jpeg',
        'image/x-png' => '.png',
        'image/png' => '.png',
        'image/webp' => '.webp'
    ));

    if (!$upload->setPath($_DOCUMENTS_CONF['path_images'])) {
        return array(false, array(), strip_tags($upload->printErrors(false)));
    }

    $upload->setMaxDimensions(
        (int) $_DOCUMENTS_CONF['max_image_width'],
        (int) $_DOCUMENTS_CONF['max_image_height']
    );
    $upload->setMaxFileSize((int) $_DOCUMENTS_CONF['max_image_size']);
    $upload->setPerms('0644');
    $upload->setFileNames($filenames);
    $upload->uploadFiles();

    if ($upload->areErrors()) {
        DOCUMENTS_imageDeleteFiles($filenames);
        return array(false, array(), strip_tags($upload->printErrors(false)));
    }

    $result = array();
    foreach ($imageFields as $index => $fieldId) {
        if (isset($filenames[$index])) {
            $result[(int) $fieldId] = basename($filenames[$index]);
        }
    }

    return array(true, $result, '');
}
