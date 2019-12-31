<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation;

use Laminas\Navigation\ConfigProvider;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service;
use Laminas\Navigation\View;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    private $config = [
        'abstract_factories' => [
            Service\NavigationAbstractServiceFactory::class,
        ],
        'aliases' => [
            'navigation' => Navigation::class,
        ],
        'delegators' => [
            'ViewHelperManager' => [
                View\ViewHelperManagerDelegatorFactory::class,
            ],
        ],
        'factories' => [
            Navigation::class => Service\DefaultNavigationFactory::class,
        ],
    ];

    public function testProvidesExpectedConfiguration()
    {
        $provider = new ConfigProvider();
        $this->assertEquals($this->config, $provider->getDependencyConfig());
        return $provider;
    }

    /**
     * @depends testProvidesExpectedConfiguration
     */
    public function testInvocationProvidesDependencyConfiguration(ConfigProvider $provider)
    {
        $this->assertEquals(['dependencies' => $provider->getDependencyConfig()], $provider());
    }
}
