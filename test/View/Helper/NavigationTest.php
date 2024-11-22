<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View\Helper;

use Laminas\I18n\Translator\Translator;
use Laminas\Navigation\Navigation as Container;
use Laminas\Navigation\Page;
use Laminas\Navigation\View\Helper\AbstractHelper;
use Laminas\Navigation\View\Helper\Navigation;
use Laminas\Navigation\View\Helper\PluginManager;
use Laminas\Permissions\Acl;
use Laminas\Permissions\Acl\Role;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use stdClass;

use function extension_loaded;
use function restore_error_handler;
use function set_error_handler;
use function spl_object_hash;
use function str_replace;

use const PHP_EOL;

/**
 * @psalm-suppress MissingConstructor
 */
class NavigationTest extends AbstractTestCase
{
    private ?string $errorHandlerMessage = null;

    protected function setUp(): void
    {
        $this->helper = new Navigation();
        parent::setUp();
    }

    public function testHelperEntryPointWithoutAnyParams(): void
    {
        $returned = $this->helper->__invoke();
        self::assertEquals($this->helper, $returned);
        self::assertEquals($this->nav1, $returned->getContainer());
    }

    public function testHelperEntryPointWithContainerParam(): void
    {
        $returned = $this->helper->__invoke($this->nav2);
        self::assertEquals($this->helper, $returned);
        self::assertEquals($this->nav2, $returned->getContainer());
    }

    public function testAcceptAclShouldReturnGracefullyWithUnknownResource(): void
    {
        // setup
        $acl = $this->getAcl();
        $this->helper->setAcl($acl['acl']);
        $this->helper->setRole($acl['role']);

        $accepted = $this->helper->accept(
            new Page\Uri([
                'resource'  => 'unknownresource',
                'privilege' => 'someprivilege',
            ], false)
        );

        self::assertEquals($accepted, false);
    }

    public function testShouldProxyToMenuHelperByDefault(): void
    {
        $this->helper->setContainer($this->nav1);
        $this->helper->setServiceLocator(new ServiceManager());

        // result
        $expected = $this->getExpectedFileContents('menu/default1.html');
        $actual   = $this->helper->render();

        self::assertEquals($expected, $actual);
    }

    public function testHasContainer(): void
    {
        $oldContainer = $this->helper->getContainer();
        $this->helper->setContainer(null);
        self::assertFalse($this->helper->hasContainer());
        $this->helper->setContainer($oldContainer);
    }

    public function testInjectingContainer(): void
    {
        // setup
        $this->helper->setContainer($this->nav2);
        $this->helper->setServiceLocator(new ServiceManager());
        $expected = [
            'menu'        => $this->getExpectedFileContents('menu/default2.html'),
            'breadcrumbs' => $this->getExpectedFileContents('bc/default.html'),
        ];
        $actual   = [];

        // result
        $actual['menu'] = $this->helper->render();
        $this->helper->setContainer($this->nav1);
        $actual['breadcrumbs'] = $this->helper->breadcrumbs()->render();

        self::assertEquals($expected, $actual);
    }

    public function testDisablingContainerInjection(): void
    {
        // setup
        $this->helper->setServiceLocator(new ServiceManager());
        $this->helper->setInjectContainer(false);
        $this->helper->menu()->setContainer(null);
        $this->helper->breadcrumbs()->setContainer(null);
        $this->helper->setContainer($this->nav2);

        // result
        $expected = [
            'menu'        => '',
            'breadcrumbs' => '',
        ];
        $actual   = [
            'menu'        => $this->helper->render(),
            'breadcrumbs' => $this->helper->breadcrumbs()->render(),
        ];

        self::assertEquals($expected, $actual);
    }

    public function testMultipleNavigationsAndOneMenuDisplayedTwoTimes(): void
    {
        $this->helper->setServiceLocator(new ServiceManager());
        $expected = $this->helper->setContainer($this->nav1)->menu()->getContainer();
        $this->helper->setContainer($this->nav2)->menu()->getContainer();
        $actual = $this->helper->setContainer($this->nav1)->menu()->getContainer();

        self::assertEquals($expected, $actual);
    }

    public function testServiceManagerIsUsedToRetrieveContainer(): void
    {
        $container      = new Container();
        $serviceManager = new ServiceManager();
        $serviceManager->setService('navigation', $container);

        new View\HelperPluginManager($serviceManager);

        $this->helper->setServiceLocator($serviceManager);
        $this->helper->setContainer('navigation');

        $expected = $this->helper->getContainer();
        $actual   = $container;
        self::assertEquals($expected, $actual);
    }

