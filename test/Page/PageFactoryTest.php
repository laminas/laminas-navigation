<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\Page;

use Laminas\Navigation;
use Laminas\Navigation\Exception\InvalidArgumentException;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Navigation\Page\Mvc;
use Laminas\Navigation\Page\Uri;
use LaminasTest\Navigation\TestAsset\InvalidPage;
use LaminasTest\Navigation\TestAsset\Page;
use PHPUnit\Framework\TestCase;

/**
 * Tests Laminas_Navigation_Page::factory()
 *
 * @group      Laminas_Navigation
 */
class PageFactoryTest extends TestCase
{
    public function testDetectFactoryPage()
    {
        AbstractPage::addFactory(function ($page) {
            if (isset($page['factory_uri'])) {
                return new Uri($page);
            } elseif (isset($page['factory_mvc'])) {
                return new Mvc($page);
            }
        });

        $this->assertInstanceOf(Uri::class, AbstractPage::factory([
            'label'       => 'URI Page',
            'factory_uri' => '#',
        ]));

        $this->assertInstanceOf(Mvc::class, AbstractPage::factory([
            'label'       => 'URI Page',
            'factory_mvc' => '#',
        ]));
    }

    public function testDetectMvcPage()
    {
        $pages = [
            AbstractPage::factory([
                'label'  => 'MVC Page',
                'action' => 'index',
            ]),
            AbstractPage::factory([
                'label'      => 'MVC Page',
                'controller' => 'index',
            ]),
            AbstractPage::factory([
                'label' => 'MVC Page',
                'route' => 'home',
            ]),
        ];

        $this->assertContainsOnly(Mvc::class, $pages);
    }

    public function testDetectUriPage()
    {
        $page = AbstractPage::factory([
            'label' => 'URI Page',
            'uri'   => '#',
        ]);

        $this->assertInstanceOf(Uri::class, $page);
    }

    public function testMvcShouldHaveDetectionPrecedence()
    {
        $page = AbstractPage::factory([
            'label'      => 'MVC Page',
            'action'     => 'index',
            'controller' => 'index',
            'uri'        => '#',
        ]);

        $this->assertInstanceOf(Mvc::class, $page);
    }

    public function testSupportsMvcShorthand()
    {
        $mvcPage = AbstractPage::factory([
            'type'       => 'mvc',
            'label'      => 'MVC Page',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        $this->assertInstanceOf(Mvc::class, $mvcPage);
    }

    public function testSupportsUriShorthand()
    {
        $uriPage = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'URI Page',
            'uri'   => 'http://www.example.com/',
        ]);

        $this->assertInstanceOf(Uri::class, $uriPage);
    }

    public function testSupportsCustomPageTypes()
    {
        $page = AbstractPage::factory([
            'type'  => Page::class,
            'label' => 'My Custom Page',
        ]);

        $this->assertInstanceOf(Page::class, $page);
    }

    public function testShouldFailForInvalidType()
    {
        $this->expectException(
            Navigation\Exception\InvalidArgumentException::class
        );

        AbstractPage::factory([
            'type'  => InvalidPage::class,
            'label' => 'My Invalid Page',
        ]);
    }

    public function testShouldFailForNonExistantType()
    {
        $this->expectException(
            Navigation\Exception\InvalidArgumentException::class
        );

        $pageConfig = [
            'type'  => 'My_NonExistent_Page',
            'label' => 'My non-existent Page',
        ];

        AbstractPage::factory($pageConfig);
    }

    public function testShouldFailIfUnableToDetermineType()
    {
        $this->expectException(
            Navigation\Exception\InvalidArgumentException::class
        );

        AbstractPage::factory([
            'label' => 'My Invalid Page',
        ]);
    }

    public function testShouldThrowExceptionOnInvalidMethodArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        AbstractPage::factory('');
    }
}
