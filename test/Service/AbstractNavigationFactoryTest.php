<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\Service;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router as MvcRouter;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service\AbstractNavigationFactory;
use Laminas\Router;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @todo Write tests covering full functionality. Tests were introduced to
 *     resolve zendframework/zend-navigation#37, and cover one specific
 *     method to ensure argument validation works correctly.
 */
class AbstractNavigationFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new TestAsset\TestNavigationFactory();
    }

    public function testCanInjectComponentsUsingLaminasRouterClasses()
    {
        $routeMatch = $this->prophesize(Router\RouteMatch::class)->reveal();
        $router = $this->prophesize(Router\RouteStackInterface::class)->reveal();
        $args = [[], $routeMatch, $router];

        $r = new ReflectionMethod($this->factory, 'injectComponents');
        $r->setAccessible(true);
        try {
            $pages = $r->invokeArgs($this->factory, $args);
        } catch (Exception\InvalidArgumentException $e) {
            $message = sprintf(
                'injectComponents should not raise exception for laminas-router classes; received %s',
                $e->getMessage()
            );
            $this->fail($message);
        }

        $this->assertSame([], $pages);
    }

    public function testCanInjectComponentsUsingLaminasMvcRouterClasses()
    {
        if (! class_exists(MvcRouter\RouteMatch::class)) {
            $this->markTestSkipped('Test does not run for laminas-mvc v3 releases');
        }

        $routeMatch = $this->prophesize(MvcRouter\RouteMatch::class)->reveal();
        $router = $this->prophesize(MvcRouter\RouteStackInterface::class)->reveal();
        $args = [[], $routeMatch, $router];

        $r = new ReflectionMethod($this->factory, 'injectComponents');
        $r->setAccessible(true);
        try {
            $pages = $r->invokeArgs($this->factory, $args);
        } catch (Exception\InvalidArgumentException $e) {
            $message = sprintf(
                'injectComponents should not raise exception for laminas-mvc router classes; received %s',
                $e->getMessage()
            );
            $this->fail($message);
        }

        $this->assertSame([], $pages);
    }

    public function testCanCreateNavigationInstanceV2()
    {
        $routerMatchClass = $this->getRouteMatchClass();
        $routerClass = $this->getRouterClass();
        $routeMatch = new $routerMatchClass([]);
        $router = new $routerClass();

        $mvcEventStub = new MvcEvent();
        $mvcEventStub->setRouteMatch($routeMatch);
        $mvcEventStub->setRouter($router);

        $applicationMock = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->getMock();

        $applicationMock->expects($this->any())
            ->method('getMvcEvent')
            ->willReturn($mvcEventStub);

        $serviceManagerMock = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', ['navigation' => ['testStubNavigation' => []]]],
                ['Application', $applicationMock]
            ]);

        $navigationFactory
            = $this->getMockForAbstractClass(AbstractNavigationFactory::class);
        $navigationFactory->expects($this->any())
            ->method('getName')
            ->willReturn('testStubNavigation');
        $navigation = $navigationFactory->createService($serviceManagerMock);

        $this->assertInstanceOf(Navigation::class, $navigation);
    }

    public function getRouterClass()
    {
        return class_exists(MvcRouter\Http\TreeRouteStack::class)
            ? MvcRouter\Http\TreeRouteStack::class
            : Router\Http\TreeRouteStack::class;
    }

    public function getRouteMatchClass()
    {
        return class_exists(MvcRouter\RouteMatch::class)
            ? MvcRouter\RouteMatch::class
            : Router\RouteMatch::class;
    }
}