    public function testInjectingAcl(): void
    {
        // setup
        $acl = $this->getAcl();
        $this->helper->setAcl($acl['acl']);
        $this->helper->setRole($acl['role']);
        $this->helper->setServiceLocator(new ServiceManager());

        $expected = $this->getExpectedFileContents('menu/acl.html');
        $actual   = $this->helper->render();

        self::assertEquals($expected, $actual);
    }

    public function testDisablingAclInjection(): void
    {
        // setup
        $acl = $this->getAcl();
        $this->helper->setAcl($acl['acl']);
        $this->helper->setRole($acl['role']);
        $this->helper->setInjectAcl(false);
        $this->helper->setServiceLocator(new ServiceManager());

        $expected = $this->getExpectedFileContents('menu/default1.html');
        $actual   = $this->helper->render();

        self::assertEquals($expected, $actual);
    }

    public function testInjectingTranslator(): void
    {
        if (! extension_loaded('intl')) {
            self::markTestSkipped('ext/intl not enabled');
        }

        $this->helper->setTranslator($this->getTranslator());
        $this->helper->setServiceLocator(new ServiceManager());

        $expected = $this->getExpectedFileContents('menu/translated.html');
        $actual   = $this->helper->render();

        self::assertEquals($expected, $actual);
    }

    public function testDisablingTranslatorInjection(): void
    {
        $this->helper->setTranslator($this->getTranslator());
        $this->helper->setInjectTranslator(false);
        $this->helper->setServiceLocator(new ServiceManager());

        $expected = $this->getExpectedFileContents('menu/default1.html');
        $actual   = $this->helper->render();

        self::assertEquals($expected, $actual);
    }

    public function testTranslatorMethods(): void
    {
        $translatorMock = $this->createMock(Translator::class);
        $this->helper->setTranslator($translatorMock, 'foo');

        self::assertEquals($translatorMock, $this->helper->getTranslator());
        self::assertEquals('foo', $this->helper->getTranslatorTextDomain());
        self::assertTrue($this->helper->hasTranslator());
        self::assertTrue($this->helper->isTranslatorEnabled());

        $this->helper->setTranslatorEnabled(false);
        self::assertFalse($this->helper->isTranslatorEnabled());
    }

    public function testSpecifyingDefaultProxy(): void
    {
        $expected = [
            'breadcrumbs' => $this->getExpectedFileContents('bc/default.html'),
            'menu'        => $this->getExpectedFileContents('menu/default1.html'),
        ];
        $actual   = [];

        // result
        $this->helper->setServiceLocator(new ServiceManager());
        $this->helper->setDefaultProxy('breadcrumbs');
        $actual['breadcrumbs'] = $this->helper->render($this->nav1);
        $this->helper->setDefaultProxy('menu');
        $actual['menu'] = $this->helper->render($this->nav1);

        self::assertEquals($expected, $actual);
    }

    public function testGetAclReturnsNullIfNoAclInstance(): void
    {
        self::assertNull($this->helper->getAcl());
    }

    public function testGetAclReturnsAclInstanceSetWithSetAcl(): void
    {
        $acl = new Acl\Acl();
        $this->helper->setAcl($acl);
        self::assertEquals($acl, $this->helper->getAcl());
    }

    public function testGetAclReturnsAclInstanceSetWithSetDefaultAcl(): void
    {
        $acl = new Acl\Acl();
        AbstractHelper::setDefaultAcl($acl);
        $actual = $this->helper->getAcl();
        AbstractHelper::setDefaultAcl(null);
        self::assertEquals($acl, $actual);
    }

    public function testSetDefaultAclAcceptsNull(): void
    {
        $acl = new Acl\Acl();
        AbstractHelper::setDefaultAcl($acl);
        AbstractHelper::setDefaultAcl(null);
        self::assertNull($this->helper->getAcl());
    }

    public function testSetDefaultAclAcceptsNoParam(): void
    {
        $acl = new Acl\Acl();
        AbstractHelper::setDefaultAcl($acl);
        AbstractHelper::setDefaultAcl();
        self::assertNull($this->helper->getAcl());
    }

    public function testSetRoleAcceptsString(): void
    {
        $this->helper->setRole('member');
        self::assertEquals('member', $this->helper->getRole());
    }

