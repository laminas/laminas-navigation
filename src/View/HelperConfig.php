<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Navigation\View;

use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;

/**
 * Service manager configuration for navigation view helpers
 */
class HelperConfig implements ConfigInterface
{
    /**
     * Configure the provided service manager instance with the configuration
     * in this class.
     *
     * Simply adds a factory for the navigation helper, and has it inject the helper
     * with the service locator instance.
     *
     * @param  ServiceManager $serviceManager
     * @return void
     */
    public function configureServiceManager(ServiceManager $serviceManager)
    {
        $serviceManager->setFactory('navigation', function (HelperPluginManager $pm) {
            $helper = new \Laminas\View\Helper\Navigation;
            $helper->setServiceLocator($pm->getServiceLocator());
            return $helper;
        });
    }
}
