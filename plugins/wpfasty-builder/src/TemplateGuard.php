<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Php;
use Latte\Compiler\Nodes\Php\Expression;
use Latte\Compiler\Nodes\TemplateNode;
use Latte\Compiler\NodeTraverser;
use Latte\Essential\Nodes\CustomFunctionCallNode;
use Latte\SecurityViolationException;

/**
 * A compile-time allowlist for templates that come from outside the plugin (remote sources). Latte's own sandbox
 * forbids |noescape, which every generated block uses to print the HTML of its parts, so this pass checks the
 * same risks itself: only the tags {= capture var if elseif else foreach}, only registered ui_* functions and a
 * few pure PHP ones, the filters noescape/nocheck, and no objects (new, ->, ::, $this, variable variables) except
 * the $iterator counters.
 */
final class TemplateGuard extends \Latte\Extension
{
    private const TAGS = ['PrintNode', 'CaptureNode', 'VarNode', 'IfNode', 'ForeachNode'];
    private const ITERATOR = ['counter', 'counter0', 'first', 'last', 'odd', 'even'];
    private const FILTERS = ['noescape', 'nocheck', 'escape', 'trim'];
    private const PHP_FUNCTIONS = ['array_merge', 'str_contains'];
    private const FORBIDDEN = [
        Expression\NewNode::class, Expression\MethodCallNode::class, Expression\StaticMethodCallNode::class,
        Expression\StaticPropertyFetchNode::class, Expression\CloneNode::class,
        Expression\ClosureNode::class, Expression\ClassConstantFetchNode::class, Expression\InstanceofNode::class,
    ];

    /** @var array<string, true> */
    private array $functions = [];

    /** @param list<string> $functions the registered functions templates may call (ui_*) */
    public function __construct(array $functions)
    {
        foreach ([...$functions, ...self::PHP_FUNCTIONS] as $f) {
            $this->functions[strtolower($f)] = true;
        }
    }

    public function getPasses(): array
    {
        return ['wpfbGuard' => self::order($this->pass(...), before: '*')];
    }

    private function pass(TemplateNode $node): void
    {
        (new NodeTraverser())->traverse($node, leave: $this->visit(...));
    }

    private function visit(Node $node): Node
    {
        $class = $node::class;
        $short = substr((string) strrchr($class, '\\'), 1);
        if (str_starts_with(
            $class,
            'Latte\\Essential\\Nodes\\',
        ) && $node instanceof CustomFunctionCallNode === false && !in_array($short, self::TAGS, true)) {
            throw new SecurityViolationException(
                "Tag $short is not allowed in a remote template.",
                $node->position ?? null,
            );
        }
        foreach (self::FORBIDDEN as $f) {
            if ($node instanceof $f) {
                throw new SecurityViolationException(
                    "$short is not allowed in a remote template.",
                    $node->position ?? null,
                );
            }
        }
        if ($node instanceof Expression\PropertyFetchNode) {
            $ok = $node->object instanceof Expression\VariableNode && $node->object->name === 'iterator'
                && $node->name instanceof Php\IdentifierNode && in_array($node->name->name, self::ITERATOR, true);
            if (!$ok) {
                throw new SecurityViolationException(
                    'Only $iterator counters may be read in a remote template.',
                    $node->position,
                );
            }
        }
        if ($node instanceof Expression\VariableNode && (!is_string($node->name) || $node->name === 'this')) {
            throw new SecurityViolationException('Forbidden variable.', $node->position);
        }
        if ($node instanceof Expression\FunctionCallNode || $node instanceof CustomFunctionCallNode) {
            $name = $node->name instanceof Php\NameNode ? strtolower((string) $node->name) : '';
            $partial = $node instanceof Expression\FunctionCallNode && $node->isPartialFunction();
            if ($name === '' || !isset($this->functions[$name]) || $partial) {
                throw new SecurityViolationException(
                    "Function $name() is not allowed in a remote template.",
                    $node->position,
                );
            }
        }
        if ($node instanceof Php\FilterNode && !in_array(strtolower((string) $node->name), self::FILTERS, true)) {
            throw new SecurityViolationException(
                "Filter |{$node->name} is not allowed in a remote template.",
                $node->position,
            );
        }
        return $node;
    }
}