    public function testSetRoleAcceptsRoleInterface(): void
    {
        $role = new Role\GenericRole('member');
        $this->helper->setRole($role);
        self::assertEquals($role, $this->helper->getRole());
    }

    public function testSetRoleAcceptsNull(): void
    {
        $this->helper->setRole('member')->setRole(null);
        self::assertNull($this->helper->getRole());
    }

    public function testSetRoleAcceptsNoParam(): void
    {
        $this->helper->setRole('member')->setRole();
        self::assertNull($this->helper->getRole());
    }

    public function testSetRoleThrowsExceptionWhenGivenAnInt(): void
    {
        try {
            $this->helper->setRole(1337);
            self::fail('An invalid argument was given, but a '
                        . 'Laminas\View\Exception\InvalidArgumentException was not thrown');
        } catch (View\Exception\ExceptionInterface $e) {
            self::assertStringContainsString('$role must be a string', $e->getMessage());
        }
    }

    public function testSetRoleThrowsExceptionWhenGivenAnArbitraryObject(): void
    {
        try {
            $this->helper->setRole(new stdClass());
            self::fail('An invalid argument was given, but a '
                        . 'Laminas\View\Exception\InvalidArgumentException was not thrown');
        } catch (View\Exception\ExceptionInterface $e) {
            self::assertStringContainsString('$role must be a string', $e->getMessage());
        }
    }

    public function testSetDefaultRoleAcceptsString(): void
    {
        $expected = 'member';
        AbstractHelper::setDefaultRole($expected);
        $actual = $this->helper->getRole();
        AbstractHelper::setDefaultRole(null);
        self::assertEquals($expected, $actual);
    }

    public function testSetDefaultRoleAcceptsRoleInterface(): void
    {
        $expected = new Role\GenericRole('member');
        AbstractHelper::setDefaultRole($expected);
        $actual = $this->helper->getRole();
        AbstractHelper::setDefaultRole(null);
        self::assertEquals($expected, $actual);
    }

    public function testSetDefaultRoleAcceptsNull(): void
    {
        AbstractHelper::setDefaultRole(null);
        self::assertNull($this->helper->getRole());
    }

    public function testSetDefaultRoleAcceptsNoParam(): void
    {
        AbstractHelper::setDefaultRole();
        self::assertNull($this->helper->getRole());
    }

    public function testSetDefaultRoleThrowsExceptionWhenGivenAnInt(): void
    {
        try {
            AbstractHelper::setDefaultRole(1337);
            self::fail('An invalid argument was given, but a '
                        . 'Laminas\View\Exception\InvalidArgumentException was not thrown');
        } catch (View\Exception\ExceptionInterface $e) {
            self::assertStringContainsString('$role must be', $e->getMessage());
        }
    }

    public function testSetDefaultRoleThrowsExceptionWhenGivenAnArbitraryObject(): void
    {
        try {
            AbstractHelper::setDefaultRole(new stdClass());
            self::fail('An invalid argument was given, but a '
                        . 'Laminas\View\Exception\InvalidArgumentException was not thrown');
        } catch (View\Exception\ExceptionInterface $e) {
            self::assertStringContainsString('$role must be', $e->getMessage());
        }
    }

    public function testMagicToStringShouldNotThrowException(): void
    {
        set_error_handler(function (int $code, string $message) {
            $this->errorHandlerMessage = $message;
        });

        $this->helper->menu()->setPartial([1337]);
        $this->helper->__toString();
        restore_error_handler();

        self::assertStringContainsString('array must contain', $this->errorHandlerMessage);
    }

    public function testPageIdShouldBeNormalized(): void
    {
        $nl = PHP_EOL;

        $container = new Container([
            [
                'label' => 'Page 1',
                'id'    => 'p1',
                'uri'   => 'p1',
            ],
            [
                'label' => 'Page 2',
                'id'    => 'p2',
                'uri'   => 'p2',
            ],
        ]);

        $expected = '<ul class="navigation">' . $nl
                  . '    <li>' . $nl
                  . '        <a id="menu-p1" href="p1">Page 1</a>' . $nl
                  . '    </li>' . $nl
                  . '    <li>' . $nl
                  . '        <a id="menu-p2" href="p2">Page 2</a>' . $nl
                  . '    </li>' . $nl
                  . '</ul>';

        $this->helper->setServiceLocator(new ServiceManager());
        $actual = $this->helper->render($container);

        self::assertEquals($expected, $actual);
    }

