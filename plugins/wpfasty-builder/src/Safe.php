<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Input guard of builder props, in the adapter (never in templates), so the Latte and the Twig block of the same
 * props get the same safe values. Since codegen-x 832d7f17 the organism runtime of both engines checks every URL sink
 * itself (og_url / og_srcset / attribute maps: http, https, mailto, tel, paths; tests/CodegenSafetySharedTest); this
 * guard stays as the first of the two layers (WordPress schemes, layout props, tokens); the two together print what
 * the codegen-x vectors allow (tests/CodegenSafetySharedTest::testAdapterThenRuntimeGivesTheCodegenAnswer):
 *   - a prop that holds a URL (its key ends in href, src, srcset, action, poster, url, image, avatar, logo, cover,
 *     or a plural of them: each item of the list) keeps its value only when the URL is relative or its scheme is
 *     allowed (wp_allowed_protocols(); data: only data:image/(png|jpeg|gif|webp|avif|svg+xml) and only in image
 *     keys); a protocol-relative URL (//host, /\host, \\host) is refused too; anything else — javascript:, vbscript:,
 *     data:text/html…, also obfuscated with case, whitespace, tabs, newlines, control characters or entities —
 *     becomes "" (the templates omit an empty URL attribute)
 *   - text: C0 control characters (but tab, line feed, carriage return) are removed (Latte refuses them in a
 *     compiled template; they have no use in a prop)
 * The TrustedHtml marker {"__html": …} is not touched here: only DataSources::current puts it on the props, after
 * wp_kses_post (DataSources::trusted) unless it is the_content.
 */
final class Safe
{
    /** prop keys that hold a URL */
    public const URL_KEY = '/(href|src|srcset|action|poster|url|image|avatar|logo|cover)s?$/i';
    /** prop keys whose URL is an image (data:image/* allowed) */
    public const IMAGE_KEY = '/(src|srcset|image|avatar|logo|cover|poster)s?$/i';
    /** schemes allowed when WordPress is not loaded (wp_allowed_protocols() of WordPress 6–7) */
    public const PROTOCOLS = [
        'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet',
        'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn',
    ];
    private const DATA_IMAGE = '#^data:image/(png|jpeg|gif|webp|avif|svg\+xml)[;,]#';
    private const CONTROL = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/';

    /**
     * Props as the templates may print them: every URL prop checked, control characters dropped from text.
     *
     * @param array<array-key, mixed> $props
     * @return array<array-key, mixed>
     */
    public static function props(array $props, string $key = ''): array
    {
        foreach ($props as $k => $v) {
            $name = is_string($k) ? $k : $key; // list items inherit the key of their list ("images" → each src)
            if (is_array($v)) {
                if ($k === '__html') {
                    continue;
                }
                $props[$k] = array_key_exists('__html', $v) ? $v : self::props($v, $name);
            } elseif (is_string($v)) {
                $v = self::text($v);
                $props[$k] = preg_match(self::URL_KEY, $name) === 1
                    ? self::url($v, preg_match(self::IMAGE_KEY, $name) === 1)
                    : $v;
            }
        }
        return $props;
    }

    /** text without C0 control characters (tab, LF, CR kept) */
    public static function text(string $s): string
    {
        return (string) preg_replace(self::CONTROL, '', $s);
    }

    /** true when a URL prop key */
    public static function isUrlKey(string $key): bool
    {
        return preg_match(self::URL_KEY, $key) === 1;
    }

    /**
     * $url when it is safe to print in a URL attribute, else "". A srcset is checked candidate by candidate.
     */
    public static function url(string $url, bool $image = false): string
    {
        if (str_contains($url, ',') && preg_match('/\s\d+(\.\d+)?[wx]\s*(,|$)/', $url) === 1) {
            foreach (explode(',', $url) as $candidate) {
                if (!self::allowed((string) preg_replace('/\s+\S+\s*$/', '', trim($candidate)), $image)) {
                    return '';
                }
            }
            return $url;
        }
        return self::allowed($url, $image) ? $url : '';
    }

    /** the scheme of a URL as a browser reads it (entities decoded, ASCII whitespace / controls removed), or "" */
    public static function scheme(string $url): string
    {
        $s = $url;
        for ($i = 0; $i < 3 && str_contains($s, '&'); $i++) {
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $s = strtolower((string) preg_replace('/[\x00-\x20\x7F]+/', '', $s));
        return preg_match('/^([a-z][a-z0-9+.\-]*):/', $s, $m) === 1 ? $m[1] : '';
    }

    /**
     * true for a URL that leaves the site without naming a scheme: //host, /\host, \\host, \/host (also behind
     * entities, leading spaces or control characters, tabs or newlines — what a browser removes). Browsers read a
     * backslash as a slash in http(s) URLs, so /\host is //host, while a server-side check sees a path.
     */
    public static function networkPath(string $url): bool
    {
        $s = $url;
        for ($i = 0; $i < 3 && str_contains($s, '&'); $i++) {
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $s = ltrim((string) preg_replace('/[\t\n\r]+/', '', $s), "\x00..\x20\x7F");
        return preg_match('#^[/\\\\]{2}#', $s) === 1;
    }

    private static function allowed(string $url, bool $image): bool
    {
        if (self::networkPath($url)) {
            // protocol-relative: refused, not rewritten — an external link names its scheme (https://host); the
            // backslash forms are only ever a filter bypass
            return false;
        }
        $scheme = self::scheme($url);
        if ($scheme === '') {
            return true; // relative: /x, #a, ?q, x.html (scheme() already sees through entities and whitespace)
        }
        if ($scheme === 'data') {
            $k = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $k = strtolower((string) preg_replace('/[\x00-\x20\x7F]+/', '', $k));
            return $image && preg_match(self::DATA_IMAGE, $k) === 1;
        }
        $allowed = function_exists('wp_allowed_protocols') ? (array) wp_allowed_protocols() : self::PROTOCOLS;
        return in_array($scheme, array_map('strtolower', array_map('strval', $allowed)), true);
    }
}
