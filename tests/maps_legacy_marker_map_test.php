<?php

$path = dirname(__DIR__) . '/maps_adapter.php';
$source = file_get_contents($path);
if ($source === false) {
    fwrite(STDERR, "Unable to read maps_adapter.php\n");
    exit(1);
}

$checks = array(
    "if (\$fieldId <= 0 || \$varName === '')",
    "if (\$mapId <= 0 && \$markerId === '')",
    "if (\$mapId > 0) {",
    "\$args['map_id'] = \$mapId;"
);

foreach ($checks as $check) {
    if (strpos($source, $check) === false) {
        fwrite(STDERR, "Missing legacy marker compatibility guard: {$check}\n");
        exit(1);
    }
}

if (strpos($source, "\$fieldId <= 0 || \$mapId <= 0 || \$varName === ''") !== false) {
    fwrite(STDERR, "Legacy map_id hard requirement is still present.\n");
    exit(1);
}

echo "Legacy Maps marker compatibility checks passed.\n";
