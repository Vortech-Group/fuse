<?php

declare(strict_types=1);

namespace Vortech\Fuse\Attributes;

use Attribute;

#[Attribute(
    Attribute::TARGET_CLASS
    | Attribute::TARGET_METHOD
    | Attribute::TARGET_FUNCTION
    | Attribute::TARGET_PROPERTY
    | Attribute::IS_REPEATABLE
)]
final readonly class TechnicalDebt extends FuseAttribute {}
