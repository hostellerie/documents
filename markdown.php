<?php

/* Safe Markdown subset for existing Documents textarea fields. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'markdown.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_textareaHasMarkdown($value)
{
    $value = (string) $value;
    if ($value === '') {
        return false;
    }

    return preg_match('/(^|\n)\s*(?:#{2,4}\s+|[-+*]\s+|\d+[.)]\s+|>\s+)|\*\*[^\n*]+\*\*|__[^\n_]+__|`[^\n`]+`|\[[^\]\n]+\]\([^\s)]+\)/m', $value) === 1;
}

function DOCUMENTS_markdownSafeUrl($url)
{
    $url = trim(html_entity_decode((string) $url, ENT_QUOTES, 'UTF-8'));
    if ($url === '') {
        return '';
    }

    if ($url[0] === '/' || $url[0] === '#') {
        return $url;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if ($scheme === null || $scheme === false || $scheme === '') {
        return '';
    }

    $scheme = strtolower((string) $scheme);
    return in_array($scheme, array('http', 'https', 'mailto'), true) ? $url : '';
}

function DOCUMENTS_markdownInline($text)
{
    $text = (string) $text;
    $tokens = array();

    $text = preg_replace_callback('/`([^`\n]+)`/', function ($match) use (&$tokens) {
        $key = "\x1A" . count($tokens) . "\x1A";
        $tokens[$key] = '<code>' . htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8') . '</code>';
        return $key;
    }, $text);

    $text = preg_replace_callback('/\[([^\]\n]+)\]\(([^\s)]+)\)/', function ($match) use (&$tokens) {
        $url = DOCUMENTS_markdownSafeUrl($match[2]);
        if ($url === '') {
            return $match[0];
        }
        $key = "\x1A" . count($tokens) . "\x1A";
        $tokens[$key] = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8') . '</a>';
        return $key;
    }, $text);

    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $safe = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $safe);
    $safe = preg_replace('/__([^_\n]+)__/', '<strong>$1</strong>', $safe);
    $safe = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $safe);

    if (!empty($tokens)) {
        $safe = strtr($safe, $tokens);
    }

    return $safe;
}

function DOCUMENTS_renderMarkdownTextarea($value)
{
    $value = str_replace(array("\r\n", "\r"), "\n", stripslashes((string) $value));
    if ($value === '') {
        return '';
    }

    if (!DOCUMENTS_textareaHasMarkdown($value)) {
        return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    $lines = explode("\n", $value);
    $html = '';
    $paragraph = array();
    $listType = '';

    $flushParagraph = function () use (&$paragraph, &$html) {
        if (empty($paragraph)) {
            return;
        }
        $parts = array();
        foreach ($paragraph as $line) {
            $parts[] = DOCUMENTS_markdownInline($line);
        }
        $html .= '<p>' . implode('<br>', $parts) . '</p>';
        $paragraph = array();
    };

    $closeList = function () use (&$listType, &$html) {
        if ($listType !== '') {
            $html .= '</' . $listType . '>';
            $listType = '';
        }
    };

    foreach ($lines as $line) {
        if (trim($line) === '') {
            $flushParagraph();
            $closeList();
            continue;
        }

        if (preg_match('/^\s*(#{2,4})\s+(.+)$/', $line, $match)) {
            $flushParagraph();
            $closeList();
            $level = min(4, strlen($match[1]));
            $html .= '<h' . $level . '>' . DOCUMENTS_markdownInline(trim($match[2])) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^\s*[-+*]\s+(.+)$/', $line, $match)) {
            $flushParagraph();
            if ($listType !== 'ul') {
                $closeList();
                $html .= '<ul>';
                $listType = 'ul';
            }
            $html .= '<li>' . DOCUMENTS_markdownInline(trim($match[1])) . '</li>';
            continue;
        }

        if (preg_match('/^\s*\d+[.)]\s+(.+)$/', $line, $match)) {
            $flushParagraph();
            if ($listType !== 'ol') {
                $closeList();
                $html .= '<ol>';
                $listType = 'ol';
            }
            $html .= '<li>' . DOCUMENTS_markdownInline(trim($match[1])) . '</li>';
            continue;
        }

        if (preg_match('/^\s*>\s+(.+)$/', $line, $match)) {
            $flushParagraph();
            $closeList();
            $html .= '<blockquote><p>' . DOCUMENTS_markdownInline(trim($match[1])) . '</p></blockquote>';
            continue;
        }

        $closeList();
        $paragraph[] = $line;
    }

    $flushParagraph();
    $closeList();

    return $html;
}
