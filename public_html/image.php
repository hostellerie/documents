<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.1                                                    |
// +---------------------------------------------------------------------------+
// | image.php                                                                 |
// |                                                                           |
// | Local-only image preview endpoint.                                        |
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

/*
 * Compatibility:
 * - historical templates pass the complete public Documents image URL;
 * - 1.1.1+ code may pass only the stored filename.
 *
 * In both cases the result must resolve to one basename inside path_images.
 */
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

// Never upscale an image just to generate a preview.
$ratio = min($width / $sourceWidth, $height / $sourceHeight, 1);
$targetWidth = max(1, (int) round($sourceWidth * $ratio));
$targetHeight = max(1, (int) round($sourceHeight * $ratio));

if ($targetWidth === $sourceWidth && $targetHeight === $sourceHeight) {
    DOCUMENTS_sendOriginalImage($sourcePath, $mime);
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

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');

switch ($mime) {
    case 'image/jpeg':
        imagejpeg($targetImage, null, 90);
        break;
    case 'image/png':
        imagepng($targetImage, null, 6);
        break;
    case 'image/gif':
        imagegif($targetImage);
        break;
    case 'image/webp':
        imagewebp($targetImage, null, 90);
        break;
}

imagedestroy($sourceImage);
imagedestroy($targetImage);
exit;
