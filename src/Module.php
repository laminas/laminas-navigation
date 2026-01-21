<?php

declare(strict_types=1);

namespace Laminas\Navigation;

/**
 * @final
 * @psalm-suppress UnusedClass
 */
class Module
{
    /**
     * Return laminas-form configuration for laminas-mvc application.
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }
}
