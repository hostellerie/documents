<?php

/* Regression: existing textarea fields render Geeklog autotags after safe Markdown. */

function PLG_replaceTags($text)
{
    return str_replace(
        '[youtube:MiZ57EvWAwk]',
        '<iframe data-youtube="MiZ57EvWAwk"></iframe>',
        (string) $text
    );
}

require_once dirname(__DIR__) . '/markdown.php';

function documentsAutotagAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$plain = DOCUMENTS_renderMarkdownTextarea('[youtube:MiZ57EvWAwk]');
documentsAutotagAssert(
    strpos($plain, '<iframe data-youtube="MiZ57EvWAwk"></iframe>') !== false,
    'Autotag must render in a plain textarea.'
);

$markdown = DOCUMENTS_renderMarkdownTextarea(
    "## Video\n\n[youtube:MiZ57EvWAwk]\n\n<script>alert(1)</script>"
);
documentsAutotagAssert(strpos($markdown, '<h2>Video</h2>') !== false, 'Markdown heading must render.');
documentsAutotagAssert(
    strpos($markdown, '<iframe data-youtube="MiZ57EvWAwk"></iframe>') !== false,
    'Autotag must render after Markdown.'
);
documentsAutotagAssert(strpos($markdown, '<script>') === false, 'Raw textarea HTML must remain blocked.');
documentsAutotagAssert(
    strpos($markdown, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false,
    'Raw textarea HTML must remain escaped.'
);

echo "OK\n";
