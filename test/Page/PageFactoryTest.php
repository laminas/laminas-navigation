<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\Page;

use Laminas\Navigation;
use Laminas\Navigation\Page\AbstractPage;

/**
 * Tests Laminas_Navigation_Page::factory()
 *
 * @group      Laminas_Navigation
 */
class PageFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDetectFactoryPage()
    {
        AbstractPage::addFactory(function ($page) {
            if (isset($page['factory_uri'])) {
                return new \Laminas\Navigation\Page\Uri($page);
            } elseif (isset($page['factory_mvc'])) {
                return new \Laminas\Navigation\Page\Mvc($page);
            }
        });

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Uri', AbstractPage::factory([
            'label' => 'URI Page',
            'factory_uri' => '#'
        ]));

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Mvc', AbstractPage::factory([
            'label' => 'URI Page',
            'factory_mvc' => '#'
        ]));
    }

    public function testDetectMvcPage()
    {
        $pages = [
            AbstractPage::factory([
                'label' => 'MVC Page',
                'action' => 'index'
            ]),
            AbstractPage::factory([
                'label' => 'MVC Page',
                'controller' => 'index'
            ]),
            AbstractPage::factory([
                'label' => 'MVC Page',
                'route' => 'home'
            ])
        ];

        $this->assertContainsOnly('Laminas\Navigation\Page\Mvc', $pages);
    }

    public function testDetectUriPage()
    {
        $page = AbstractPage::factory([
            'label' => 'URI Page',
            'uri' => '#'
        ]);

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Uri', $page);
    }

    public function testMvcShouldHaveDetectionPrecedence()
    {
        $page = AbstractPage::factory([
            'label' => 'MVC Page',
            'action' => 'index',
            'controller' => 'index',
            'uri' => '#'
        ]);

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Mvc', $page);
    }

    public function testSupportsMvcShorthand()
    {
        $mvcPage = AbstractPage::factory([
            'type' => 'mvc',
            'label' => 'MVC Page',
            'action' => 'index',
            'controller' => 'index'
        ]);

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Mvc', $mvcPage);
    }

    public function testSupportsUriShorthand()
    {
        $uriPage = AbstractPage::factory([
            'type' => 'uri',
            'label' => 'URI Page',
            'uri' => 'http://www.example.com/'
        ]);

        $this->assertInstanceOf('Laminas\\Navigation\\Page\\Uri', $uriPage);
    }

    public function testSupportsCustomPageTypes()
    {
        $page = AbstractPage::factory([
            'type' => 'LaminasTest\Navigation\TestAsset\Page',
            'label' => 'My Custom Page'
        ]);

        return $this->assertInstanceOf('LaminasTest\\Navigation\\TestAsset\\Page', $page);
    }

    public function testShouldFailForInvalidType()
    {
        try {
            $page = AbstractPage::factory([
                'type' => 'LaminasTest\Navigation\TestAsset\InvalidPage',
                'label' => 'My Invalid Page'
            ]);
        } catch (Navigation\Exception\InvalidArgumentException $e) {
            return;
        }

        $this->fail('An exception has not been thrown for invalid page type');
    }

    public function testShouldFailForNonExistantType()
    {
        $pageConfig = [
            'type' => 'My_NonExistent_Page',
            'label' => 'My non-existent Page'
        ];

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
            $page = AbstractPage::factory([
                'label' => 'My Invalid Page'
            ]);
        } catch (Navigation\Exception\InvalidArgumentException $e) {
            return;
        }

        $this->fail('An exception has not been thrown for invalid page type');
    }
}
