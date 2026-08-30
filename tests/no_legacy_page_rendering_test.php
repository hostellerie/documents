<?php

$root = dirname(__DIR__);
$forbidden = array(
    'COM_siteHeader(',
    'COM_siteFooter('
);
$failures = array();

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $path = $fileInfo->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

    if (strpos($relative, 'dist/') === 0 || strpos($relative, '.git/') === 0) {
        continue;
    }

    if (substr($relative, -4) !== '.php' && substr($relative, -4) !== '.inc') {
        continue;
    }

    if ($relative === 'tests/no_legacy_page_rendering_test.php') {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $failures[] = 'Unable to read ' . $relative;
        continue;
    }

    foreach ($forbidden as $needle) {
        if (strpos($content, $needle) !== false) {
            $failures[] = $relative . ' still uses deprecated page rendering API ' . $needle;
        }
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Legacy Geeklog page rendering API checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Legacy Geeklog page rendering API checks: PASS\n";
