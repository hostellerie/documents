<?php

$root = dirname(__DIR__);
require_once $root . '/markdown.php';

function documentsMarkdownAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$plain = "Texte historique\nDeuxième ligne <strong>brut</strong>";
$plainHtml = DOCUMENTS_renderMarkdownTextarea($plain);
documentsMarkdownAssert(strpos($plainHtml, '<br') !== false, 'Plain textarea keeps historical line breaks.');
documentsMarkdownAssert(strpos($plainHtml, '&lt;strong&gt;') !== false, 'Raw HTML is escaped in plain text.');
documentsMarkdownAssert(strpos($plainHtml, '<strong>brut</strong>') === false, 'Raw HTML is never interpreted.');

$markdown = "## Compétences\n\n**Cordiste expérimenté**\n\n- Travaux sur cordes\n- Maçonnerie\n\n[Le Cordiste](https://le-cordiste.com/)";
$markdownHtml = DOCUMENTS_renderMarkdownTextarea($markdown);
documentsMarkdownAssert(strpos($markdownHtml, '<h2>Compétences</h2>') !== false, 'Level-two Markdown heading is rendered.');
documentsMarkdownAssert(strpos($markdownHtml, '<strong>Cordiste expérimenté</strong>') !== false, 'Bold Markdown is rendered.');
documentsMarkdownAssert(strpos($markdownHtml, '<ul>') !== false && strpos($markdownHtml, '<li>Travaux sur cordes</li>') !== false, 'Markdown lists are rendered.');
documentsMarkdownAssert(strpos($markdownHtml, '<a href="https://le-cordiste.com/">Le Cordiste</a>') !== false, 'Safe Markdown links are rendered.');

$noH1 = "# Titre interdit\n\n**Texte**";
$noH1Html = DOCUMENTS_renderMarkdownTextarea($noH1);
documentsMarkdownAssert(stripos($noH1Html, '<h1') === false, 'Markdown never generates an H1.');
documentsMarkdownAssert(strpos($noH1Html, '# Titre interdit') !== false, 'A single-hash heading stays literal text.');

$htmlAttack = "## Test\n\n<script>alert('x')</script>\n\n[attaque](javascript:alert(1))";
$htmlAttackRendered = DOCUMENTS_renderMarkdownTextarea($htmlAttack);
documentsMarkdownAssert(stripos($htmlAttackRendered, '<script') === false, 'Script HTML is escaped.');
documentsMarkdownAssert(strpos($htmlAttackRendered, '&lt;script&gt;') !== false, 'Escaped script remains visible as text.');
documentsMarkdownAssert(stripos($htmlAttackRendered, 'href="javascript:') === false, 'Unsafe URL schemes are not linked.');

fwrite(STDOUT, "Textarea Markdown tests passed.\n");
