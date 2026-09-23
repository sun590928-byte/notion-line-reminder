<?php
declare(strict_types=1);

namespace App\Services;

/** 富文字白名單過濾：只保留基本排版標籤，移除 script、事件屬性等 */
final class Sanitizer
{
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'target'], 'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'blockquote' => [], 'mark' => [], 'small' => [], 'code' => [], 'hr' => [], 'span' => [], 'div' => [],
    ];
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select',
        'button', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link', 'base', 'frame', 'frameset', 'video', 'audio'];

    public static function html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        if (!class_exists(\DOMDocument::class)) {
            return nl2br(e(strip_tags($html)));
        }
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="amf-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('amf-root');
        if (!$root) {
            return '';
        }
        self::clean($root);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function clean(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, self::DROP, true)) {
                    $node->removeChild($child);
                    continue;
                }
                self::clean($child);
                if (!array_key_exists($tag, self::ALLOWED)) {
                    // 不允許的標籤：保留文字內容、移除標籤本身
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    if (!in_array(strtolower($attr->name), self::ALLOWED[$tag], true)) {
                        $child->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'a') {
                    $href = Url::clean($child->getAttribute('href'));
                    if ($href === '') {
                        $child->removeAttribute('href');
                    } else {
                        $child->setAttribute('href', $href);
                    }
                    if ($child->getAttribute('target') === '_blank') {
                        $child->setAttribute('rel', 'noopener');
                    } else {
                        $child->removeAttribute('target');
                    }
                }
            } elseif ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
            }
        }
    }

    /** 單行/多行純文字：移除控制字元並限制長度 */
    public static function text(string $text, int $max = 5000): string
    {
        $text = (string) preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u', '', $text);
        $text = str_replace("\r\n", "\n", $text);
        return mb_substr($text, 0, $max);
    }
}
