<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

use Laminas\Config\Config;
use Psr\Container\ContainerInterface;

/**
 * Constructed factory to set pages during construction.
 *
 * @final
 */
class ConstructedNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @param string|Config|array $config
     */
    public function __construct(protected $config)
    {
    }

    /**
     * @return array|null|Config
     */
    public function getPages(ContainerInterface $container)
    {
        if (null === $this->pages) {
            $this->pages = $this->preparePages($container, $this->getPagesFromConfig($this->config));
        }
        return $this->pages;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'constructed';
    }
}