    public function testRenderInvisibleItem(): void
    {
        $container = new Container([
            [
                'label' => 'Page 1',
                'id'    => 'p1',
                'uri'   => 'p1',
            ],
            [
                'label'   => 'Page 2',
                'id'      => 'p2',
                'uri'     => 'p2',
                'visible' => false,
            ],
        ]);

        $this->helper->setServiceLocator(new ServiceManager());
        $render = $this->helper->menu()->render($container);

        self::assertStringNotContainsString('p2', $render);

        $this->helper->menu()->setRenderInvisible();

        $render = $this->helper->menu()->render($container);

        self::assertStringContainsString('p2', $render);
    }

    public function testMultipleNavigations(): void
    {
        $sm   = new ServiceManager();
        $nav1 = new Container();
        $nav2 = new Container();
        $sm->setService('nav1', $nav1);
        $sm->setService('nav2', $nav2);

        $helper = new Navigation();
        $helper->setServiceLocator($sm);

        $menu     = $helper('nav1')->menu();
        $actual   = spl_object_hash($nav1);
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);

        $menu     = $helper('nav2')->menu();
        $actual   = spl_object_hash($nav2);
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);
    }

    public function testMultipleNavigationsWithDifferentHelpersAndDifferentContainers(): void
    {
        $sm   = new ServiceManager();
        $nav1 = new Container();
        $nav2 = new Container();
        $sm->setService('nav1', $nav1);
        $sm->setService('nav2', $nav2);

        $helper = new Navigation();
        $helper->setServiceLocator($sm);

        $menu     = $helper('nav1')->menu();
        $actual   = spl_object_hash($nav1);
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);

        $breadcrumbs = $helper('nav2')->breadcrumbs();
        $actual      = spl_object_hash($nav2);
        $expected    = spl_object_hash($breadcrumbs->getContainer());
        self::assertEquals($expected, $actual);

        $links    = $helper()->links();
        $expected = spl_object_hash($links->getContainer());
        self::assertEquals($expected, $actual);
    }

    public function testMultipleNavigationsWithDifferentHelpersAndSameContainer(): void
    {
        $sm   = new ServiceManager();
        $nav1 = new Container();
        $sm->setService('nav1', $nav1);

        $helper = new Navigation();
        $helper->setServiceLocator($sm);

        // Tests
        $menu     = $helper('nav1')->menu();
        $actual   = spl_object_hash($nav1);
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);

        $breadcrumbs = $helper('nav1')->breadcrumbs();
        $expected    = spl_object_hash($breadcrumbs->getContainer());
        self::assertEquals($expected, $actual);

        $links    = $helper()->links();
        $expected = spl_object_hash($links->getContainer());
        self::assertEquals($expected, $actual);
    }

    public function testMultipleNavigationsWithSameHelperAndSameContainer(): void
    {
        $sm   = new ServiceManager();
        $nav1 = new Container();
        $sm->setService('nav1', $nav1);

        $helper = new Navigation();
        $helper->setServiceLocator($sm);

        // Test
        $menu     = $helper('nav1')->menu();
        $actual   = spl_object_hash($nav1);
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);

        $menu     = $helper('nav1')->menu();
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);

        $menu     = $helper()->menu();
        $expected = spl_object_hash($menu->getContainer());
        self::assertEquals($expected, $actual);
    }

    public function testSetPluginManagerAndView(): void
    {
        $pluginManager = new PluginManager(new ServiceManager());
        $view          = new PhpRenderer();

        $helper = new Navigation();
        $helper->setPluginManager($pluginManager);
        $helper->setView($view);

        self::assertEquals($view, $pluginManager->getRenderer());
    }

    public function testInjectsLazyInstantiatedPluginManagerWithCurrentServiceLocator(): void
    {
        $services = $this->createMock(ContainerInterface::class);
        $helper   = new Navigation();
        $helper->setServiceLocator($services);

        $plugins = $helper->getPluginManager();
        self::assertInstanceOf(PluginManager::class, $plugins);

        $pluginsReflection    = new ReflectionObject($plugins);
        $creationContext      = $pluginsReflection->getProperty('creationContext');
        $creationContextValue = $creationContext->getValue($plugins);
        self::assertSame($creationContextValue, $services);
    }

    /** @inheritDoc */
    protected function getExpectedFileContents(string $filename): string
    {
        return str_replace("\n", PHP_EOL, parent::getExpectedFileContents($filename));
    }
}
