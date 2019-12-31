<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\TestAsset;

class Router extends \Laminas\Mvc\Router\Http\TreeRouteStack
{
    const RETURN_URL = 'spotify:track:2nd6CTjR9zjHGT0QtpfLHe';

    public function assemble(array $params = array(), array $options = array())
    {
        return self::RETURN_URL;
    }
}
