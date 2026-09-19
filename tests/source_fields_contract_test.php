<?php

/* Static contract checks for Documents source-field interoperability. */

$root = dirname(__DIR__);
$failures = array();

$services = file_get_contents($root . '/services.inc.php');
$interop = file_get_contents($root . '/interoperability.php');

function documents_source_contract_require($content, $needle, $message, &$failures)
{
    if ($content === false || strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

documents_source_contract_require($services, 'function service_source_fields_collection_documents(', 'Bounded source-field collection service is missing.', $failures);
documents_source_contract_require($services, 'function service_source_fields_update_documents(', 'Controlled source-field update service is missing.', $failures);
documents_source_contract_require($services, "return $type === 'text' || $type === 'textarea';", 'Source-field mutation must remain limited to editorial text fields.', $failures);
documents_source_contract_require($services, "hash('sha256'", 'Source-field fingerprints are missing.', $failures);
documents_source_contract_require($services, 'hash_equals(', 'Optimistic concurrency protection is missing.', $failures);
documents_source_contract_require($services, "if (!DOCUMENTS_canEditDocument($context))", 'Source-field update does not enforce document edit permissions.', $failures);
documents_source_contract_require($services, "if (!empty($args['dry_run']))", 'Source-field update dry-run support is missing.', $failures);
documents_source_contract_require($services, 'DOCUMENTS_documentMutationUpsertValues(', 'Source-field updates do not reuse the Documents mutation helper.', $failures);
documents_source_contract_require($services, "'next_cursor'", 'Source-field collection cursor support is missing.', $failures);
documents_source_contract_require($services, "'contains'", 'Source-field collection literal filtering is missing.', $failures);
documents_source_contract_require($interop, "'content.source_fields.collection'", 'Source-field collection capability is not advertised.', $failures);
documents_source_contract_require($interop, "'content.source_fields.update'", 'Source-field update capability is not advertised.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents source-field contract checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents source-field contract checks: PASS\n";
