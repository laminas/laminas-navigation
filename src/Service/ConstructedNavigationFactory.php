<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Navigation\Service;

use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Constructed factory to set pages during construction.
 */
class ConstructedNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @param string|\Laminas\Config\Config|array $config
     */
    public function __construct($config)
    {
        $this->pages = $this->getPagesFromConfig($config);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return array|null|\Laminas\Config\Config
     */
    public function getPages(ServiceLocatorInterface $serviceLocator)
    {
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
