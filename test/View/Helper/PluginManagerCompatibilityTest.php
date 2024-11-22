<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View\Helper;

use Laminas\Navigation\View\Helper\AbstractHelper;
use Laminas\Navigation\View\Helper\Breadcrumbs;
use Laminas\Navigation\View\Helper\PluginManager;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use Laminas\View\Exception\InvalidHelperException;
use PHPUnit\Framework\TestCase;

class PluginManagerCompatibilityTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected static function getPluginManager(): PluginManager
    {
        return new PluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException(): string
    {
        return InvalidHelperException::class;
    }

    protected function getInstanceOf(): string
    {
        return AbstractHelper::class;
    }

    public function testInjectsParentContainerIntoHelpers(): void
    {
        $config = new Config([
            'navigation' => [
                'default' => [],
            ],
        ]);

        $services = new ServiceManager();
        $config->configureServiceManager($services);
        $helpers = new PluginManager($services);

        $helper = $helpers->get('breadcrumbs');
        $this->assertInstanceOf(Breadcrumbs::class, $helper);
        $this->assertSame($services, $helper->getServiceLocator());
    }
}
