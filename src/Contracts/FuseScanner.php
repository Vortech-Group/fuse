<?php

declare(strict_types=1);

namespace Vortech\Fuse\Contracts;

use Vortech\Fuse\Data\ScanResult;
use Vortech\Fuse\Exceptions\FuseScanException;

interface FuseScanner
{
    /**
     * @throws FuseScanException
     */
    public function scan(): ScanResult;
}
