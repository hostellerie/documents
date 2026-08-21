<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | storage.php                                                               |
// |                                                                           |
// | Persistent storage creation and legacy migration helpers.                 |
// +---------------------------------------------------------------------------+

/**
 * Ensure the site-specific persistent Documents data directory exists.
 *
 * @return bool
 */
function DOCUMENTS_ensureDataDirectory()
{
    $target = DOCUMENTS_dataDir();
    if ($target === '') {
        return false;
    }

    if (!is_dir($target)) {
        if (!@mkdir($target, 0755, true) && !is_dir($target)) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents storage: unable to create data directory ' . $target);
            }
            return false;
        }
    }

    return is_writable($target);
}

/**
 * Copy one legacy directory tree without overwriting existing target files.
 *
 * Symlinks are deliberately ignored. Existing files are preserved so the
 * operation can be rerun safely and never replaces a newer migrated copy.
 *
 * @param string $source Source directory
 * @param string $target Target directory
 * @param array &$report Migration counters
 * @return bool
 */
function DOCUMENTS_copyLegacyTree($source, $target, &$report)
{
    $source = rtrim((string) $source, "/\\") . DIRECTORY_SEPARATOR;
    $target = rtrim((string) $target, "/\\") . DIRECTORY_SEPARATOR;

    if (!is_dir($source)) {
        return true;
    }

    if (!is_dir($target) && !@mkdir($target, 0755, true) && !is_dir($target)) {
        $report['errors']++;
        return false;
    }

    $items = @scandir($source);
    if (!is_array($items)) {
        $report['errors']++;
        return false;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $source . $item;
        $targetPath = $target . $item;

        if (is_link($sourcePath)) {
            $report['skipped']++;
            continue;
        }

        if (is_dir($sourcePath)) {
            DOCUMENTS_copyLegacyTree($sourcePath, $targetPath, $report);
            continue;
        }

        if (!is_file($sourcePath)) {
            $report['skipped']++;
            continue;
        }

        if (file_exists($targetPath)) {
            $report['skipped']++;
            continue;
        }

        if (@copy($sourcePath, $targetPath)) {
            $report['copied']++;
        } else {
            $report['errors']++;
        }
    }

    return $report['errors'] === 0;
}

/**
 * Migrate legacy data_documents content into the site-specific data directory.
 *
 * The migration is intentionally non-destructive and idempotent:
 * - the legacy directory is never deleted;
 * - existing target files are never overwritten;
 * - rerunning the migration only copies files still missing from the target.
 *
 * @return array Migration report
 */
function DOCUMENTS_migrateLegacyData()
{
    $report = array(
        'source_exists' => false,
        'target_ready' => false,
        'copied' => 0,
        'skipped' => 0,
        'errors' => 0
    );

    $source = DOCUMENTS_legacyDataDir();
    $target = DOCUMENTS_dataDir();

    if ($target === '' || !DOCUMENTS_ensureDataDirectory()) {
        $report['errors']++;
        return $report;
    }

    $report['target_ready'] = true;

    if ($source === '' || !is_dir($source)) {
        return $report;
    }

    $report['source_exists'] = true;
    DOCUMENTS_copyLegacyTree($source, $target, $report);

    return $report;
}
