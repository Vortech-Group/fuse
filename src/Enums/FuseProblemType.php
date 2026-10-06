<?php

declare(strict_types=1);

namespace Vortech\Fuse\Enums;

enum FuseProblemType: string
{
    /** A Fuse attribute with missing, malformed or unsupported metadata. */
    case InvalidAttribute = 'invalid-attribute';

    /** A source file that could not be parsed. */
    case ParseError = 'parse-error';
}
