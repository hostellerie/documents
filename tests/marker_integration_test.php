<?php

/* Documents marker/Maps integration regression checks. */

$root = dirname(__DIR__);
$failures = array();

$includeEdit = file_get_contents($root . '/include_edit.php');
$markerTemplate = file_get_contents($root . '/templates/marker_form.thtml');

if ($includeEdit === false) {
    $failures[] = 'Unable to read include_edit.php.';
} else {
    if (strpos($includeEdit, "$markerId = trim((string) $value);") === false) {
        $failures[] = 'Marker IDs must be preserved as strings.';
    }
    if (strpos($includeEdit, "WHERE mkid = '{$markerIdSql}' LIMIT 1") === false) {
        $failures[] = 'Existing Maps markers are not looked up by their string mkid.';
    }
    if (strpos($includeEdit, '$markerId = (int) $value;') !== false) {
        $failures[] = 'Marker IDs are still being cast to integers.';
    }
}

if ($markerTemplate === false) {
    $failures[] = 'Unable to read templates/marker_form.thtml.';
} else {
    if (strpos($markerTemplate, 'id="geoaddress" name="address"') === false) {
        $failures[] = 'The visible marker address field must submit as address.';
    }
    if (strpos($markerTemplate, 'onclick="if (typeof codeAddress ===') === false) {
        $failures[] = 'The marker geocode button must trigger on click.';
    }
    if (strpos($markerTemplate, 'name="geoaddress"') !== false) {
        $failures[] = 'The marker address must not depend on JavaScript copying from geoaddress.';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents marker integration checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents marker integration checks: PASS\n";
