<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | include_compat.php                                                        |
// |                                                                           |
// | Compatibility helpers shared by the 1.1.x stabilization line.            |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+

if (strpos(strtolower(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''), 'include_compat.php') !== false) {
    die('This file can not be used on its own.');
}

if (!defined('DOCUMENTS_STATUS_INACTIVE')) {
    define('DOCUMENTS_STATUS_INACTIVE', 0);
}
if (!defined('DOCUMENTS_STATUS_ACTIVE')) {
    define('DOCUMENTS_STATUS_ACTIVE', 1);
}
if (!defined('DOCUMENTS_STATUS_DRAFT')) {
    define('DOCUMENTS_STATUS_DRAFT', 2);
}
if (!defined('DOCUMENTS_STATUS_SUBMISSION')) {
    define('DOCUMENTS_STATUS_SUBMISSION', 3);
}

/**
 * Return a request value without raising undefined-index notices.
 *
 * @param array  $source  Request source array
 * @param string $key     Key to read
 * @param mixed  $default Default value
 * @return mixed
 */
function DOCUMENTS_requestValue($source, $key, $default = '')
{
    return (is_array($source) && isset($source[$key])) ? $source[$key] : $default;
}

/**
 * Convert http/https URLs in plain text to safe links.
 *
 * Replaces the historical preg_replace /e implementation, which was removed
 * from PHP 7.0. The callback syntax remains compatible with PHP 5.6.
 *
 * @param string $content Text/HTML fragment
 * @return string
 */
function DOCUMENTS_linkifyUrls($content)
{
    return preg_replace_callback(
        '~https?://[^\s<)]+~i',
        'DOCUMENTS_linkifyUrlCallback',
        $content
    );
}

/**
 * preg_replace_callback callback for DOCUMENTS_linkifyUrls().
 *
 * @param array $matches Regex matches
 * @return string
 */
function DOCUMENTS_linkifyUrlCallback($matches)
{
    $url = isset($matches[0]) ? $matches[0] : '';
    if ($url === '') {
        return '';
    }

    $label = (strlen($url) >= 50) ? substr($url, 0, 50) . '...' : $url;
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" title="'
        . $safeUrl . '">' . $safeLabel . '</a>';
}

/**
 * Validate a custom template directory name.
 *
 * Only one directory component is accepted. This keeps custom templates rooted
 * below the Documents data directory and blocks ../ traversal.
 *
 * @param string $template Template directory name
 * @return string Empty string when invalid
 */
function DOCUMENTS_templateName($template)
{
    $template = trim((string) $template);
    if ($template === '' || basename($template) !== $template || strpos($template, '..') !== false) {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9._-]+$/', $template)) {
        return '';
    }

    return $template;
}

/**
 * Return the new multisite-safe custom template directory.
 *
 * This helper always points to the new persistent location and must be used for
 * future writes. It never redirects writes to the legacy data_documents path.
 *
 * @param string $template Template directory name
 * @return string Empty string when invalid
 */
function DOCUMENTS_customTemplateDir($template)
{
    $template = DOCUMENTS_templateName($template);
    if ($template === '') {
        return '';
    }

    $base = function_exists('DOCUMENTS_dataDir') ? DOCUMENTS_dataDir() : '';
    if ($base === '') {
        return '';
    }

    return rtrim($base, "/\\") . DIRECTORY_SEPARATOR
        . 'templates' . DIRECTORY_SEPARATOR
        . $template . DIRECTORY_SEPARATOR;
}

/**
 * Resolve a custom template directory for reading during migration.
 *
 * The new multisite-safe location is preferred. Until the 1.1.7 migration has
 * moved existing files, the historical data_documents directory is accepted as
 * a read-only fallback. New writes must continue to use
 * DOCUMENTS_customTemplateDir().
 *
 * @param string $template Template directory name
 * @return string Empty string when no readable template directory exists
 */
function DOCUMENTS_customTemplateReadDir($template)
{
    $template = DOCUMENTS_templateName($template);
    if ($template === '') {
        return '';
    }

    $newDir = DOCUMENTS_customTemplateDir($template);
    if ($newDir !== '' && is_dir($newDir)) {
        return $newDir;
    }

    $legacyBase = function_exists('DOCUMENTS_legacyDataDir') ? DOCUMENTS_legacyDataDir() : '';
    if ($legacyBase !== '') {
        $legacyDir = rtrim($legacyBase, "/\\") . DIRECTORY_SEPARATOR
            . 'templates' . DIRECTORY_SEPARATOR
            . $template . DIRECTORY_SEPARATOR;
        if (is_dir($legacyDir)) {
            return $legacyDir;
        }
    }

    return '';
}
