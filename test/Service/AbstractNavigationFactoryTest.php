<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\Service;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Navigation;
use Laminas\Router;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class AbstractNavigationFactoryTest extends TestCase
{
    private TestAsset\TestNavigationFactory $factory;

    public function setUp(): void
    {
        $this->factory = new TestAsset\TestNavigationFactory();
    }

    public function testCanInjectComponentsUsingLaminasRouterClasses(): void
    {
        $routeMatch = $this->createMock(Router\RouteMatch::class);
        $router     = $this->createMock(Router\RouteStackInterface::class);
        $args       = [[], $routeMatch, $router];

        $r     = new ReflectionMethod($this->factory, 'injectComponents');
        $pages = $r->invokeArgs($this->factory, $args);

        $this->assertSame([], $pages);
    }

    public function testCanCreateNavigationInstanceV2(): void
    {
        $mvcEventStub = new MvcEvent();
        $mvcEventStub->setRouteMatch(new Router\RouteMatch([]));
        /** @psalm-suppress InvalidArgument */
        $mvcEventStub->setRouter(new Router\Http\TreeRouteStack());

        $applicationStub = $this->createStub(Application::class);
        $applicationStub->method('getMvcEvent')->willReturn($mvcEventStub);

        $serviceManagerStub = $this->createStub(ServiceManager::class);
        $serviceManagerStub->method('get')->willReturnMap([
            ['config', ['navigation' => ['testStubNavigation' => []]]],
            ['Application', $applicationStub],
        ]);

        $navigationFactory = new TestAsset\TestNavigationFactory('testStubNavigation');
        $navigation        = $navigationFactory->createService($serviceManagerStub);

        $this->assertInstanceOf(Navigation::class, $navigation);
    }

    public function testThrowsExceptionWhenNavigationConfigKeyMissing(): void
    {
        $serviceManagerStub = $this->createStub(ServiceManager::class);
        $serviceManagerStub->method('get')->willReturn([]);

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find navigation configuration key');

        $this->factory->createService($serviceManagerStub);
    }

    public function testThrowsExceptionWhenNavigationContainerNotFound(): void
    {
        $serviceManagerStub = $this->createStub(ServiceManager::class);
        $serviceManagerStub->method('get')->willReturn(['navigation' => []]);

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to find a navigation container by the name "test"');

        $this->factory->createService($serviceManagerStub);
    }

    public function testInjectComponentsThrowsExceptionForInvalidRouteMatch(): void
    {
        $r = new ReflectionMethod($this->factory, 'injectComponents');

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Laminas\Router\RouteMatch expected');

        $r->invokeArgs($this->factory, [[], 'invalid-route-match', null]);
    }

    public function testInjectComponentsThrowsExceptionForInvalidRouter(): void
    {
        $r = new ReflectionMethod($this->factory, 'injectComponents');

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Laminas\Router\RouteMatch expected');

        $r->invokeArgs($this->factory, [[], null, 'invalid-router']);
    }

    public function testGetPagesFromConfigThrowsExceptionForNonExistentFile(): void
    {
        $r = new ReflectionMethod($this->factory, 'getPagesFromConfig');

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $r->invokeArgs($this->factory, ['/non/existent/file.php']);
    }

    public function testGetPagesFromConfigThrowsExceptionForInvalidType(): void
    {
        $r = new ReflectionMethod($this->factory, 'getPagesFromConfig');

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input, expected array, filename, or Traversable object');

        $r->invokeArgs($this->factory, [12345]);
    }

    public function testInjectComponentsWithNullRouter(): void
    {
        $routeMatch = $this->createMock(Router\RouteMatch::class);
        $r          = new ReflectionMethod($this->factory, 'injectComponents');

        $pages = $r->invokeArgs($this->factory, [[], $routeMatch, null]);

        $this->assertSame([], $pages);
    }
}
