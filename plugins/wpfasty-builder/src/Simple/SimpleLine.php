<?php

declare(strict_types=1);

namespace WPFastyBuilder\Simple;

/**
 * The Simple block line (ADR-007): the bundled, pinned snapshot in sources/ui8kit (tools/builder/simple-snapshot.ts),
 * read without WordPress. contracts.json is the neutral contract DTO (block-contract/v1 + content model of every
 * block), prototypes.json the static wireframe HTML of every variant (the DOM fixture of ui8kit/prototypes; rectangles
 * only: no text, no URL, no script, checked by the snapshot tool and tests), <engine>/catalog.json the wpfasty-catalog@2
 * the renderer uses. Nothing here touches the network; a missing or malformed file is an empty line, never an error
 * on a page.
 */
final class SimpleLine
{
    public const ITEM = '/^[a-z][a-z0-9-]{0,62}$/';
    public const VARIANT = '/^[A-Za-z][A-Za-z0-9_-]{0,62}$/';

    /** @var array<string, array<string, mixed>|null> */
    private array $json = [];

    public function __construct(private readonly string $dir)
    {
    }

    public function dir(): string
    {
        return $this->dir;
    }

    /** the template folder of one runtime (blocks/, compose/LinkButton, php/), or null */
    public function runtimeDir(string $runtime): ?string
    {
        $dir = $this->dir.'/'.$runtime;

        return in_array($runtime, ['latte', 'twig'], true) && is_file($dir.'/catalog.json') ? $dir : null;
    }

    /**
     * The pins and digest of the snapshot (snapshot.json without its file list).
     *
     * @return array<string, mixed>
     */
    public function provenance(): array
    {
        $snap = $this->read('snapshot.json') ?? [];
        unset($snap['files']);

        return $snap;
    }

    /**
     * The contract of each block: item => {item, title, description, category, contract, model}.
     *
     * @return array<string, array<string, mixed>>
     */
    public function contracts(): array
    {
        $out = [];
        foreach ((array) ($this->read('contracts.json')['blocks'] ?? []) as $b) {
            if (is_array($b) && is_string($b['item'] ?? null) && 1 === preg_match(self::ITEM, $b['item'])) {
                $out[$b['item']] = \WPFastyBuilder\Cast::map($b);
            }
        }

        return $out;
    }

    public function has(string $item): bool
    {
        return isset($this->contracts()[$item]);
    }

    /** @return list<string> the variant ids of a block, in contract order */
    public function variants(string $item): array
    {
        $out = [];
        $variants = $this->contracts()[$item]['contract'] ?? null;
        foreach (is_array($variants) ? (array) ($variants['variants'] ?? []) : [] as $v) {
            if (is_array($v) && is_string($v['id'] ?? null) && 1 === preg_match(self::VARIANT, $v['id'])) {
                $out[] = $v['id'];
            }
        }

        return $out;
    }

    /** the static wireframe HTML of a variant (trusted bundled markup), null for an unknown one */
    public function prototype(string $item, string $variant): ?string
    {
        if (!in_array($variant, $this->variants($item), true)) {
            return null;
        }
        $variants = $this->read('prototypes.json')['variants'] ?? null;
        $html = is_array($variants) ? ($variants[$item.'/'.$variant] ?? null) : null;

        return is_string($html) && self::wireframe($html) ? $html : null;
    }

    /**
     * The prototype sheet of a block: one scope per variant, addressed by the fragment #<item>--<variant> (CSS
     * :target shows only that one, no script). Null for an unknown block.
     */
    public function sheet(string $item): ?string
    {
        if (!$this->has($item)) {
            return null;
        }
        $out = '';
        foreach ($this->variants($item) as $variant) {
            $html = $this->prototype($item, $variant);
            if (null !== $html) {
                $id = $item.'--'.$variant;
                $out .= '<div class="wpfb-proto" id="'.$id.'" data-item="'.$item.'" data-variant="'.$variant.'">'.$html."</div>\n";
            }
        }

        return '' !== $out ? '<div class="wpfb-protos" aria-hidden="true">'."\n".$out.'</div>' : null;
    }

    /**
     * Structural wireframe only (the same rule as the snapshot tool): allowlisted tags and attributes, no comment,
     * script, style, URL, event handler or text node.
     */
    public static function wireframe(string $html): bool
    {
        // active content: comments, <script> / <style> elements, script URLs, handlers, inline style, links, sources
        if (1 === preg_match('/<!--|<(script|style)\b|javascript:|\son[a-z]+=|\sstyle=|\shref=|\ssrc=/i', $html)) {
            return false;
        }
        $tags = ['section', 'header', 'footer', 'nav', 'div', 'span', 'ul', 'ol', 'li', 'article', 'aside', 'figure', 'dl', 'dt', 'dd', 'p'];
        preg_match_all('/<([a-z0-9-]+)([^>]*)>/i', $html, $m, PREG_SET_ORDER);
        foreach ($m as $tag) {
            if (!in_array(strtolower($tag[1]), $tags, true)) {
                return false;
            }
            preg_match_all('/\s([a-z-]+)(?:="[^"<>]*")?/i', $tag[2], $attrs);
            foreach ($attrs[1] as $attr) {
                if (1 !== preg_match('/^(class|aria-hidden|role|data-[a-z-]+)$/', $attr)) {
                    return false;
                }
            }
        }

        return 1 !== preg_match('/>[^<\s][^<]*</', $html);
    }

    /** @return array<string, mixed>|null */
    private function read(string $file): ?array
    {
        if (!array_key_exists($file, $this->json)) {
            $path = $this->dir.'/'.$file;
            $raw = is_file($path) ? (string) file_get_contents($path) : '';
            $data = '' !== $raw ? json_decode($raw, true) : null;
            /** @var array<string, mixed>|null $data */
            $data = is_array($data) ? $data : null;
            $this->json[$file] = $data;
        }

        return $this->json[$file];
    }
}
