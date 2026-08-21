<?php

/* Documents 1.1.9 metadata and header consistency checks. */

$root = dirname(__DIR__);
$failures = array();

function DOCUMENTS_metaRead($root, $path, &$failures)
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        $failures[] = 'Missing required file: ' . $path;
        return '';
    }

    $content = file_get_contents($file);
    if ($content === false) {
        $failures[] = 'Unable to read required file: ' . $path;
        return '';
    }

    return $content;
}

$autoinstall = DOCUMENTS_metaRead($root, 'autoinstall.php', $failures);
$readme = DOCUMENTS_metaRead($root, 'README.md', $failures);
$changelog = DOCUMENTS_metaRead($root, 'CHANGELOG.md', $failures);

$requiredMetadata = array(
    "'pi_version'      => '1.1.9'" => 'Plugin version metadata is not 1.1.9.',
    "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')" => 'Minimum Geeklog version metadata is inconsistent.',
    "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')" => 'Maximum Geeklog version metadata is inconsistent.',
    "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')" => 'Minimum PHP version metadata is inconsistent.',
    "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')" => 'Maximum PHP version metadata is inconsistent.'
);

foreach ($requiredMetadata as $needle => $message) {
    if (strpos($autoinstall, $needle) === false) {
        $failures[] = $message;
    }
}

if (strpos($readme, '1.1.9') === false) {
    $failures[] = 'README does not identify the 1.1.9 release-candidate line.';
}
if (strpos($changelog, '## 1.1.9') === false) {
    $failures[] = 'CHANGELOG does not contain a 1.1.9 section.';
}

/*
 * Large historical files are still being cleaned up header-by-header. Keep an
 * explicit allow-list so no additional stale 1.1.2 header can appear unnoticed.
 */
$allowedLegacyHeaders = array(
    'functions.inc',
    'include_edit.php',
    'include_html.php',
    'integrity.php',
    'public_html/index.php'
);
sort($allowedLegacyHeaders);

$legacyHeadersFound = array();
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $extension = strtolower($fileInfo->getExtension());
    if ($extension !== 'php' && $extension !== 'inc') {
        continue;
    }

    $path = substr($fileInfo->getPathname(), strlen($root) + 1);
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

    if (strpos($path, 'test-artifacts/') === 0) {
        continue;
    }

    $content = file_get_contents($fileInfo->getPathname());
    if ($content === false) {
        $failures[] = 'Unable to inspect source header: ' . $path;
        continue;
    }

    if (strpos($content, 'Documents Plugin 1.1.2') !== false) {
        $legacyHeadersFound[] = $path;
    }

    if (strpos($content, 'Documents Plugin 1.1.1') !== false
        || strpos($content, 'Documents Plugin 1.1.0') !== false) {
        $failures[] = 'Unexpected older plugin header remains in ' . $path . '.';
    }
}

sort($legacyHeadersFound);
if ($legacyHeadersFound !== $allowedLegacyHeaders) {
    $failures[] = 'Stale 1.1.2 header inventory changed. Expected: '
        . implode(', ', $allowedLegacyHeaders) . '; found: '
        . implode(', ', $legacyHeadersFound) . '.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents metadata consistency checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents metadata consistency checks: PASS\n";
echo "Known legacy 1.1.2 headers: " . implode(', ', $legacyHeadersFound) . "\n";
