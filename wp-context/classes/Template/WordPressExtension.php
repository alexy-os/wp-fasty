<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Extension;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\Php\ScalarNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Compiler\Token;

class WordPressExtension extends Extension
{
    public function getTags(): array
    {
        return [
            'do_action' => [self::class, 'createDoActionNode'],
            'apply_filters' => [self::class, 'createApplyFiltersNode'],
            'wp_head' => [self::class, 'createWpHeadNode'],
            'wp_footer' => [self::class, 'createWpFooterNode'],
            'body_class' => [self::class, 'createBodyClassNode'],
        ];
    }

    public static function createDoActionNode(Tag $tag): StatementNode
    {
        $tag->expectArguments();
        $node = $tag->parser->parseArguments();
        
        return new class ($node) extends StatementNode {
            public function __construct(
                public readonly Node $node,
            ) {}

            public function print(PrintContext $context): string
            {
                if ($this->node instanceof ArrayNode && count($this->node->items) > 0) {
                    $code = '';
                    $first = true;
                    
                    foreach ($this->node->items as $item) {
                        $value = $item->value->print($context);
                        
                        if ($first) {
                            // First argument is the hook name
                            $code .= "do_action({$value}";
                            $first = false;
                        } else {
                            // Additional arguments
                            $code .= ", {$value}";
                        }
                    }
                    
                    $code .= ');';
                    return $code;
                }
                
                // Single argument (just the hook name)
                return "do_action({$this->node->print($context)});";
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
            public function __construct(
                public readonly Node $node,
            ) {}

            public function print(PrintContext $context): string
            {
                if ($this->node instanceof ArrayNode && count($this->node->items) > 0) {
                    $code = '';
                    $first = true;
                    
                    foreach ($this->node->items as $item) {
                        $value = $item->value->print($context);
                        
                        if ($first) {
                            // First argument is the filter name
                            $code .= "echo apply_filters({$value}";
                            $first = false;
                        } else {
                            // Additional arguments
                            $code .= ", {$value}";
                        }
                    }
                    
                    $code .= ');';
                    return $code;
                }
                
                // Single argument (just the filter name)
                return "echo apply_filters({$this->node->print($context)});";
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
            public function __construct(
                public readonly Node $node,
            ) {}

            public function print(PrintContext $context): string
            {
                if ($this->node instanceof ArrayNode && count($this->node->items) > 0) {
                    $code = '';
                    $first = true;
                    
                    foreach ($this->node->items as $item) {
                        $value = $item->value->print($context);
                        
                        if ($first) {
                            $code .= "body_class({$value}";
                            $first = false;
                        } else {
                            $code .= ", {$value}";
                        }
                    }
                    
                    $code .= ');';
                    return $code;
                }
                
                return "body_class({$this->node->print($context)});";
            }

            public function &getIterator(): \Generator
            {
                yield $this->node;
            }
        };
    }
} 