<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\Page;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Page;
use Laminas\Navigation\Page\Mvc;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Literal as LiteralRoute;
use Laminas\Router\Http\Regex as RegexRoute;
use Laminas\Router\Http\Segment as SegmentRoute;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use LaminasTest\Navigation\TestAsset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function ksort;

/**
 * Tests the class Laminas_Navigation_Page_Mvc
 */
#[Group('Laminas_Navigation')]
final class MvcTest extends TestCase
{
    private RegexRoute $route;
    private TreeRouteStack $router;
    private RouteMatch $routeMatch;

    protected function setUp(): void
    {
        $this->route = new RegexRoute(
            '((?<controller>[^/]+)(/(?<action>[^/]+))?)',
            '/%controller%/%action%',
            [
                'controller' => 'index',
                'action'     => 'index',
            ]
        );

        $this->router = new TreeRouteStack();
        $this->router->addRoute('default', $this->route);

        $this->routeMatch = new RouteMatch([]);
        $this->routeMatch->setMatchedRouteName('default');
    }

    public function testHrefGeneratedByRouterWithDefaultRoute(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);
        Page\Mvc::setDefaultRoute('default');
        $page->setRouter($this->router);
        $page->setAction('view');
        $page->setController('news');

        $this->assertEquals('/news/view', $page->getHref());
    }

    public function testHrefGeneratedByRouterRequiresNoRoute(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);
        $page->setRouteMatch($this->routeMatch);
        $page->setRouter($this->router);
        $page->setAction('view');
        $page->setController('news');

        $this->assertEquals('/news/view', $page->getHref());
    }

    public function testHrefRouteMatchEnabledWithoutRouteMatchObject(): void
    {
        $page   = new Page\Mvc([
            'label'           => 'foo',
            'route'           => 'test/route',
            'use_route_match' => true,
        ]);
        $router = $this->createMock(TreeRouteStack::class);
        $router->expects($this->once())->method('assemble')->willReturn('/test/route');
        $page->setRouter($router);
        $this->assertEquals('/test/route', $page->getHref());
    }

    public function testHrefGeneratedIsRouteAware(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'myaction',
            'controller' => 'mycontroller',
            'route'      => 'myroute',
            'params'     => [
                'page' => 1337,
            ],
        ]);

        $route  = new RegexRoute(
            '(lolcat/(?<action>[^/]+)/(?<page>\d+))',
            '/lolcat/%action%/%page%',
            [
                'controller' => 'foobar',
                'action'     => 'bazbat',
                'page'       => 1,
            ]
        );
        $router = new TreeRouteStack();
        $router->addRoute('myroute', $route);

        $routeMatch = new RouteMatch([
            'controller' => 'foobar',
            'action'     => 'bazbat',
            'page'       => 1,
        ]);

        $page->setRouter($router);
        $page->setRouteMatch($routeMatch);

        $this->assertEquals('/lolcat/myaction/1337', $page->getHref());
    }

    public function testIsActiveReturnsTrueWhenMatchingRoute(): void
    {
        $page = new Page\Mvc([
            'label' => 'spiffyjrwashere',
            'route' => 'lolfish',
        ]);

        $route = new LiteralRoute('/lolfish');

        $router = new TreeRouteStack();
        $router->addRoute('lolfish', $route);

        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('lolfish');

        $page->setRouter($router);
        $page->setRouteMatch($routeMatch);

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsTrueWhenMatchingRouteWhileUsingModuleRouteListener(): void
    {
        $page = new Page\Mvc([
            'label'      => 'mpinkstonwashere',
            'route'      => 'roflcopter',
            'controller' => 'index',
        ]);

        $route = new Literal('/roflcopter');

        $router = new TreeRouteStack();
        $router->addRoute('roflcopter', $route);

        $routeMatch = new RouteMatch([
            ModuleRouteListener::MODULE_NAMESPACE => 'Application\Controller',
            'controller'                          => 'index',
        ]);
        $routeMatch->setMatchedRouteName('roflcopter');

        $event = new MvcEvent();
        $event->setRouter($router)
              ->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->onRoute($event);

        $page->setRouter($event->getRouter());
        $page->setRouteMatch($event->getRouteMatch());

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsFalseWhenMatchingRouteButNonMatchingParams(): void
    {
        $page       = new Page\Mvc([
            'label'  => 'foo',
            'route'  => 'bar',
            'action' => 'baz',
        ]);
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('bar');
        $routeMatch->setParam('action', 'qux');
        $page->setRouteMatch($routeMatch);

        $this->assertFalse($page->isActive());
    }

    public function testIsActiveReturnsFalseWhenNoRouteAndNoMatchedRouteNameIsSet(): void
    {
        $page = new Page\Mvc();

        $routeMatch = new RouteMatch([]);
        $page->setRouteMatch($routeMatch);

        $this->assertFalse($page->isActive());
    }

    #[Group('Laminas-8922')]
    public function testGetHrefWithFragmentIdentifier(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'fragment'   => 'qux',
            'controller' => 'mycontroller',
            'action'     => 'myaction',
            'route'      => 'myroute',
            'params'     => [
                'page' => 1337,
            ],
        ]);

        $route = new RegexRoute(
            '(lolcat/(?<action>[^/]+)/(?<page>\d+))',
            '/lolcat/%action%/%page%',
            [
                'controller' => 'foobar',
                'action'     => 'bazbat',
                'page'       => 1,
            ]
        );
        $this->router->addRoute('myroute', $route);
        $this->routeMatch->setMatchedRouteName('myroute');

        $page->setRouteMatch($this->routeMatch);
        $page->setRouter($this->router);

        $this->assertEquals('/lolcat/myaction/1337#qux', $page->getHref());
    }

    public function testGetHrefPassesQueryPartToRouter(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'query'      => 'foo=bar&baz=qux',
            'controller' => 'mycontroller',
            'action'     => 'myaction',
            'route'      => 'myroute',
            'params'     => [
                'page' => 1337,
            ],
        ]);

        $route = new RegexRoute(
            '(lolcat/(?<action>[^/]+)/(?<page>\d+))',
            '/lolcat/%action%/%page%',
            [
                'controller' => 'foobar',
                'action'     => 'bazbat',
                'page'       => 1,
            ]
        );
        $this->router->addRoute('myroute', $route);
        $this->routeMatch->setMatchedRouteName('myroute');

        $page->setRouteMatch($this->routeMatch);
        $page->setRouter($this->router);

        $this->assertEquals('/lolcat/myaction/1337?foo=bar&baz=qux', $page->getHref());

        // Test with array notation
        $page->setQuery([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
        $this->assertEquals('/lolcat/myaction/1337?foo=bar&baz=qux', $page->getHref());
    }

    public function testIsActiveReturnsTrueOnIdenticalControllerAction(): void
    {
        $page = new Page\Mvc([
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $routeMatch = new RouteMatch([
            'controller' => 'index',
            'action'     => 'index',
        ]);

        $page->setRouteMatch($routeMatch);

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsFalseOnDifferentControllerAction(): void
    {
        $page = new Page\Mvc([
            'action'     => 'bar',
            'controller' => 'index',
        ]);

        $routeMatch = new RouteMatch([
            'controller' => 'index',
            'action'     => 'index',
        ]);

        $page->setRouteMatch($routeMatch);

        $this->assertFalse($page->isActive());
    }

    public function testIsActiveReturnsTrueOnIdenticalIncludingPageParams(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'view',
            'controller' => 'post',
            'params'     => [
                'id' => '1337',
            ],
        ]);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        ]);

        $page->setRouteMatch($routeMatch);

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsTrueWhenRequestHasMoreParams(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'view',
            'controller' => 'post',
        ]);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        ]);

        $page->setRouteMatch($routeMatch);

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsFalseWhenRequestHasLessParams(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'view',
            'controller' => 'post',
            'params'     => [
                'id' => '1337',
            ],
        ]);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => null,
        ]);

        $page->setRouteMatch($routeMatch);

        $this->assertFalse($page->isActive());
    }

    /** @return array<string, array{0: mixed}> */
    public static function validActionValueProvider(): array
    {
        return [
            'string-index'   => ['index'],
            'string-help'    => ['help'],
            'string-home'    => ['home'],
            'string-default' => ['default'],
            'string-1'       => ['1'],
            'string-space'   => [' '],
            'string-empty'   => [''],
            'null'           => [null],
        ];
    }

    #[DataProvider('validActionValueProvider')]
    public function testSetActionAcceptsValidValue(mixed $value): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $page->setAction($value);
        $this->assertSame($value, $page->getAction());
    }

    /** @return array<string, array{0: mixed}> */
    public static function validControllerValueProvider(): array
    {
        return [
            'string-index'   => ['index'],
            'string-help'    => ['help'],
            'string-home'    => ['home'],
            'string-default' => ['default'],
            'string-1'       => ['1'],
            'string-space'   => [' '],
            'string-empty'   => [''],
            'null'           => [null],
        ];
    }

    #[DataProvider('validControllerValueProvider')]
    public function testSetControllerAcceptsValidValue(mixed $value): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $page->setController($value);
        $this->assertSame($value, $page->getController());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidActionValueProvider(): array
    {
        return [
            'integer' => [42],
            'object'  => [(object) null],
        ];
    }

    #[DataProvider('invalidActionValueProvider')]
    public function testSetActionThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $page->setAction($invalid);
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidControllerValueProvider(): array
    {
        return [
            'integer' => [42],
            'object'  => [(object) null],
        ];
    }

    #[DataProvider('invalidControllerValueProvider')]
    public function testSetControllerThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $page->setController($invalid);
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidRouteValueProvider(): array
    {
        return [
            'integer' => [42],
            'object'  => [(object) null],
        ];
    }

    #[DataProvider('invalidRouteValueProvider')]
    public function testSetRouteThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $page->setRoute($invalid);
    }

    /** @return array<string, array{0: mixed}> */
    public static function validRouteValueProvider(): array
    {
        return [
            'string-index'   => ['index'],
            'string-help'    => ['help'],
            'string-home'    => ['home'],
            'string-default' => ['default'],
            'string-1'       => ['1'],
            'string-space'   => [' '],
            'null'           => [null],
        ];
    }

    #[DataProvider('validRouteValueProvider')]
    public function testSetRouteAcceptsValidValue(mixed $value): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $page->setRoute($value);
        $this->assertSame($value, $page->getRoute());
    }

    public function testSetAndGetParams(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $params = ['foo' => 'bar', 'baz' => 'bat'];

        $page->setParams($params);
        $this->assertEquals($params, $page->getParams());

        $page->setParams();
        $this->assertEquals([], $page->getParams());

        $page->setParams($params);
        $this->assertEquals($params, $page->getParams());

        $page->setParams([]);
        $this->assertEquals([], $page->getParams());
    }

    public function testToArrayMethod(): void
    {
        $options = [
            'label'       => 'foo',
            'action'      => 'index',
            'controller'  => 'index',
            'fragment'    => 'bar',
            'id'          => 'my-id',
            'class'       => 'my-class',
            'title'       => 'my-title',
            'target'      => 'my-target',
            'order'       => 100,
            'active'      => true,
            'visible'     => false,
            'foo'         => 'bar',
            'meaning'     => 42,
            'router'      => $this->router,
            'route_match' => $this->routeMatch,
        ];

        $page = new Page\Mvc($options);

        $toArray = $page->toArray();

        $options['route']  = null;
        $options['params'] = [];
        $options['rel']    = [];
        $options['rev']    = [];

        $options['privilege']  = null;
        $options['resource']   = null;
        $options['permission'] = null;
        $options['pages']      = [];
        $options['type']       = Mvc::class;

        ksort($options);
        ksort($toArray);
        $this->assertEquals($options, $toArray);
    }

    public function testSpecifyingAnotherUrlHelperToGenerateHrefs(): void
    {
        $newRouter = new TestAsset\Router();

        $page = new Page\Mvc([
            'route' => 'default',
        ]);
        $page->setRouter($newRouter);

        $expected = TestAsset\Router::RETURN_URL;
        $actual   = $page->getHref();

        $this->assertEquals($expected, $actual);
    }

    public function testDefaultRouterCanBeSetWithConstructor(): void
    {
        $page = new Page\Mvc([
            'label'         => 'foo',
            'action'        => 'index',
            'controller'    => 'index',
            'defaultRouter' => $this->router,
        ]);

        $this->assertEquals($this->router, $page::getDefaultRouter());
        $page::setDefaultRouter(null);
    }

    public function testDefaultRouterCanBeSetWithGetter(): void
    {
        $page = new Page\Mvc([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);
        $page::setDefaultRouter($this->router);

        $this->assertEquals($this->router, $page::getDefaultRouter());
        $page::setDefaultRouter(null);
    }

    public function testNoExceptionForGetHrefIfDefaultRouterIsSet(): void
    {
        $page = new Page\Mvc([
            'label'         => 'foo',
            'action'        => 'index',
            'controller'    => 'index',
            'route'         => 'default',
            'defaultRouter' => $this->router,
        ]);

        // If the default router is not used an exception will be thrown.
        // This method intentionally has no assertion.
        $this->assertNotEmpty($page->getHref());
        $page::setDefaultRouter(null);
    }

    public function testBoolSetAndGetUseRouteMatch(): void
    {
        $page = new Page\Mvc([
            'useRouteMatch' => 2,
        ]);
        $this->assertTrue($page->useRouteMatch());

        $page->setUseRouteMatch(null);
        $this->assertFalse($page->useRouteMatch());

        $page->setUseRouteMatch(false);
        $this->assertFalse($page->useRouteMatch());

        $page->setUseRouteMatch(true);
        $this->assertTrue($page->useRouteMatch());

        $page->setUseRouteMatch();
        $this->assertTrue($page->useRouteMatch());
    }

    public function testMvcPageParamsInheritRouteMatchParams(): void
    {
        $page = new Page\Mvc([
            'label' => 'lollerblades',
            'route' => 'lollerblades',
        ]);

        $route = new SegmentRoute('/lollerblades/view[/:serialNumber]');

        $router = new TreeRouteStack();
        $router->addRoute('lollerblades', $route);

        $routeMatch = new RouteMatch([
            'serialNumber' => 23,
        ]);
        $routeMatch->setMatchedRouteName('lollerblades');

        $page->setRouter($router);
        $page->setRouteMatch($routeMatch);

        $this->assertEquals('/lollerblades/view', $page->getHref());

        $page->setUseRouteMatch(true);
        $this->assertEquals('/lollerblades/view/23', $page->getHref());
    }

    public function testInheritedRouteMatchParamsWorkWithModuleRouteListener(): void
    {
        $page = new Page\Mvc([
            'label' => 'mpinkstonwashere',
            'route' => 'lmaoplane',
        ]);

        $route = new SegmentRoute('/lmaoplane[/:controller]');

        $router = new TreeRouteStack();
        $router->addRoute('lmaoplane', $route);

        $routeMatch = new RouteMatch([
            ModuleRouteListener::MODULE_NAMESPACE => 'Application\Controller',
            'controller'                          => 'index',
        ]);
        $routeMatch->setMatchedRouteName('lmaoplane');

        $event = new MvcEvent();
        $event->setRouter($router)
            ->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->onRoute($event);

        $page->setRouter($event->getRouter());
        $page->setRouteMatch($event->getRouteMatch());

        $this->assertEquals('/lmaoplane', $page->getHref());

        $page->setUseRouteMatch(true);
        $this->assertEquals('/lmaoplane/index', $page->getHref());
    }

    public function testMistakeDetectIsActiveOnIndexController(): void
    {
        $page = new Page\Mvc(
            [
                'label' => 'some Label',
                'route' => 'myRoute',
            ]
        );

        $route = new LiteralRoute('/foo');

        $router = new TreeRouteStack();
        $router->addRoute('myRoute', $route);

        $routeMatch = new RouteMatch(
            [
                ModuleRouteListener::MODULE_NAMESPACE => 'Application\Controller',
                'controller'                          => 'index',
                'action'                              => 'index',
            ]
        );
        $routeMatch->setMatchedRouteName('index');

        $event = new MvcEvent();
        $event->setRouter($router)
            ->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->onRoute($event);

        $page->setRouter($event->getRouter());
        $page->setRouteMatch($event->getRouteMatch());

        $this->assertFalse($page->isActive());
    }

    public function testRecursiveDetectIsActiveWhenRouteNameIsKnown(): void
    {
        $parentPage = new Page\Mvc(
            [
                'label' => 'some Label',
                'route' => 'parentPageRoute',
            ]
        );
        $childPage  = new Page\Mvc(
            [
                'label' => 'child',
                'route' => 'childPageRoute',
            ]
        );
        $parentPage->addPage($childPage);

        $router = new TreeRouteStack();
        $router->addRoutes(
            [
                'parentPageRoute' => [
                    'type'    => 'literal',
                    'options' => [
                        'route'    => '/foo',
                        'defaults' => [
                            'controller' => 'fooController',
                            'action'     => 'fooAction',
                        ],
                    ],
                ],
                'childPageRoute'  => [
                    'type'    => 'literal',
                    'options' => [
                        'route'    => '/bar',
                        'defaults' => [
                            'controller' => 'barController',
                            'action'     => 'barAction',
                        ],
                    ],
                ],
            ]
        );

        $routeMatch = new RouteMatch(
            [
                ModuleRouteListener::MODULE_NAMESPACE => 'Application\Controller',
                'controller'                          => 'barController',
                'action'                              => 'barAction',
            ]
        );
        $routeMatch->setMatchedRouteName('childPageRoute');

        $event = new MvcEvent();
        $event->setRouter($router)
            ->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->onRoute($event);

        $parentPage->setRouter($event->getRouter());
        $parentPage->setRouteMatch($event->getRouteMatch());

        $childPage->setRouter($event->getRouter());
        $childPage->setRouteMatch($event->getRouteMatch());

        $this->assertTrue($childPage->isActive(true));
        $this->assertTrue($parentPage->isActive(true));
    }

    public function testSetRouteMatchThrowsExceptionOnInvalidParameter(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);

        $page = new Page\Mvc();
        $page->setRouter(TreeRouteStack::class);
        $page->setRouteMatch(null);
    }

    public function testDefaultRouteCanBeSetAndRetrieved(): void
    {
        $originalRoute = Page\Mvc::getDefaultRoute();

        Page\Mvc::setDefaultRoute('custom-route');
        $this->assertSame('custom-route', Page\Mvc::getDefaultRoute());

        Page\Mvc::setDefaultRoute($originalRoute);
    }

    public function testGetHrefReturnsCachedValue(): void
    {
        $page = new Page\Mvc([
            'label'  => 'foo',
            'route'  => 'default',
            'action' => 'index',
        ]);
        $page->setRouter($this->router);

        $href1 = $page->getHref();
        $href2 = $page->getHref();

        $this->assertSame($href1, $href2);
    }

    public function testGetHrefThrowsExceptionWhenNoRouter(): void
    {
        Page\Mvc::setDefaultRouter(null);

        $page = new Page\Mvc([
            'label' => 'foo',
            'route' => 'default',
        ]);

        $this->expectException(Exception\DomainException::class);
        $this->expectExceptionMessage('cannot execute as no Laminas\Router\RouteStackInterface instance is composed');
        $page->getHref();
    }

    public function testGetHrefUsesRouteMatchWhenNoRouteSet(): void
    {
        $originalDefaultRoute = Page\Mvc::getDefaultRoute();
        Page\Mvc::setDefaultRoute(null);

        $page = new Page\Mvc([
            'label'      => 'foo',
            'controller' => 'index',
            'action'     => 'index',
        ]);

        $page->setRouteMatch($this->routeMatch);
        $page->setRouter($this->router);

        $href = $page->getHref();
        $this->assertIsString($href);

        Page\Mvc::setDefaultRoute($originalDefaultRoute);
    }

    public function testGetHrefThrowsExceptionWhenNoRouteName(): void
    {
        Page\Mvc::setDefaultRoute(null);
        Page\Mvc::setDefaultRouter(null);

        $page = new Page\Mvc([
            'label' => 'foo',
        ]);
        $page->setRouter($this->router);

        $this->expectException(Exception\DomainException::class);
        $this->expectExceptionMessage('No route name could be found');
        $page->getHref();
    }

    public function testSetRouteMatchThrowsExceptionForInvalidObject(): void
    {
        $page = new Page\Mvc([
            'label' => 'foo',
        ]);

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('RouteMatch passed to');
        $page->setRouteMatch((object) ['invalid' => 'object']);
    }
}
