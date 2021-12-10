<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Router\Http\TreeRouteStack;

class Router extends TreeRouteStack
{
    const RETURN_URL = 'spotify:track:2nd6CTjR9zjHGT0QtpfLHe';

    public function assemble(array $params = [], array $options = [])
    {
        return self::RETURN_URL;
    }
}
