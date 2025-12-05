<?php

declare(strict_types=1);

namespace Laminas\Navigation;

final readonly class ConfigProvider
{
    /**
     * Return general-purpose laminas-navigation configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return application-level dependency configuration.
     */
    public function getDependencyConfig(): array
    {
        return [
            'abstract_factories' => [
                Service\NavigationAbstractServiceFactory::class,
            ],
            'aliases'            => [
                'navigation' => Navigation::class,

                // Legacy Zend Framework aliases
                \Zend\Navigation\Navigation::class => Navigation::class,
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
    }
}
