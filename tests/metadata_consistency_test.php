<?php

/* Documents 1.2.0 metadata and header consistency checks. */

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
$testing = DOCUMENTS_metaRead($root, 'TESTING.md', $failures);
$changelog = DOCUMENTS_metaRead($root, 'CHANGELOG.md', $failures);

$requiredMetadata = array(
    "'pi_version' => '1.2.0'" => 'Plugin version metadata is not 1.2.0.',
    "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')" => 'Minimum Geeklog version metadata is inconsistent.',
    "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')" => 'Maximum Geeklog version metadata is inconsistent.',
    "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')" => 'Minimum PHP version metadata is inconsistent.',
    "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')" => 'Maximum PHP version metadata is inconsistent.',
    "define('DOCUMENTS_SUPPORTED_DBMS', 'mysql')" => 'Supported database metadata is inconsistent.'
);

foreach ($requiredMetadata as $needle => $message) {
    if (strpos($autoinstall, $needle) === false) {
        $failures[] = $message;
    }
}

if (is_file($root . '/sql/mssql_install.php')) {
    $failures[] = 'MSSQL support must not be shipped with Documents 1.2.0.';
}
if (!is_file($root . '/sql/mysql_install.php')) {
    $failures[] = 'MySQL/MariaDB installation schema is missing.';
}

if (strpos($readme, 'Documents 1.2.0') === false) {
    $failures[] = 'README does not identify the Documents 1.2.0 development target.';
}
if (strpos($readme, 'Geeklog **2.1.1 through 2.2.2**') === false) {
    $failures[] = 'README compatibility range is inconsistent.';
}
if (strpos($readme, 'PHP **5.6 through 8.1**') === false) {
    $failures[] = 'README PHP compatibility range is inconsistent.';
}
if (strpos($readme, 'MySQL/MariaDB') === false) {
    $failures[] = 'README does not document the supported database family.';
}
if (strpos($testing, '# Documents 1.2.0 release-candidate test matrix') === false) {
    $failures[] = 'TESTING.md is not aligned with the 1.2.0 release candidate.';
}
if (strpos($changelog, '## 1.2.0 —') === false) {
    $failures[] = 'CHANGELOG does not contain the 1.2.0 release section.';
}

/* Removed historical controllers must stay removed. New source files should
 * not silently reintroduce obsolete 1.1.0/1.1.1/1.1.2 identity headers. */
if (is_file($root . '/include_edit.php')) {
    $failures[] = 'Removed include_edit.php legacy controller has reappeared.';
}
if (is_file($root . '/include_html.php')) {
    $failures[] = 'Removed include_html.php legacy controller has reappeared.';
}

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

    if (strpos($path, 'test-artifacts/') === 0 || strpos($path, 'tests/') === 0) {
        continue;
    }

    $content = file_get_contents($fileInfo->getPathname());
    if ($content === false) {
        $failures[] = 'Unable to inspect source header: ' . $path;
        continue;
    }

    if (preg_match('/Documents Plugin 1\.1\.(?:0|1|2)(?![0-9])/', $content)) {
        $failures[] = 'Obsolete pre-modernization plugin header remains in ' . $path . '.';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents metadata consistency checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents metadata consistency checks: PASS\n";
