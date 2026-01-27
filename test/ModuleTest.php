<?php

declare(strict_types=1);

namespace LaminasTest\Navigation;

use Laminas\Navigation\Module;
use PHPUnit\Framework\TestCase;

final class ModuleTest extends TestCase
{
    public function testGetConfigReturnsArray(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('service_manager', $config);
        $this->assertIsArray($config['service_manager']);
    }
}
