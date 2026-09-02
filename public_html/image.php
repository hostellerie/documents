<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | image.php                                                                 |
// |                                                                           |
// | Access-controlled local image preview endpoint with persistent caching.   |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// +---------------------------------------------------------------------------+

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

if (!isset($_DOCUMENTS_CONF) || !is_array($_DOCUMENTS_CONF)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

require_once $_CONF['path'] . 'plugins/documents/runtime.php';
require_once $_CONF['path'] . 'plugins/documents/include_compat.php';

function DOCUMENTS_imageError($status)
{
    $messages = array(
        400 => 'Bad Request',
        404 => 'Not Found',
        415 => 'Unsupported Media Type',
        500 => 'Internal Server Error'
    );

    $text = isset($messages[$status]) ? $messages[$status] : 'Error';
    header('HTTP/1.1 ' . $status . ' ' . $text);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: private, no-store, max-age=0');
    echo $text;
    exit;
}

function DOCUMENTS_imageCacheHeaders($path)
{
    $mtime = @filemtime($path);
    $size = @filesize($path);
    $etag = '"' . sha1($path . '|' . (string) $mtime . '|' . (string) $size) . '"';

    /* Keep access-controlled images private. no-cache allows the browser to
     * retain the bytes, but it must revalidate with this endpoint before reuse. */
    header('Cache-Control: private, no-cache, max-age=0, must-revalidate');
    header('ETag: ' . $etag);
    if ($mtime !== false && $mtime > 0) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    }

    $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH'])
        ? trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) : '';
    if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }

    $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
        ? strtotime((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
    if ($ifNoneMatch === '' && $mtime !== false && $ifModifiedSince !== false
        && $ifModifiedSince >= $mtime) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}

function DOCUMENTS_sendOriginalImage($path, $mime)
{
    DOCUMENTS_imageCacheHeaders($path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function DOCUMENTS_previewExtension($mime)
{
    switch ($mime) {
        case 'image/jpeg':
            return '.jpg';
        case 'image/png':
            return '.png';
        case 'image/gif':
            return '.gif';
        case 'image/webp':
            return '.webp';
    }

    return '';
}

function DOCUMENTS_writePreviewImage($image, $path, $mime)
{
    switch ($mime) {
        case 'image/jpeg':
            return imagejpeg($image, $path, 82);
        case 'image/png':
            return imagepng($image, $path, 7);
        case 'image/gif':
            return imagegif($image, $path);
        case 'image/webp':
            return function_exists('imagewebp') ? imagewebp($image, $path, 82) : false;
    }

    return false;
}

function DOCUMENTS_outputPreviewImage($image, $mime)
{
    header('Content-Type: ' . $mime);
    header('Cache-Control: private, no-store, max-age=0');

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($image, null, 82);
            break;
        case 'image/png':
            imagepng($image, null, 7);
            break;
        case 'image/gif':
            imagegif($image);
            break;
        case 'image/webp':
            imagewebp($image, null, 82);
            break;
    }
}

function DOCUMENTS_pruneStalePreviews($sourceKey, $mtime)
{
    $directory = DOCUMENTS_previewDirectory();
    if ($directory === '' || !is_dir($directory)) {
        return;
    }

    $currentPrefix = $sourceKey . '-' . (string) $mtime . '-';
    $matches = glob($directory . $sourceKey . '-*');
    if (!is_array($matches)) {
        return;
    }

    foreach ($matches as $path) {
        $filename = basename($path);
        if (strpos($filename, $currentPrefix) !== 0 && is_file($path)) {
            @unlink($path);
        }
    }
}

function DOCUMENTS_canViewImageReference($filename)
{
    global $_TABLES;

    $filename = basename((string) $filename);
    if ($filename === '') {
        return false;
    }

    $filenameSql = DB_escapeString($filename);
    $result = DB_query(
        "SELECT DISTINCT d.active, d.owner_id, d.group_id, "
        . "d.perm_owner, d.perm_group, d.perm_members, d.perm_anon "
        . "FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_docs']} AS d ON d.doc_url=v.doc_url "
        . "WHERE f.f_type='image' AND v.v_value='{$filenameSql}'"
    );

    while ($document = DB_fetchArray($result)) {
        if (DOCUMENTS_canViewDocument($document, 2)) {
            return true;
        }
    }

    return false;
}

$src = isset($_GET['src']) ? trim($_GET['src']) : '';
$width = isset($_GET['w']) ? (int) $_GET['w'] : 0;
$height = isset($_GET['h']) ? (int) $_GET['h'] : 0;

if ($src === '') {
    DOCUMENTS_imageError(400);
}

$allowedUrl = isset($_DOCUMENTS_CONF['images_url'])
    ? rtrim($_DOCUMENTS_CONF['images_url'], '/') . '/'
    : '';

if ($allowedUrl !== '' && strpos($src, $allowedUrl) === 0) {
    $relative = substr($src, strlen($allowedUrl));
} else {
    $relative = $src;
}

$relative = rawurldecode($relative);

if (
    $relative === ''
    || basename($relative) !== $relative
    || strpos($relative, '..') !== false
    || strpos($relative, '/') !== false
    || strpos($relative, '\\') !== false
) {
    DOCUMENTS_imageError(404);
}

if (!DOCUMENTS_canViewImageReference($relative)) {
    DOCUMENTS_imageError(404);
}

$sourcePath = rtrim($_DOCUMENTS_CONF['path_images'], '/\\')
    . DIRECTORY_SEPARATOR . $relative;

if (!is_file($sourcePath) || !is_readable($sourcePath)) {
    DOCUMENTS_imageError(404);
}

$imageInfo = @getimagesize($sourcePath);
if (
    $imageInfo === false
    || empty($imageInfo[0])
    || empty($imageInfo[1])
    || empty($imageInfo['mime'])
) {
    DOCUMENTS_imageError(415);
}

$sourceWidth = (int) $imageInfo[0];
$sourceHeight = (int) $imageInfo[1];
$mime = $imageInfo['mime'];
$allowedMime = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');

if (!in_array($mime, $allowedMime, true)) {
    DOCUMENTS_imageError(415);
}

$maxPreviewDimension = 1600;
if (
    $width < 0
    || $height < 0
    || $width > $maxPreviewDimension
    || $height > $maxPreviewDimension
) {
    DOCUMENTS_imageError(400);
}

if ($width === 0 && $height === 0) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

if ($width === 0) {
    $width = (int) round($sourceWidth * ($height / $sourceHeight));
}
if ($height === 0) {
    $height = (int) round($sourceHeight * ($width / $sourceWidth));
}

if ($width < 1 || $height < 1) {
    DOCUMENTS_imageError(400);
}

$ratio = min($width / $sourceWidth, $height / $sourceHeight, 1);
$targetWidth = max(1, (int) round($sourceWidth * $ratio));
$targetHeight = max(1, (int) round($sourceHeight * $ratio));

if ($targetWidth === $sourceWidth && $targetHeight === $sourceHeight) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

$previewPath = '';
$extension = DOCUMENTS_previewExtension($mime);
if ($extension !== '' && DOCUMENTS_ensurePreviewDirectory()) {
    $mtime = @filemtime($sourcePath);
    $sourceKey = sha1($relative);
    DOCUMENTS_pruneStalePreviews($sourceKey, $mtime);

    $cacheKey = $sourceKey . '-'
        . (string) $mtime . '-'
        . $targetWidth . 'x' . $targetHeight;
    $previewPath = DOCUMENTS_previewDirectory() . $cacheKey . $extension;

    if (is_file($previewPath) && is_readable($previewPath)) {
        DOCUMENTS_sendOriginalImage($previewPath, $mime);
    }
}

if (!function_exists('imagecreatetruecolor')) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

switch ($mime) {
    case 'image/jpeg':
        if (!function_exists('imagecreatefromjpeg')) {
            DOCUMENTS_sendOriginalImage($sourcePath, $mime);
        }
        $sourceImage = @imagecreatefromjpeg($sourcePath);
        break;

    case 'image/png':
        if (!function_exists('imagecreatefrompng')) {
            DOCUMENTS_sendOriginalImage($sourcePath, $mime);
        }
        $sourceImage = @imagecreatefrompng($sourcePath);
        break;

    case 'image/gif':
        if (!function_exists('imagecreatefromgif')) {
            DOCUMENTS_sendOriginalImage($sourcePath, $mime);
        }
        $sourceImage = @imagecreatefromgif($sourcePath);
        break;

    case 'image/webp':
        if (!function_exists('imagecreatefromwebp')) {
            DOCUMENTS_sendOriginalImage($sourcePath, $mime);
        }
        $sourceImage = @imagecreatefromwebp($sourcePath);
        break;

    default:
        DOCUMENTS_imageError(415);
}

if (!$sourceImage) {
    DOCUMENTS_imageError(415);
}

$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
if (!$targetImage) {
    imagedestroy($sourceImage);
    DOCUMENTS_imageError(500);
}

if ($mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp') {
    imagealphablending($targetImage, false);
    imagesavealpha($targetImage, true);
    $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
    imagefilledrectangle(
        $targetImage,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $transparent
    );
}

if (!imagecopyresampled(
    $targetImage,
    $sourceImage,
    0,
    0,
    0,
    0,
    $targetWidth,
    $targetHeight,
    $sourceWidth,
    $sourceHeight
)) {
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    DOCUMENTS_imageError(500);
}

if ($previewPath !== '' && DOCUMENTS_writePreviewImage($targetImage, $previewPath, $mime)) {
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    DOCUMENTS_sendOriginalImage($previewPath, $mime);
}

DOCUMENTS_outputPreviewImage($targetImage, $mime);
imagedestroy($sourceImage);
imagedestroy($targetImage);
exit;
