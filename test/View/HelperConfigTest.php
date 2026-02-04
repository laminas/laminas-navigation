<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View;

use ArrayObject;
use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\Navigation\View\HelperConfig;
use Laminas\Navigation\View\NavigationHelperFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\Navigation as NavigationHelper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests the class Laminas_Navigation_Page_Mvc
 */
#[Group('Laminas_Navigation')]
final class HelperConfigTest extends TestCase
{
    /** @return list<array{0: string}> */
    public static function navigationServiceNameProvider(): array
    {
        return [
            ['navigation'],
            ['Navigation'],
            [NavigationHelper::class],
            ['laminasviewhelpernavigation'],
        ];
    }

    #[DataProvider('navigationServiceNameProvider')]
    public function testConfigureServiceManagerWithConfig(
        string $navigationHelperServiceName
    ): void {
        $replacedMenuClass = NavigationHelper\Links::class;

        $serviceManager = new ServiceManager([
            'services'  => [
                'config' => [
                    'navigation_helpers' => [
                        'invokables' => [
                            'menu' => $replacedMenuClass,
                        ],
                    ],
                    'navigation'         => [
                        'file'    => __DIR__ . '/../_files/navigation.xml',
                        'default' => [
                            [
                                'label' => 'Page 1',
                                'uri'   => 'page1.html',
                            ],
                            [
                                'label' => 'MVC Page',
                                'route' => 'foo',
                                'pages' => [
                                    [
                                        'label' => 'Sub MVC Page',
                                        'route' => 'foo',
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Page 3',
                                'uri'   => 'page3.html',
                            ],
                        ],
                    ],
                ],
            ],
            'factories' => [
                'Navigation'        => DefaultNavigationFactory::class,
                'ViewHelperManager' => fn($services) => new HelperPluginManager($services),
            ],
        ]);

        $helpers = $serviceManager->get('ViewHelperManager');
        (new HelperConfig())->configureServiceManager($helpers);

        $menu = $helpers->get($navigationHelperServiceName)->findHelper('menu');
        $this->assertInstanceOf($replacedMenuClass, $menu);
    }

    public function testConfigureServiceManagerWithoutConfigService(): void
    {
        $serviceManager = new ServiceManager();
        $helpers        = new HelperPluginManager($serviceManager);

        (new HelperConfig())->configureServiceManager($helpers);

        $this->assertTrue($helpers->has('navigation'));
        $this->assertTrue($helpers->has(NavigationHelper::class));
    }

    public function testConstructorMergesInvokablesConfig(): void
    {
        $config = new HelperConfig([
            'invokables' => [
                'testHelper' => NavigationHelper\Menu::class,
            ],
        ]);

        $serviceManager = new ServiceManager();
        $helpers        = new HelperPluginManager($serviceManager);

        $config->configureServiceManager($helpers);

        $this->assertTrue($helpers->has('testHelper'));
    }

    public function testConfigureServiceManagerWithNoNavigationHelpersConfig(): void
    {
        $serviceManager = new ServiceManager([
            'services' => [
                'config' => [
                    'other_key' => [],
                ],
            ],
        ]);
        $helpers        = new HelperPluginManager($serviceManager);

        (new HelperConfig())->configureServiceManager($helpers);

        $this->assertTrue($helpers->has('navigation'));
    }

    public function testConfigureServiceManagerTwiceUsesCachedDelegator(): void
    {
        $serviceManager = new ServiceManager();
        $helpers        = new HelperPluginManager($serviceManager);

        $config = new HelperConfig();

        $config->configureServiceManager($helpers);
        $config->configureServiceManager($helpers);

        $this->assertTrue($helpers->has('navigation'));
    }

    public function testConstructorWithInvokableWhereNameEqualsClass(): void
    {
        $config = new HelperConfig([
            'invokables' => [
                NavigationHelper\Menu::class => NavigationHelper\Menu::class,
            ],
        ]);

        $serviceManager = new ServiceManager();
        $helpers        = new HelperPluginManager($serviceManager);

        $config->configureServiceManager($helpers);

        $this->assertTrue($helpers->has(NavigationHelper\Menu::class));
    }

    public function testConfigureServiceManagerWithTraversableConfig(): void
    {
        $traversableConfig = new ArrayObject([
            'navigation_helpers' => [
                'aliases' => [
                    'customNav' => NavigationHelper::class,
                ],
            ],
        ]);

        $serviceManager = new ServiceManager([
            'services' => [
                'config' => $traversableConfig,
            ],
        ]);
        $helpers        = new HelperPluginManager($serviceManager);

        (new HelperConfig())->configureServiceManager($helpers);

        $this->assertTrue($helpers->has('customNav'));
    }

    public function testInjectNavigationDelegatorFactorySkipsWhenAlreadyPresent(): void
    {
        $config = new HelperConfig();

        $prepareMethod = new ReflectionMethod($config, 'prepareNavigationDelegatorFactory');
        $factory       = $prepareMethod->invoke($config);

        $configProperty = new ReflectionProperty($config, 'config');
        $internalConfig = $configProperty->getValue($config);

        $internalConfig['delegators'][NavigationHelperFactory::class][] = $factory;
        $configProperty->setValue($config, $internalConfig);

        $serviceManager = new ServiceManager();
        $helpers        = new HelperPluginManager($serviceManager);

        $config->configureServiceManager($helpers);

        $this->assertTrue($helpers->has('navigation'));
    }
}
