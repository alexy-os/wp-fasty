<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\ContextHtml;
use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Author;
use WPFasty\Core\Model\Image;
use WPFasty\Core\Model\Post;
use WPFasty\Core\Model\PostLink;
use WPFasty\Core\Model\Term;
use WPFasty\Data\Post\PostViewModelBuilder;
use WPFasty\Template\LatteRenderer;
use WPFasty\Template\RendererInterface;
use WPFasty\Template\TwigRenderer;
use WPFasty\Theme\PrimitivesLocator;

/**
 * Renderer contract: one Post DTO, built into the view-model by PostViewModelBuilder, renders the single page
 * (pages/single in layout/default) through LatteRenderer and TwigRenderer to the same DOM, and every DTO value the
 * view shows reaches both outputs escaped the same way. The rest of the context is the frozen single fixture.
 */
final class PostRenderContractTest extends TestCase
{
    private string $cache;

    public function testOnePostDtoRendersTheSameThroughLatteAndTwig(): void
    {
        $post = new Post(
            id: 42,
            kind: 'post',
            slug: 'contract-post',
            title: 'Contract <post> & “quotes”',
            content: new TrustedHtml('<p>Body <strong>trusted</strong></p>'),
            excerpt: 'Short & plain',
            url: 'https://example.test/contract-post/',
            dateDisplay: 'November 2, 2026',
            dateIso: '2026-11-02T10:00:00+00:00',
            featuredImage: new Image('https://example.test/f.jpg', '1200', '630', 'Alt <x>', '', ''),
            categories: [new Term('category', 'News & notes', 'news', 'https://example.test/c/news/')],
            tags: [new Term('post_tag', 'Alpha', 'alpha', 'https://example.test/t/alpha/')],
            author: new Author(1, 'Ann O\'Neil', 'https://example.test/a/ann/'),
            cssClass: 'post-42 post type-post',
            linkPages: TrustedHtml::empty(),
            previous: new PostLink('Older one', 'https://example.test/older/'),
            next: new PostLink('Newer one', 'https://example.test/newer/'),
            singular: true,
        );
        $fx = json_decode((string) file_get_contents(__DIR__.'/fixtures/parity/latte/single.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($fx);
        $context = ContextHtml::trust($fx['context']);
        $context['page'] = (new PostViewModelBuilder())->build($post);

        $latte = $this->page($this->latte(), $context);
        $twig = $this->page($this->twig(), $context);

        $compare = require \dirname(__DIR__).'/tools/parity/compare-classes.php';
        self::assertNull($compare($latte, $twig), 'Latte and Twig render the Post DTO to the same DOM');
        foreach ([
            'Contract &lt;post&gt; &amp; “quotes”',
            '<p>Body <strong>trusted</strong></p>',
            'News &amp; notes',
            'Older one',
            'Newer one',
            'https://example.test/f.jpg',
        ] as $needle) {
            self::assertStringContainsString($needle, $latte, "latte: $needle");
            self::assertStringContainsString($needle, $twig, "twig: $needle");
        }
        self::assertStringNotContainsString('<post>', $latte.$twig, 'the title is escaped');
    }

    protected function setUp(): void
    {
        $this->cache = sys_get_temp_dir().'/post-contract-'.uniqid('', true);
    }

    /** @param array<string, mixed> $context */
    private function page(RendererInterface $r, array $context): string
    {
        $context['content'] = $r->render('pages/single', $context);

        return $r->render('layout/default', $context);
    }

    private function views(): string
    {
        return \dirname(__DIR__).'/themes/wp-fasty/views';
    }

    private function latte(): LatteRenderer
    {
        return new LatteRenderer([$this->views(), ...PrimitivesLocator::latteRoots(\dirname(__DIR__).'/themes/wp-fasty')], $this->cache);
    }

    private function twig(): TwigRenderer
    {
        $prims = PrimitivesLocator::twigRoots(\dirname(__DIR__).'/themes/wp-fasty');
        if ([] === $prims || !class_exists(\Twig\Environment::class)) {
            self::markTestSkipped('Twig primitives not installed (themes/wp-fasty/twig: npx ui8kit@3 add --all)');
        }

        return new TwigRenderer([$this->views(), ...$prims], $this->cache.'/twig');
    }
}
