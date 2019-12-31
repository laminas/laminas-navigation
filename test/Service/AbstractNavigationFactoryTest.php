<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\Service;

use Laminas\Mvc\Router as MvcRouter;
use Laminas\Navigation\Exception;
use Laminas\Router;
use PHPUnit_Framework_TestCase as TestCase;
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
}
