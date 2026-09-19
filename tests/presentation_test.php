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
presentation_assert(DOCUMENTS_formatTextDisplay('MiXeD Text', 1001) === 'mixed text', 'lowercase format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('MiXeD Text', 1002) === 'MIXED TEXT', 'uppercase format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('mIXED TEXT', 1003) === 'Mixed text', 'sentence format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('mIXED tEXT', 1004) === 'Mixed Text', 'title format failed');
presentation_assert(DOCUMENTS_formatTextDisplay('unchanged', 99) === 'unchanged', 'unknown format should be raw');

$html = DOCUMENTS_textFormatOptions(1002, array(
    'raw' => 'Raw',
    'lower' => 'Lower',
    'upper' => 'Upper',
    'sentence' => 'Sentence',
    'title' => 'Title'
));
presentation_assert(strpos($html, 'value="1002" selected="selected"') !== false, 'selected format is not preserved');
presentation_assert(strpos($html, 'value="-') === false, 'text format options must survive numeric request filtering');

$GLOBALS['documents_test_admin'] = false;
$GLOBALS['documents_test_anon'] = true;
function SEC_hasRights($rights)
{
    return !empty($GLOBALS['documents_test_admin']);
}
function COM_isAnonUser()
{
    return !empty($GLOBALS['documents_test_anon']);
}

$_DOCUMENTS_CONF = array('stats_visibility' => 0);
presentation_assert(DOCUMENTS_canShowStats() === false, 'hidden stats should be hidden');

$_DOCUMENTS_CONF['stats_visibility'] = 1;
presentation_assert(DOCUMENTS_canShowStats() === false, 'anonymous visitor saw admin-only stats');
$GLOBALS['documents_test_admin'] = true;
presentation_assert(DOCUMENTS_canShowStats() === true, 'administrator could not see admin-only stats');

$GLOBALS['documents_test_admin'] = false;
$_DOCUMENTS_CONF['stats_visibility'] = 2;
$GLOBALS['documents_test_anon'] = true;
presentation_assert(DOCUMENTS_canShowStats() === false, 'anonymous visitor saw member stats');
$GLOBALS['documents_test_anon'] = false;
presentation_assert(DOCUMENTS_canShowStats() === true, 'logged-in user could not see member stats');

$_DOCUMENTS_CONF['stats_visibility'] = 3;
$GLOBALS['documents_test_anon'] = true;
presentation_assert(DOCUMENTS_canShowStats() === true, 'anonymous visitor could not see public stats');

echo "Documents presentation tests: PASS\n";
