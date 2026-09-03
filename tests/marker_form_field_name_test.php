<?php

$root = dirname(__DIR__);
$template = file_get_contents($root . '/templates/marker_form.thtml');
$form = file_get_contents($root . '/public_form.php');

if (strpos($form, "set_var('var_name', $name)") === false) {
    fwrite(STDERR, "public_form.php must pass the marker field variable name to the template.\n");
    exit(1);
}

if (strpos($template, 'name="{var_name}" value="{mkid}"') === false) {
    fwrite(STDERR, "Marker form must post the existing marker id using the Documents field variable name.\n");
    exit(1);
}

if (strpos($template, 'name="mkid" value="{mkid}"') !== false) {
    fwrite(STDERR, "Marker form must not post the marker id only under the legacy fixed mkid name.\n");
    exit(1);
}

echo "Marker form field name contract OK\n";
