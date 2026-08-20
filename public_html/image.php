<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.1                                                    |
// +---------------------------------------------------------------------------+
// | image.php                                                                 |
// |                                                                           |
// | Local image preview endpoint.                                             |
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

if (!isset($_PLUGINS) || !in_array('documents', $_PLUGINS)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

if (!isset($_DOCUMENTS_CONF) || !is_array($_DOCUMENTS_CONF)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

/**
 * Stop processing with an HTTP error.
 *
 * @param int $status HTTP status code
 */
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
    echo $text;
    exit;
}

/**
 * Send an existing local image without resizing.
 *
 * @param string $path Local image path
 * @param string $mime MIME type
 */
function DOCUMENTS_sendOriginalImage($path, $mime)
{
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
}

$src = isset($_GET['src']) ? trim($_GET['src']) : '';
$width = isset($_GET['w']) ? (int) $_GET['w'] : 0;
$height = isset($_GET['h']) ? (int) $_GET['h'] : 0;

if ($src === '') {
    DOCUMENTS_imageError(400);
}

// Legacy Documents templates pass a full public URL. Only accept URLs from the
// Documents item image directory; remote images are never fetched.
$allowedUrl = isset($_DOCUMENTS_CONF['images_url']) ? rtrim($_DOCUMENTS_CONF['images_url'], '/') . '/' : '';
if ($allowedUrl === '' || strpos($src, $allowedUrl) !== 0) {
    DOCUMENTS_imageError(404);
}

$relative = substr($src, strlen($allowedUrl));
$relative = rawurldecode($relative);

// Documents 1.1.x image fields store a filename, not an arbitrary path.
if ($relative === '' || basename($relative) !== $relative || strpos($relative, '..') !== false) {
    DOCUMENTS_imageError(404);
}

$sourcePath = rtrim($_DOCUMENTS_CONF['path_images'], '/\\') . DIRECTORY_SEPARATOR . $relative;
if (!is_file($sourcePath) || !is_readable($sourcePath)) {
    DOCUMENTS_imageError(404);
}

$imageInfo = @getimagesize($sourcePath);
if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1]) || empty($imageInfo['mime'])) {
    DOCUMENTS_imageError(415);
}

$sourceWidth = (int) $imageInfo[0];
$sourceHeight = (int) $imageInfo[1];
$mime = $imageInfo['mime'];
$allowedMime = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');

if (!in_array($mime, $allowedMime, true)) {
    DOCUMENTS_imageError(415);
}

// Keep previews bounded. Existing templates currently request w=450.
$maxPreviewDimension = 1600;
if ($width < 0 || $height < 0 || $width > $maxPreviewDimension || $height > $maxPreviewDimension) {
    DOCUMENTS_imageError(400);
}

if ($width === 0 && $height === 0) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

if ($width === 0) {
    $width = (int) round($sourceWidth * ($height / $sourceHeight));
} elseif ($height === 0) {
    $height = (int) round($sourceHeight * ($width / $sourceWidth));
}

if ($width < 1 || $height < 1) {
    DOCUMENTS_imageError(400);
}

// Never upscale previews.
if ($width >= $sourceWidth && $height >= $sourceHeight) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

if (!function_exists('imagecreatetruecolor')) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

$sourceImage = false;
switch ($mime) {
    case 'image/jpeg':
        if (function_exists('imagecreatefromjpeg')) {
            $sourceImage = @imagecreatefromjpeg($sourcePath);
        }
        break;

    case 'image/png':
        if (function_exists('imagecreatefrompng')) {
            $sourceImage = @imagecreatefrompng($sourcePath);
        }
        break;

    case 'image/gif':
        if (function_exists('imagecreatefromgif')) {
            $sourceImage = @imagecreatefromgif($sourcePath);
        }
        break;

    case 'image/webp':
        if (function_exists('imagecreatefromwebp')) {
            $sourceImage = @imagecreatefromwebp($sourcePath);
        }
        break;
}

if ($sourceImage === false) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
}

$preview = imagecreatetruecolor($width, $height);
if ($preview === false) {
    imagedestroy($sourceImage);
    DOCUMENTS_imageError(500);
}

if ($mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp') {
    imagealphablending($preview, false);
    imagesavealpha($preview, true);
    $transparent = imagecolorallocatealpha($preview, 0, 0, 0, 127);
    imagefilledrectangle($preview, 0, 0, $width, $height, $transparent);
}

if (!imagecopyresampled(
    $preview,
    $sourceImage,
    0,
    0,
    0,
    0,
    $width,
    $height,
    $sourceWidth,
    $sourceHeight
)) {
    imagedestroy($preview);
    imagedestroy($sourceImage);
    DOCUMENTS_imageError(500);
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');

switch ($mime) {
    case 'image/jpeg':
        imagejpeg($preview, null, 88);
        break;

    case 'image/png':
        imagepng($preview, null, 6);
        break;

    case 'image/gif':
        imagegif($preview);
        break;

    case 'image/webp':
        if (function_exists('imagewebp')) {
            imagewebp($preview, null, 88);
        } else {
            imagedestroy($preview);
            imagedestroy($sourceImage);
            DOCUMENTS_sendOriginalImage($sourcePath, $mime);
        }
        break;
}

imagedestroy($preview);
imagedestroy($sourceImage);
exit;
