<?php

declare(strict_types=1);

namespace LaminasTest\Navigation;

use Laminas\Navigation\Navigation;
use Laminas\Navigation\Page;
use PHPUnit\Framework\TestCase;

use function count;
use function var_export;

class NavigationTest extends TestCase
{
    /** @var Navigation */
    private $navigation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    protected function tearDown(): void
    {
        $this->navigation = null;
        parent::tearDown();
    }

    /**
     * Testing that navigation order is done correctly
     *
     * @group   Laminas-8337
     * @group   Laminas-8313
     */
    public function testNavigationArraySortsCorrectly(): void
    {
        $page1 = new Page\Uri(['uri' => 'page1']);
        $page2 = new Page\Uri(['uri' => 'page2']);
        $page3 = new Page\Uri(['uri' => 'page3']);

        $this->navigation->setPages([$page1, $page2, $page3]);

        $page1->setOrder(1);
        $page3->setOrder(0);
        $page2->setOrder(2);

        $pages = $this->navigation->toArray();

        $this->assertSame(3, count($pages));
        $this->assertEquals('page3', $pages[0]['uri'], var_export($pages, true));
        $this->assertEquals('page1', $pages[1]['uri']);
        $this->assertEquals('page2', $pages[2]['uri']);
    }
}
