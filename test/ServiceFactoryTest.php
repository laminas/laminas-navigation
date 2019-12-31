<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation;

use Laminas\Config;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\Navigation;
use Laminas\Navigation\Page\Mvc as MvcPage;
use Laminas\Navigation\Service\ConstructedNavigationFactory;
use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\ServiceManager\ServiceManager;

/**
 * Tests the class Laminas\Navigation\MvcNavigationFactory
 *
 * @category   Laminas
 * @package    Laminas_Navigation
 * @subpackage UnitTests
 * @group      Laminas_Navigation
 */
class ServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $config = array(
            'modules'                 => array(),
            'module_listener_options' => array(
                'config_cache_enabled' => false,
                'cache_dir'            => 'data/cache',
                'module_paths'         => array(),
                'extra_config'         => array(
                    'service_manager' => array(
                        'factories' => array(
                            'Config' => function () {
                                return array(
                                    'navigation' => array(
                                        'file'    => __DIR__ . '/_files/navigation.xml',
                                        'default' => array(
                                            array(
                                                'label' => 'Page 1',
                                                'uri'   => 'page1.html'
                                            ),
                                            array(
                                                'label' => 'MVC Page',
                                                'route' => 'foo',
                                                'pages' => array(
                                                    array(
                                                        'label' => 'Sub MVC Page',
                                                        'route' => 'foo'
                                                    )
                                                )
                                            ),
                                            array(
                                                'label' => 'Page 3',
                                                'uri'   => 'page3.html'
                                            )
                                        )
                                    )
                                );
                            }
                        )
                    ),
                )
            ),
        );

        $sm = $this->serviceManager = new ServiceManager(new ServiceManagerConfig);
        $sm->setService('ApplicationConfig', $config);
        $sm->get('ModuleManager')->loadModules();
        $sm->get('Application')->bootstrap();

        $app = $this->serviceManager->get('Application');
        $app->getMvcEvent()->setRouteMatch(new RouteMatch(array(
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        )));
    }

    /**
     * Tear down the environment after running a test
     */
    protected function tearDown()
    {

    }

    /**
     * @covers \Laminas\Navigation\MvcNavigationFactory
     */
    public function testDefaultFactoryAcceptsFileString()
    {
        $this->serviceManager->setFactory('Navigation', 'LaminasTest\Navigation\TestAsset\FileNavigationFactory');
        $container = $this->serviceManager->get('Navigation');
    }

    /**
     * @covers \Laminas\Navigation\MvcNavigationFactory
     */
    public function testMvcPagesGetInjectedWithComponents()
    {
        $this->serviceManager->setFactory('Navigation', 'Laminas\Navigation\Service\DefaultNavigationFactory');
        $container = $this->serviceManager->get('Navigation');

        $recursive = function ($that, $pages) use (&$recursive) {
            foreach ($pages as $page) {
                if ($page instanceof MvcPage) {
                    $that->assertInstanceOf('Laminas\Mvc\Router\RouteStackInterface', $page->getRouter());
                    $that->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    /**
     * @covers \Laminas\Navigation\MvcNavigationFactory
     */
    public function testDefaultFactory()
    {
        $this->serviceManager->setFactory('Navigation', 'Laminas\Navigation\Service\DefaultNavigationFactory');

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\MvcNavigationFactory
     */
    public function testConstructedFromArray()
    {
        $argument = array(
            array(
                'label' => 'Page 1',
                'uri'   => 'page1.html'
            ),
            array(
                'label' => 'Page 2',
                'uri'   => 'page2.html'
            ),
            array(
                'label' => 'Page 3',
                'uri'   => 'page3.html'
            )
        );

        $factory = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\MvcNavigationFactory
     */
    public function testConstructedFromFileString()
    {
        $argument = __DIR__ . '/_files/navigation.xml';
        $factory  = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\MvcNavigationFactory
     */
    public function testConstructedFromConfig()
    {
        $argument = new Config\Config(array(
            array(
                'label' => 'Page 1',
                'uri'   => 'page1.html'
            ),
            array(
                'label' => 'Page 2',
                'uri'   => 'page2.html'
            ),
            array(
                'label' => 'Page 3',
                'uri'   => 'page3.html'
            )
        ));

        $factory = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }
}
