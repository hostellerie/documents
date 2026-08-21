<?php

/* Documents 1.1.9 comment security checks. */

$root = dirname(__DIR__);
$functionsFile = $root . DIRECTORY_SEPARATOR . 'functions.inc';
$failures = array();

if (!is_file($functionsFile)) {
    $failures[] = 'functions.inc is missing.';
} else {
    $content = file_get_contents($functionsFile);
    if ($content === false) {
        $failures[] = 'Unable to read functions.inc.';
    } else {
        $functionPos = strpos($content, 'function plugin_savecomment_documents(');
        $start = ($functionPos === false) ? 0 : $functionPos;
        $savePos = strpos($content, 'CMT_saveComment(', $start);
        $activePos = strpos($content, "(int) \$doc['active'] === 1", $start);
        $accessPos = strpos($content, 'SEC_hasAccess(', $start);
        $denyPos = strpos($content, 'if (!$canComment)', $start);

        if ($functionPos === false) {
            $failures[] = 'plugin_savecomment_documents() is missing.';
        }
        if ($savePos === false) {
            $failures[] = 'CMT_saveComment() call is missing.';
        }
        if ($activePos === false || ($savePos !== false && $activePos > $savePos)) {
            $failures[] = 'Comment save does not verify published status before CMT_saveComment().';
        }
        if ($accessPos === false || ($savePos !== false && $accessPos > $savePos)) {
            $failures[] = 'Comment save does not verify document read access before CMT_saveComment().';
        }
        if ($denyPos === false || ($savePos !== false && $denyPos > $savePos)) {
            $failures[] = 'Comment save does not reject unauthorized requests before CMT_saveComment().';
        }
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents comment security checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents comment security checks: PASS\n";
