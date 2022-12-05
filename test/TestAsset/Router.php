<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Router\Http\RouteInterface;
use Laminas\Router\Http\TreeRouteStack;

/** @extends TreeRouteStack<RouteInterface> */
class Router extends TreeRouteStack
{
    public const RETURN_URL = 'spotify:track:2nd6CTjR9zjHGT0QtpfLHe';

    public function assemble(array $params = [], array $options = []): string
    {
        return self::RETURN_URL;
    }
}
