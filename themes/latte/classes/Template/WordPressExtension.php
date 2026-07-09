<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Extension;

/**
 * Latte tags for WordPress shell hooks.
 */
final class WordPressExtension extends Extension
{
    public function getTags(): array
    {
        return [
            'do_action' => [self::class, 'createDoActionNode'],
            'apply_filters' => [self::class, 'createApplyFiltersNode'],
            'wp_head' => [self::class, 'createWpHeadNode'],
            'wp_footer' => [self::class, 'createWpFooterNode'],
            'wp_body_open' => [self::class, 'createWpBodyOpenNode'],
            'language_attributes' => [self::class, 'createLanguageAttributesNode'],
            'body_class' => [self::class, 'createBodyClassNode'],
        ];
    }

    public static function createDoActionNode(Tag $tag): StatementNode
    {
        $tag->expectArguments();
        $node = $tag->parser->parseArguments();

        return new class ($node) extends StatementNode {
            public function __construct(public readonly Node $node)
            {
            }

            public function print(PrintContext $context): string
            {
                return WordPressExtension::callFn('do_action', $this->node, $context, echo: false);
            }

            public function &getIterator(): \Generator
            {
                yield $this->node;
            }
        };
    }

    public static function createApplyFiltersNode(Tag $tag): StatementNode
    {
        $tag->expectArguments();
        $node = $tag->parser->parseArguments();

        return new class ($node) extends StatementNode {
            public function __construct(public readonly Node $node)
            {
            }

            public function print(PrintContext $context): string
            {
                return WordPressExtension::callFn('apply_filters', $this->node, $context, echo: true);
            }

            public function &getIterator(): \Generator
            {
                yield $this->node;
            }
        };
    }

    public static function createWpHeadNode(Tag $tag): StatementNode
    {
        return new class extends StatementNode {
            public function print(PrintContext $context): string
            {
                return 'wp_head();';
            }

            public function &getIterator(): \Generator
            {
                return;
                yield;
            }
        };
    }

    public static function createWpFooterNode(Tag $tag): StatementNode
    {
        return new class extends StatementNode {
            public function print(PrintContext $context): string
            {
                return 'wp_footer();';
            }

            public function &getIterator(): \Generator
            {
                return;
                yield;
            }
        };
    }

    public static function createWpBodyOpenNode(Tag $tag): StatementNode
    {
        return new class extends StatementNode {
            public function print(PrintContext $context): string
            {
                return 'if (function_exists(\'wp_body_open\')) { wp_body_open(); }';
            }

            public function &getIterator(): \Generator
            {
                return;
                yield;
            }
        };
    }

    public static function createLanguageAttributesNode(Tag $tag): StatementNode
    {
        return new class extends StatementNode {
            public function print(PrintContext $context): string
            {
                return 'language_attributes();';
            }

            public function &getIterator(): \Generator
            {
                return;
                yield;
            }
        };
    }

    public static function createBodyClassNode(Tag $tag): StatementNode
    {
        if ($tag->parser->isEnd()) {
            return new class extends StatementNode {
                public function print(PrintContext $context): string
                {
                    return 'body_class();';
                }

                public function &getIterator(): \Generator
                {
                    return;
                    yield;
                }
            };
        }

        $node = $tag->parser->parseArguments();
        return new class ($node) extends StatementNode {
            public function __construct(public readonly Node $node)
            {
            }

            public function print(PrintContext $context): string
            {
                return WordPressExtension::callFn('body_class', $this->node, $context, echo: false);
            }

            public function &getIterator(): \Generator
            {
                yield $this->node;
            }
        };
    }

    public static function callFn(string $fn, Node $node, PrintContext $context, bool $echo): string
    {
        $prefix = $echo ? 'echo ' : '';
        if ($node instanceof ArrayNode && count($node->items) > 0) {
            $args = [];
            foreach ($node->items as $item) {
                $args[] = $item->value->print($context);
            }
            return $prefix . $fn . '(' . implode(', ', $args) . ');';
        }
        return $prefix . $fn . '(' . $node->print($context) . ');';
    }
}
