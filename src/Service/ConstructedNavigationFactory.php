<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

use Laminas\Config\Config;
use Psr\Container\ContainerInterface;

/**
 * Constructed factory to set pages during construction.
 */
class ConstructedNavigationFactory extends AbstractNavigationFactory
{
    /** @var string|Config|array */
    protected $config;

    /**
     * @param string|Config|array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
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
