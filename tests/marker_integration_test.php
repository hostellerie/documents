<?php

/* Documents marker/Maps interoperability regression checks. PHP 5.6+. */

$root = dirname(__DIR__);
$failures = array();

$adapter = file_get_contents($root . '/maps_adapter.php');
$renderer = file_get_contents($root . '/public_document.php');
$delete = file_get_contents($root . '/document_delete.php');
$autoinstall = file_get_contents($root . '/autoinstall.php');

$files = array(
    'maps_adapter.php' => $adapter,
    'public_document.php' => $renderer,
    'document_delete.php' => $delete,
    'autoinstall.php' => $autoinstall
);

foreach ($files as $name => $content) {
    if ($content === false) {
        $failures[] = 'Unable to read ' . $name . '.';
    }
}

if ($adapter !== false) {
    if (strpos($adapter, "PLG_invokeService('maps', 'marker_save'") === false) {
        $failures[] = 'Maps marker persistence must use the marker_save service.';
    }
    if (strpos($adapter, 'DOCUMENTS_hasMaps()') === false) {
        $failures[] = 'Maps integration must remain optional and capability-checked.';
    }
}

if ($renderer !== false
    && strpos($renderer, "PLG_invokeService(\n        'maps',\n        'marker_render'") === false) {
    $failures[] = 'Maps marker rendering must use the marker_render service.';
}

if ($delete !== false
    && strpos($delete, 'DOCUMENTS_mapsDeactivateMarker($documentId, $markerId)') === false) {
    $failures[] = 'Document deletion must delegate marker withdrawal to Maps.';
}

$forbidden = array(
    'maps_markers',
    'maps_maps',
    "['maps_markers']",
    "['maps_maps']",
    'UPDATE gl_maps_',
    'INSERT INTO gl_maps_',
    'DELETE FROM gl_maps_'
);
foreach ($files as $name => $content) {
    if ($content === false) {
        continue;
    }
    foreach ($forbidden as $needle) {
        if (strpos($content, $needle) !== false) {
            $failures[] = $name . ' contains forbidden direct Maps database access: ' . $needle;
        }
    }
}

if ($autoinstall !== false) {
    if (strpos($autoinstall, "'documents_pics'") === false) {
        $failures[] = 'Documents own table declaration is incomplete.';
    }
    if (strpos($autoinstall, "'maps'") !== false
        || strpos($autoinstall, 'maps_markers') !== false
        || strpos($autoinstall, 'maps_maps') !== false) {
        $failures[] = 'Maps must not be an installation dependency or declared Documents table.';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents Maps interoperability checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents Maps interoperability checks: PASS\n";
