<?php

declare(strict_types=1);

namespace Laminas\Navigation;

final readonly class Module
{
    /**
     * Return laminas-form configuration for laminas-mvc application.
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }
}
