<?php

$root = dirname(__DIR__);
require_once $root . '/integrity.php';

$failures = array();
$cases = array(
    'cv_cordiste' => 'cv_cordiste',
    '4_hauteur_securite_formations' => '4_hauteur_securite_formations',
    'simple-slug' => 'simple-slug',
    'Titre de catégorie' => 'titre-de-categorie',
    'Formation & sécurité' => 'formation-securite'
);

foreach ($cases as $input => $expected) {
    $actual = DOCUMENTS_normalizeRouteSlug($input);
    if ($actual !== $expected) {
        $failures[] = $input . ' => ' . $actual . ' (expected ' . $expected . ')';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents legacy route slug checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents legacy route slug checks: PASS\n";
