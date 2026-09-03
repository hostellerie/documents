<?php

$source = file_get_contents(dirname(__DIR__) . '/maps_adapter.php');

$checks = array(
    "DOCUMENTS_documentMutationExistingFieldValue($documentId, $fieldId)",
    '$markerRequest = $request;',
    '$markerRequest[$markerName] = $values[$fieldId];',
    "isset($request['mkid'])"
);

foreach ($checks as $check) {
    if (strpos($source, $check) === false) {
        fwrite(STDERR, "Missing expected marker preservation code: {$check}\n");
        exit(1);
    }
}

echo "Existing Maps marker preservation checks passed.\n";
