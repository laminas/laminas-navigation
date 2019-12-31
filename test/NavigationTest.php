<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation;

use Laminas\Navigation\Page;

/**
 * Laminas_Navigation
 */

/**
 * @group      Laminas_Navigation
 */
class NavigationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var     Laminas_Navigation
     */
    private $_navigation;

    protected function setUp()
    {
        parent::setUp();
        $this->_navigation = new \Laminas\Navigation\Navigation();
    }

    protected function tearDown()
    {
        $this->_navigation = null;
        parent::tearDown();
    }

    /**
     * Testing that navigation order is done correctly
     *
     * @group   Laminas-8337
     * @group   Laminas-8313
     */
    public function testNavigationArraySortsCorrectly()
    {
        $page1 = new Page\Uri(array('uri' => 'page1'));
        $page2 = new Page\Uri(array('uri' => 'page2'));
        $page3 = new Page\Uri(array('uri' => 'page3'));

        $this->_navigation->setPages(array($page1, $page2, $page3));

        $page1->setOrder(1);
        $page3->setOrder(0);
        $page2->setOrder(2);

        $pages = $this->_navigation->toArray();

        $this->assertSame(3, count($pages));
        $this->assertEquals('page3', $pages[0]['uri'], var_export($pages, 1));
        $this->assertEquals('page1', $pages[1]['uri']);
        $this->assertEquals('page2', $pages[2]['uri']);
    }

}
