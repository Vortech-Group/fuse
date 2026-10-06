<?php

declare(strict_types=1);

namespace Vortech\Fuse\Scanner;

use Carbon\CarbonImmutable;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use UnitEnum;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Exceptions\InvalidFuseAttribute;
use Vortech\Fuse\Support\Expiration;

/**
 * Turns a parsed `#[Fuse(...)]` attribute node into a {@see FuseItem} without ever loading the attributed class.
 */
final class AttributeResolver
{
    /** Constructor parameters of the Fuse attribute, in positional order. */
    private const array PARAMETERS = [
        'reason', 'expires', 'owner', 'issue', 'severity', 'type', 'replacement', 'created',
    ];

    /**
     * @throws InvalidFuseAttribute
     */
    public function resolve(Attribute $attribute, string $file, ?string $class, ?string $method, ?string $property): FuseItem
    {
        $arguments = $this->arguments($attribute);

        $reason = $this->requiredString($arguments, 'reason');
        $expires = $this->requiredString($arguments, 'expires');

        $expiresAt = Expiration::parse($expires)
            ?? throw new InvalidFuseAttribute(sprintf('Invalid expiration date "%s". Use the strict YYYY-MM-DD format.', $expires));

        $created = $this->optionalString($arguments, 'created');
        $createdAt = $created === null ? null : $this->createdAt($created);

        return new FuseItem(
            reason: $reason,
            expiresAt: $expiresAt,
            file: $file,
            line: $attribute->getStartLine(),
            class: $class,
            method: $method,
            property: $property,
            owner: $this->optionalString($arguments, 'owner'),
            issue: $this->optionalString($arguments, 'issue'),
            severity: $this->enum($arguments, 'severity', FuseSeverity::class) ?? FuseSeverity::Medium,
            type: $this->enum($arguments, 'type', FuseType::class) ?? FuseType::TechnicalDebt,
            replacement: $this->optionalString($arguments, 'replacement'),
            createdAt: $createdAt,
        );
    }

    /**
     * @return array<string, Expr>
     */
    private function arguments(Attribute $attribute): array
    {
        $arguments = [];

        foreach ($attribute->args as $position => $arg) {
            if ($arg->unpack) {
                throw new InvalidFuseAttribute('Argument unpacking is not supported in a Fuse attribute.');
            }

            $name = $arg->name instanceof Identifier ? $arg->name->toString() : (self::PARAMETERS[$position] ?? null);

            if ($name === null || ! in_array($name, self::PARAMETERS, true)) {
                throw new InvalidFuseAttribute(sprintf('Unknown Fuse argument "%s".', $arg->name instanceof Identifier ? $name : '#'.($position + 1)));
            }

            if (isset($arguments[$name])) {
                throw new InvalidFuseAttribute(sprintf('Fuse argument "%s" is passed more than once.', $name));
            }

            $arguments[$name] = $arg->value;
        }

        return $arguments;
    }

    /**
     * @param  array<string, Expr>  $arguments
     */
    private function requiredString(array $arguments, string $name): string
    {
        $value = $this->optionalString($arguments, $name);

        return $value ?? throw new InvalidFuseAttribute(sprintf('Missing required Fuse argument "%s".', $name));
    }

    /**
     * @param  array<string, Expr>  $arguments
     */
    private function optionalString(array $arguments, string $name): ?string
    {
        if (! isset($arguments[$name])) {
            return null;
        }

        $expression = $arguments[$name];

        if ($expression instanceof ConstFetch && strtolower($expression->name->toString()) === 'null') {
            return null;
        }

        $value = $this->literalString($expression)
            ?? throw new InvalidFuseAttribute(sprintf('Fuse argument "%s" must be a string literal.', $name));

        if (trim($value) === '') {
            throw new InvalidFuseAttribute(sprintf('Fuse argument "%s" must not be empty.', $name));
        }

        return $value;
    }

    private function literalString(Expr $expression): ?string
    {
        if ($expression instanceof String_) {
            return $expression->value;
        }

        if ($expression instanceof Concat) {
            $left = $this->literalString($expression->left);
            $right = $this->literalString($expression->right);

            return $left === null || $right === null ? null : $left.$right;
        }

        return null;
    }

    /**
     * @template T of UnitEnum
     *
     * @param  array<string, Expr>  $arguments
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function enum(array $arguments, string $name, string $enum): ?UnitEnum
    {
        if (! isset($arguments[$name])) {
            return null;
        }

        $expression = $arguments[$name];
        $short = substr($enum, (int) strrpos($enum, '\\') + 1);

        if (
            ! $expression instanceof ClassConstFetch
            || ! $expression->class instanceof Name
            || ! $expression->name instanceof Identifier
            || ltrim($expression->class->toString(), '\\') !== $enum
        ) {
            throw new InvalidFuseAttribute(sprintf('Fuse argument "%s" must be a %s enum case.', $name, $short));
        }

        $case = $expression->name->toString();
        $constant = $enum.'::'.$case;

        if (! defined($constant) || ! (constant($constant) instanceof $enum)) {
            throw new InvalidFuseAttribute(sprintf('Unsupported %s case "%s".', $short, $case));
        }

        /** @var T */
        return constant($constant);
    }

    private function createdAt(string $created): CarbonImmutable
    {
        return Expiration::parse($created)
            ?? throw new InvalidFuseAttribute(sprintf('Invalid creation date "%s". Use the strict YYYY-MM-DD format.', $created));
    }
}
