<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\Service;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service\AbstractNavigationFactory;
use Laminas\Router;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function sprintf;

/**
 * @todo Write tests covering full functionality. Tests were introduced to
 *     resolve zendframework/zend-navigation#37, and cover one specific
 *     method to ensure argument validation works correctly.
 */
class AbstractNavigationFactoryTest extends TestCase
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

        $r = new ReflectionMethod($this->factory, 'injectComponents');
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

    public function testCanCreateNavigationInstanceV2()
    {
        $mvcEventStub = new MvcEvent();
        $mvcEventStub->setRouteMatch(new Router\RouteMatch([]));
        $mvcEventStub->setRouter(new Router\Http\TreeRouteStack());

        $applicationMock = $this->createMock(Application::class);

        $applicationMock->expects($this->any())
            ->method('getMvcEvent')
            ->willReturn($mvcEventStub);

        $serviceManagerMock = $this->createMock(ServiceManager::class);

        $serviceManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', ['navigation' => ['testStubNavigation' => []]]],
                ['Application', $applicationMock],
            ]);

        $navigationFactory
            = $this->getMockForAbstractClass(AbstractNavigationFactory::class);
        $navigationFactory->expects($this->any())
            ->method('getName')
            ->willReturn('testStubNavigation');
        $navigation = $navigationFactory->createService($serviceManagerMock);

        $this->assertInstanceOf(Navigation::class, $navigation);
    }
}
