<?php

namespace LaminasTest\Navigation\TestAsset;

class Router extends \Laminas\Router\Http\TreeRouteStack
{
    const RETURN_URL = 'spotify:track:2nd6CTjR9zjHGT0QtpfLHe';

    public function assemble(array $params = [], array $options = [])
    {
        return self::RETURN_URL;
    }
}
