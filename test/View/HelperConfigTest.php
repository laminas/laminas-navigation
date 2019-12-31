<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\View;

use Laminas\Navigation\View\HelperConfig;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the class Laminas_Navigation_Page_Mvc
 *
 * @group      Laminas_Navigation
 */
class HelperConfigTest extends TestCase
{
    protected $pluginManager = null;
    protected $serviceManager = null;
    protected $helperConfig = null;

    public function setUp()
    {
        $this->serviceManager = new ServiceManager();

        $this->pluginManager = new \Laminas\View\HelperPluginManager();
        $this->pluginManager->setServiceLocator($this->serviceManager);

        $this->helperConfig = new HelperConfig();
    }

    public function testConfigureServiceManagerWithConfig()
    {
        $replacedMenuClass = 'Laminas\View\Helper\Navigation\Links';
        $this->serviceManager->setService('config', array('navigation_helpers' => array(
            'invokables' => array(
                'menu' => $replacedMenuClass
             )
        )));
        $this->helperConfig->configureServiceManager($this->pluginManager);

        $menu = $this->pluginManager->get('navigation')->findHelper('menu');
        $this->assertInstanceOf($replacedMenuClass, $menu);
    }
}
