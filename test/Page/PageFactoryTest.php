<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation;

use Laminas\Navigation;
use Laminas\Navigation\Page\AbstractPage;

/**
 * Tests Laminas_Navigation_Page::factory()
 *
/**
 * @group      Laminas_Navigation
 */
class PageFactoryTest extends \PHPUnit_Framework_TestCase
{


    public function testDetectMvcPage()
    {
        $pages = array(
            AbstractPage::factory(array(
                'label' => 'MVC Page',
                'action' => 'index'
            )),
            AbstractPage::factory(array(
                'label' => 'MVC Page',
                'controller' => 'index'
            )),
            AbstractPage::factory(array(
                'label' => 'MVC Page',
                'route' => 'home'
            ))
        );

        $this->assertContainsOnly('Laminas\Navigation\Page\Mvc', $pages);
    }

    public function testDetectUriPage()
    {
        $page = AbstractPage::factory(array(
            'label' => 'URI Page',
            'uri' => '#'
        ));

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Uri', $page);
    }

    public function testMvcShouldHaveDetectionPrecedence()
    {
        $page = AbstractPage::factory(array(
            'label' => 'MVC Page',
            'action' => 'index',
            'controller' => 'index',
            'uri' => '#'
        ));

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Mvc', $page);
    }

    public function testSupportsMvcShorthand()
    {
        $mvcPage = AbstractPage::factory(array(
            'type' => 'mvc',
            'label' => 'MVC Page',
            'action' => 'index',
            'controller' => 'index'
        ));

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Mvc', $mvcPage);
    }

    public function testSupportsUriShorthand()
    {
        $uriPage = AbstractPage::factory(array(
            'type' => 'uri',
            'label' => 'URI Page',
            'uri' => 'http://www.example.com/'
        ));

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Uri', $uriPage);
    }

    public function testSupportsCustomPageTypes()
    {
        $page = AbstractPage::factory(array(
            'type' => 'LaminasTest\Navigation\TestAsset\Page',
            'label' => 'My Custom Page'
        ));

        return $this->assertInstanceOf('LaminasTest\\Navigation\\TestAsset\\Page', $page);
    }

    public function testShouldFailForInvalidType()
    {
        try {
            $page = AbstractPage::factory(array(
                'type' => 'LaminasTest\Navigation\TestAsset\InvalidPage',
                'label' => 'My Invalid Page'
            ));
        } catch (Navigation\Exception\InvalidArgumentException $e) {
            return;
        }

        $this->fail('An exception has not been thrown for invalid page type');
    }

    public function testShouldFailForNonExistantType()
    {
        $pageConfig = array(
            'type' => 'My_NonExistent_Page',
            'label' => 'My non-existent Page'
        );

        try {
            $page = AbstractPage::factory($pageConfig);
        } catch (Navigation\Exception\InvalidArgumentException $e) {
            return;
        }

        $msg = 'An exception has not been thrown for non-existent class';
        $this->fail($msg);
    }

    public function testShouldFailIfUnableToDetermineType()
    {
        try {
            $page = AbstractPage::factory(array(
                'label' => 'My Invalid Page'
            ));
        } catch (Navigation\Exception\InvalidArgumentException $e) {
            return;
        }

        $this->fail('An exception has not been thrown for invalid page type');
    }
}
