<?php

declare(strict_types=1);

namespace LaminasTest\Navigation;

use Laminas\Navigation\ConfigProvider;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service;
use Laminas\Navigation\View;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private $config = [
        'abstract_factories' => [
            Service\NavigationAbstractServiceFactory::class,
        ],
        'aliases'            => [
            'navigation'                 => Navigation::class,
            'Zend\Navigation\Navigation' => Navigation::class,
        ],
        'delegators'         => [
            'ViewHelperManager' => [
                View\ViewHelperManagerDelegatorFactory::class,
            ],
        ],
        'factories'          => [
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
