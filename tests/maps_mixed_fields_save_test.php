<?php

/* Regression guard: legacy Maps categories may mix marker with radio/album/file fields. */

$root = dirname(__DIR__);
$source = file_get_contents($root . '/maps_adapter.php');

$checks = array(
    "return $hasMarker && DOCUMENTS_hasMaps();" => 'Maps support depends on marker + Maps availability only',
    "array('album', 'file', 'radio')" => 'legacy mixed-field rejection removed',
    "array('file', 'category', 'album')" => 'historical fields are preserved when absent from request',
    "array('select', 'radio')" => 'radio values receive the same option validation as selects'
);

$failed = array();

if (strpos($source, "return $hasMarker && DOCUMENTS_hasMaps();") === false) {
    $failed[] = $checks["return $hasMarker && DOCUMENTS_hasMaps();"];
}
if (strpos($source, "array('album', 'file', 'radio')") !== false) {
    $failed[] = $checks["array('album', 'file', 'radio')"];
}
if (strpos($source, "array('file', 'category', 'album')") === false) {
    $failed[] = $checks["array('file', 'category', 'album')"];
}
if (strpos($source, "array('select', 'radio')") === false) {
    $failed[] = $checks["array('select', 'radio')"];
}

if (!empty($failed)) {
    fwrite(STDERR, "Maps mixed-fields regression failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Maps mixed-fields regression checks passed.\n";
