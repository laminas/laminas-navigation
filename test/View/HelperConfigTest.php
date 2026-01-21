<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View;

use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\Navigation\View\HelperConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\Navigation as NavigationHelper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Tests the class Laminas_Navigation_Page_Mvc
 */
#[Group('Laminas_Navigation')]
final class HelperConfigTest extends TestCase
{
    /**
     * @psalm-suppress DeprecatedClass
     * @return list<array{0: string}>
     */
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
        /** @psalm-suppress DeprecatedClass */
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
                'ViewHelperManager' => fn(ContainerInterface $services) => new HelperPluginManager($services),
            ],
        ]);

        $helpers = $serviceManager->get('ViewHelperManager');
        self::assertInstanceOf(HelperPluginManager::class, $helpers);
        (new HelperConfig())->configureServiceManager($helpers);

        $navigationHelper = $helpers->get($navigationHelperServiceName);
        /** @psalm-suppress DeprecatedClass */
        self::assertInstanceOf(NavigationHelper::class, $navigationHelper);
        /** @psalm-suppress DeprecatedInterface */
        $menu = $navigationHelper->findHelper('menu');
        $this->assertInstanceOf($replacedMenuClass, $menu);
    }
}
