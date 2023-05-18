<?php

declare(strict_types=1);

namespace PeibinLaravel\Guzzle;

interface MiddlewareInterface
{
    public function getMiddleware(): callable;
}
