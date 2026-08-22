<?php

$_SERVER['PHP_SELF'] = 'tests/presentation_test.php';
require_once dirname(__DIR__) . '/presentation.php';

function presentation_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . "\n");
        exit(1);
    }
}

presentation_assert(DOCUMENTS_formatTextDisplay('MiXeD Text', 0) === 'MiXeD Text', 'raw format changed text');
presentation_assert(DOCUMENTS_formatTextDisplay('MiXeD Text', -1) === 'mixed text', 'lowercase format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('MiXeD Text', -2) === 'MIXED TEXT', 'uppercase format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('mIXED TEXT', -3) === 'Mixed text', 'sentence format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('mIXED tEXT', -4) === 'Mixed Text', 'title format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('unchanged', 99) === 'unchanged', 'unknown format should be raw');

$html = DOCUMENTS_textFormatOptions(-2, array(
    'raw' => 'Raw',
    'lower' => 'Lower',
    'upper' => 'Upper',
    'sentence' => 'Sentence',
    'title' => 'Title'
));
presentation_assert(strpos($html, 'value="-2" selected="selected"') !== false, 'selected format is not preserved');

echo "Documents presentation tests: PASS\n";
