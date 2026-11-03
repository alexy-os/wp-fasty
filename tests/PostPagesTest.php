<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\ContextHtml;
use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Post;
use WPFasty\Core\Model\PostPages;
use WPFasty\Data\Post\PostViewModelBuilder;
use WPFasty\Template\LatteRenderer;
use WPFasty\Template\RendererInterface;
use WPFasty\Template\TwigRenderer;
use WPFasty\Theme\PrimitivesLocator;

/**
 * chaosbun#119: a multi-page entry (`<!--nextpage-->`) gets the view/v1 `pages` primitive and both engines render the
 * Pages nav from it (escaped data, no raw HTML); the nav markup is pinned by an HTML snapshot.
 */
final class PostPagesTest extends TestCase
{
    private const SNAPSHOT = __DIR__.'/fixtures/snapshots/post-pages-nav.html';

    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__).'/tools/qa/context-schema.php';
    }

    /** @return iterable<string, array{int, list<string>}> */
    public static function invalidPages(): iterable
    {
        yield 'one page' => [1, ['/a/']];
        yield 'no pages' => [1, []];
        yield 'current 0' => [0, ['/a/', '/a/2/']];
        yield 'current past the last' => [3, ['/a/', '/a/2/']];
    }

    /** @param list<string> $urls */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPages')]
    public function testPagesNeedTwoPagesAndACurrentOneWithinThem(int $current, array $urls): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PostPages($current, $urls);
    }

    public function testPagesBounds(): void
    {
        $first = new PostPages(1, ['/a/', '/a/2/']);
        $last = new PostPages(2, ['/a/', '/a/2/']);
        self::assertSame([1, 2, 2, 2], [$first->current, $first->total(), $last->current, $last->total()]);
        self::assertSame(3, (new PostPages(3, ['/a/', '/a/2/', '/a/3/']))->total());
    }

    public function testViewModelHasPagesOnlyForMultiPageEntries(): void
    {
        $builder = new PostViewModelBuilder();
        $single = $builder->build(new Post(1, 'post', 's', 'T', TrustedHtml::empty(), '', '/s/', '', ''));
        self::assertArrayNotHasKey('pages', $single);

        $vm = $builder->build(new Post(
            69,
            'post',
            's',
            'T',
            TrustedHtml::empty(),
            '',
            '/s/',
            '',
            '',
            singular: true,
            pages: new PostPages(2, ['/s/', '/s/2/', '/s/3/']),
        ));
        self::assertSame([
            'current' => 2,
            'total' => 3,
            'links' => [
                ['title' => '1', 'url' => '/s/', 'current' => false],
                ['title' => '2', 'url' => '/s/2/', 'current' => true],
                ['title' => '3', 'url' => '/s/3/', 'current' => false],
            ],
        ], $vm['pages']);
        self::assertSame([], wpfasty_context_schema_errors($vm, 'entry'));
    }

    public function testLatteAndTwigRenderTheSamePagesNavAsTheSnapshot(): void
    {
        $root = dirname(__DIR__);
        $fixture = (string) file_get_contents($root.'/tests/fixtures/parity/latte/single-multipage.json');
        $fx = json_decode($fixture, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($fx);
        $ctx = ContextHtml::trust($fx['context']);
        $cache = sys_get_temp_dir().'/wpfasty-post-pages-'.getmypid();
        $views = [$root.'/themes/latte-press/views', $root.'/themes/latte/views'];
        $renderers = [
            'latte' => new LatteRenderer([...$views, ...PrimitivesLocator::latteRoots($root.'/themes/latte')], $cache),
            'twig' => new TwigRenderer(
                [...$views, ...PrimitivesLocator::twigRoots($root.'/themes/latte')],
                $cache.'/twig',
            ),
        ];
        $navs = array_map(
            fn (RendererInterface $r): string => $this->nav($r->render('blocks/post-content', $ctx)),
            $renderers,
        );

        self::assertSame($navs['latte'], $navs['twig']);
        self::assertSame(trim((string) file_get_contents(self::SNAPSHOT)), $navs['latte']);
        self::assertStringContainsString('aria-current="page">2</span>', $navs['latte']);
        self::assertStringContainsString('aria-label="Pages"', $navs['latte']);
    }

    /** The `<nav class="post-pages …">` element, whitespace between tags collapsed. */
    private function nav(string $html): string
    {
        self::assertSame(1, preg_match('#<nav class="post-pages[^"]*".*?</nav>#s', $html, $m), 'no Pages nav rendered');

        return (string) preg_replace(['/>\s+</', '/\s+/'], ['><', ' '], $m[0]);
    }
}
