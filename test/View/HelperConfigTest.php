<?php

namespace LaminasTest\Navigation\View;

use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\Navigation\View\HelperConfig;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\Navigation as NavigationHelper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests the class Laminas_Navigation_Page_Mvc
 *
 * @group      Laminas_Navigation
 */
class HelperConfigTest extends TestCase
{
    public function navigationServiceNameProvider()
    {
        return [
            ['navigation'],
            ['Navigation'],
            [NavigationHelper::class],
            ['laminasviewhelpernavigation'],
        ];
    }

    /**
     * @dataProvider navigationServiceNameProvider
     */
    public function testConfigureServiceManagerWithConfig($navigationHelperServiceName)
    {
        $replacedMenuClass = NavigationHelper\Links::class;

        $serviceManager = new ServiceManager();
        (new Config([
            'services' => [
                'config' => [
                    'navigation_helpers' => [
                        'invokables' => [
                            'menu' => $replacedMenuClass,
                        ],
                    ],
                    'navigation' => [
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
                'Navigation' => DefaultNavigationFactory::class,
                'ViewHelperManager' => function ($services) {
                    return new HelperPluginManager($services);
                },
            ],
        ]))->configureServiceManager($serviceManager);

        $helpers = $serviceManager->get('ViewHelperManager');
        (new HelperConfig())->configureServiceManager($helpers);

        $menu = $helpers->get($navigationHelperServiceName)->findHelper('menu');
        $this->assertInstanceOf($replacedMenuClass, $menu);
    }
}
