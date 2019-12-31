<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\View;

use Laminas\Navigation\View\ViewHelperManagerDelegatorFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\Navigation as NavigationHelper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;

class ViewHelperManagerDelegatorFactoryTest extends TestCase
{
    public function testFactoryConfiguresViewHelperManagerWithNavigationHelpers()
    {
        $services = new ServiceManager();
        $helpers = new HelperPluginManager($services);
        $callback = function () use ($helpers) {
            return $helpers;
        };

        $factory = new ViewHelperManagerDelegatorFactory();
        $this->assertSame($helpers, $factory($services, 'ViewHelperManager', $callback));

        $this->assertTrue($helpers->has('navigation'));
        $this->assertTrue($helpers->has(NavigationHelper::class));
    }
}
