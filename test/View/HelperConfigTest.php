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
}
