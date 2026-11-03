<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\StatementNode;

/** A Latte statement without child nodes (the WordPress tags of WordPressExtension). */
abstract class AbstractLeafStatementNode extends StatementNode
{
    /** @var list<Node> */
    private array $children = [];

    public function &getIterator(): \Generator
    {
        foreach ($this->children as &$child) {
            yield $child;
        }
    }
}
