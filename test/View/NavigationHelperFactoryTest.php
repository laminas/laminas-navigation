<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View;

use Laminas\Navigation\View\NavigationHelperFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\Navigation as NavigationHelper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;

final class NavigationHelperFactoryTest extends TestCase
{
    public function testInvokeReturnsNavigationHelper(): void
    {
        $services = new ServiceManager();
        $helpers  = new HelperPluginManager($services);

        $factory = new NavigationHelperFactory();
        $result  = $factory($helpers, NavigationHelper::class);

        $this->assertInstanceOf(NavigationHelper::class, $result);
    }

    public function testCreateServiceV2ReturnsNavigationHelper(): void
    {
        $services = new ServiceManager();
        $helpers  = new HelperPluginManager($services);

        $factory = new NavigationHelperFactory();
        $result  = $factory->createService($helpers);

        $this->assertInstanceOf(NavigationHelper::class, $result);
    }
}
