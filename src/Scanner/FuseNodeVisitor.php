<?php

declare(strict_types=1);

namespace Vortech\Fuse\Scanner;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;
use Vortech\Fuse\Attributes\Fuse;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Data\FuseProblem;
use Vortech\Fuse\Enums\FuseProblemType;
use Vortech\Fuse\Exceptions\InvalidFuseAttribute;

/**
 * Collects every `#[Fuse]` attribute of a single file. Expects names to be resolved by the NameResolver first.
 */
final class FuseNodeVisitor extends NodeVisitorAbstract
{
    /** @var list<FuseItem> */
    private array $items = [];

    /** @var list<FuseProblem> */
    private array $problems = [];

    /** @var list<string> */
    private array $classes = [];

    public function __construct(
        private readonly AttributeResolver $resolver,
        private readonly string $file,
    ) {}

    public function enterNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            $this->classes[] = $node->namespacedName?->toString() ?? $node->name?->toString() ?? 'class@anonymous';
            $this->collect($node->attrGroups, $this->currentClass(), null, null);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->collect($node->attrGroups, $this->currentClass(), $node->name->toString(), null);

            // Promoted constructor parameters are properties, so their attributes count as property attributes.
            foreach ($node->params as $param) {
                if ($param->flags !== 0 && $param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                    $this->collect($param->attrGroups, $this->currentClass(), null, $param->var->name);
                }
            }

            return null;
        }

        if ($node instanceof Function_) {
            $this->collect($node->attrGroups, null, $node->namespacedName?->toString() ?? $node->name->toString(), null);

            return null;
        }

        if ($node instanceof Property) {
            foreach ($node->props as $property) {
                $this->collect($node->attrGroups, $this->currentClass(), null, $property->name->toString());
            }
        }

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classes);
        }

        return null;
    }

    /**
     * @return list<FuseItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return list<FuseProblem>
     */
    public function problems(): array
    {
        return $this->problems;
    }

    /**
     * @param  array<AttributeGroup>  $groups
     */
    private function collect(array $groups, ?string $class, ?string $method, ?string $property): void
    {
        foreach ($groups as $group) {
            foreach ($group->attrs as $attribute) {
                if (strcasecmp(ltrim($attribute->name->toString(), '\\'), Fuse::class) !== 0) {
                    continue;
                }

                try {
                    $this->items[] = $this->resolver->resolve($attribute, $this->file, $class, $method, $property);
                } catch (InvalidFuseAttribute $e) {
                    $this->problems[] = new FuseProblem(
                        FuseProblemType::InvalidAttribute,
                        $e->getMessage(),
                        $this->file,
                        $attribute->getStartLine(),
                    );
                }
            }
        }
    }

    private function currentClass(): ?string
    {
        return $this->classes === [] ? null : $this->classes[array_key_last($this->classes)];
    }
}
