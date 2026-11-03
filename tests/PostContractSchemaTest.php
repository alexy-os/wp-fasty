<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Post;

/**
 * The Post DTO fields named after the Codex v0.4 entry validate against the published @ui8kit/contract entry schema
 * (node_modules/@ui8kit/contract/schemas/entry.schema.json), field by field.
 *
 * Mapping (docs/definition-of-done.md, "Contract mapping"): id -> "<kind>-<id>" (Mapper::entry), kind, slug, title,
 * content (rendered HTML string), excerpt. The template context is a view projection, not an entry: locale, status,
 * visibility, version, created_at, updated_at and the other entry fields are not in it, so the whole context is
 * validated by schema/theme-context.schema.json (ContextSchemaTest), the view profile view/v1 copied from the contract.
 */
final class PostContractSchemaTest extends TestCase
{
    private const SCHEMA = 'https://schema.buildy.tw/entry.schema.json';

    private static ?Validator $validator = null;

    /** @return iterable<string, array{Post}> */
    public static function posts(): iterable
    {
        yield 'post' => [new Post(7, 'post', 'hello-world', 'Hello & “world”', new TrustedHtml('<p>Hi</p>'), 'Hi', '/hello/', '', '')];
        yield 'page' => [new Post(2, 'page', 'about', 'About', TrustedHtml::empty(), '', '/about/', '', '')];
        yield 'custom type' => [new Post(70, 'project', 'alpha', 'Alpha', TrustedHtml::empty(), '', '/p/alpha/', '', '')];
        yield 'attachment' => [new Post(68, 'attachment', 'pic', 'Pic', TrustedHtml::empty(), '', '/pic/', '', '', attachmentUrl: '/p.png')];
    }

    #[DataProvider('posts')]
    public function testCodexFieldsMatchTheContractEntrySchema(Post $post): void
    {
        foreach (self::entryFields($post) as $key => $value) {
            self::assertTrue(self::valid($value, $key), "entry.$key = ".var_export($value, true));
        }
    }

    public function testContractRejectsWhatTheMappingGuardsAgainst(): void
    {
        self::assertFalse(self::valid('', 'slug'), 'empty slug');
        self::assertFalse(self::valid('Project', 'kind'), 'kind is lowercase');
        self::assertFalse(self::valid(7, 'id'), 'the entry id is a string, not the WordPress int');
    }

    /** @return array<string, string> */
    private static function entryFields(Post $post): array
    {
        return [
            'id' => $post->kind.'-'.$post->id,
            'kind' => $post->kind,
            'slug' => $post->slug,
            'title' => $post->title,
            'content' => (string) $post->content,
            'excerpt' => $post->excerpt,
        ];
    }

    private static function valid(mixed $value, string $key): bool
    {
        if (null === self::$validator) {
            $file = \dirname(__DIR__).'/node_modules/@ui8kit/contract/schemas/entry.schema.json';
            if (!is_file($file)) {
                self::markTestSkipped('@ui8kit/contract is not installed (bun install)');
            }
            self::$validator = new Validator();
            self::$validator->resolver()?->registerFile(self::SCHEMA, $file);
        }

        return self::$validator->validate($value, self::SCHEMA.'#/properties/'.$key)->isValid();
    }
}
