<?php

$root = dirname(__DIR__);
$failures = array();

function patchOnce($path, $search, $replace, &$failures)
{
    $content = file_get_contents($path);
    if ($content === false) {
        $failures[] = 'Unable to read ' . $path;
        return;
    }

    $count = 0;
    $updated = str_replace($search, $replace, $content, $count);
    if ($count !== 1) {
        $failures[] = basename($path) . ': expected one replacement, found ' . $count;
        return;
    }

    if (file_put_contents($path, $updated) === false) {
        $failures[] = 'Unable to write ' . $path;
    }
}

$editFile = $root . '/include_edit.php';
$editSearch = <<<'PHP'
    $template->set_var('type_label', $LANG_DOCUMENTS_1['type']);
    $template->set_var('type_select', DOCUMENTS_fieldsTypeSelect($field['f_type']));
    $template->set_var('sel_label', $LANG_DOCUMENTS_1['sel_group']);

    $groupSelect = '<select name="sel_id"><option value="0"> -- '
        . $LANG_DOCUMENTS_1['none'] . ' -- </option>';
    $res = DB_query(
        "SELECT g_name, gid FROM {$_TABLES['documents_selects_group']} ORDER BY g_name"
    );
    while ($row = DB_fetchArray($res)) {
        $selected = ((int) $row['gid'] === (int) $field['sel_id']) ? ' selected="selected"' : '';
        $groupSelect .= '<option value="' . (int) $row['gid'] . '"' . $selected . '>'
            . htmlspecialchars($row['g_name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $groupSelect .= '</select>';
    $template->set_var('group_select', $groupSelect);
PHP;

$editReplace = <<<'PHP'
    $template->set_var('type_label', $LANG_DOCUMENTS_1['type']);
    $template->set_var('type_select', DOCUMENTS_fieldsTypeSelect($field['f_type']));

    if ($field['f_type'] === 'text') {
        if (!function_exists('DOCUMENTS_textFormatOptions')) {
            require_once $_CONF['path'] . 'plugins/documents/presentation.php';
        }
        $isFrench = isset($_CONF['language'])
            && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
        $formatLabels = $isFrench
            ? array(
                'raw' => 'Tel que saisi',
                'lower' => 'minuscules',
                'upper' => 'MAJUSCULES',
                'sentence' => 'Première lettre en majuscule',
                'title' => 'Initiale de chaque mot en majuscule'
            )
            : array(
                'raw' => 'As entered',
                'lower' => 'lowercase',
                'upper' => 'UPPERCASE',
                'sentence' => 'First letter uppercase',
                'title' => 'Each Word Capitalized'
            );
        $template->set_var(
            'sel_label',
            $isFrench ? 'Format d’affichage du texte' : 'Text display format'
        );
        $groupSelect = DOCUMENTS_textFormatOptions($field['sel_id'], $formatLabels);
    } else {
        $template->set_var('sel_label', $LANG_DOCUMENTS_1['sel_group']);
        $groupSelect = '<select name="sel_id"><option value="0"> -- '
            . $LANG_DOCUMENTS_1['none'] . ' -- </option>';
        $res = DB_query(
            "SELECT g_name, gid FROM {$_TABLES['documents_selects_group']} ORDER BY g_name"
        );
        while ($row = DB_fetchArray($res)) {
            $selected = ((int) $row['gid'] === (int) $field['sel_id']) ? ' selected="selected"' : '';
            $groupSelect .= '<option value="' . (int) $row['gid'] . '"' . $selected . '>'
                . htmlspecialchars($row['g_name'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $groupSelect .= '</select>';
    }
    $template->set_var('group_select', $groupSelect);
PHP;

patchOnce($editFile, $editSearch, $editReplace, $failures);

$htmlFile = $root . '/include_html.php';
$htmlSearch = <<<'PHP'
        case 'text':
        default:
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $content = nl2br(stripslashes($value));
            $content = DOCUMENTS_linkifyUrls($content);
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;
PHP;

$htmlReplace = <<<'PHP'
        case 'text':
        default:
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $displayValue = stripslashes($value);
            if ($field['f_type'] === 'text' && function_exists('DOCUMENTS_formatTextDisplay')) {
                $displayValue = DOCUMENTS_formatTextDisplay(
                    $displayValue,
                    isset($field['sel_id']) ? $field['sel_id'] : 0
                );
            }
            $content = nl2br($displayValue);
            $content = DOCUMENTS_linkifyUrls($content);
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;
PHP;

patchOnce($htmlFile, $htmlSearch, $htmlReplace, $failures);

if (!empty($failures)) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . "\n");
    }
    exit(1);
}

echo "Presentation patch applied successfully.\n";
