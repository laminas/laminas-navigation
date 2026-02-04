<?php

declare(strict_types=1);

namespace LaminasTest\Navigation;

use Laminas\Config;
use Laminas\Navigation;
use Laminas\Navigation\Page;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Navigation\Page\Uri;
use LaminasTest\Navigation\TestAsset\AbstractContainer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use stdClass;

use function count;
use function gettype;

/**
 * Tests the class Laminas_Navigation_Container
 */
#[Group('Laminas_Navigation')]
final class ContainerTest extends TestCase
{
    public function testConstructWithArray(): void
    {
        $argument = [
            [
                'label' => 'Page 1',
                'uri'   => 'page1.html',
            ],
            [
                'label' => 'Page 2',
                'uri'   => 'page2.html',
            ],
            [
                'label' => 'Page 3',
                'uri'   => 'page3.html',
            ],
        ];

        $container = new Navigation\Navigation($argument);
        $this->assertEquals(3, $container->count());
    }

    public function testConstructWithConfig(): void
    {
        $argument = new Config\Config([
            [
                'label' => 'Page 1',
                'uri'   => 'page1.html',
            ],
            [
                'label' => 'Page 2',
                'uri'   => 'page2.html',
            ],
            [
                'label' => 'Page 3',
                'uri'   => 'page3.html',
            ],
        ]);

        $container = new Navigation\Navigation($argument);
        $this->assertEquals(3, $container->count());
    }

    public function testConstructorShouldThrowExceptionOnStringArgument(): void
    {
        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $pages');
        new Navigation\Navigation('ok');
    }

    public function testConstructorShouldThrowExceptionOnIntegerArgument(): void
    {
        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $pages');
        new Navigation\Navigation(1337);
    }

    public function testConstructorShouldThrowExceptionOnObjectArgument(): void
    {
        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $pages');
        new Navigation\Navigation(new stdClass());
    }

    public function testAddPagesWithNullValueSkipsPage(): void
    {
        $nav   = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            null,
        ]);
        $count = count($nav->getPages());
        $this->assertEquals(1, $count);
    }

    public function testIterationShouldBeOrderAware(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
                'order' => -1,
            ],
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 4',
                'uri'   => '#',
                'order' => 100,
            ],
            [
                'label' => 'Page 5',
                'uri'   => '#',
            ],
        ]);

        $expected = ['Page 2', 'Page 1', 'Page 3', 'Page 5', 'Page 4'];
        $actual   = [];
        foreach ($nav as $page) {
            $actual[] = $page->getLabel();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testRecursiveIteration(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
                'pages' => [
                    [
                        'label' => 'Page 1.1',
                        'uri'   => '#',
                        'pages' => [
                            [
                                'label' => 'Page 1.1.1',
                                'uri'   => '#',
                            ],
                            [
                                'label' => 'Page 1.1.2',
                                'uri'   => '#',
                            ],
                        ],
                    ],
                    [
                        'label' => 'Page 1.2',
                        'uri'   => '#',
                    ],
                ],
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
                'pages' => [
                    [
                        'label' => 'Page 2.1',
                        'uri'   => '#',
                    ],
                ],
            ],
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
        ]);

        $actual   = [];
        $expected = [
            'Page 1',
            'Page 1.1',
            'Page 1.1.1',
            'Page 1.1.2',
            'Page 1.2',
            'Page 2',
            'Page 2.1',
            'Page 3',
        ];

        $iterator = new RecursiveIteratorIterator(
            $nav,
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $page) {
            $actual[] = $page->getLabel();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @link https://github.com/zendframework/zf2/issues/3211
     */
    public function testHasChildrenCompatibility(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
                'pages' => [
                    [
                        'label' => 'Page 1.1',
                        'uri'   => '#',
                        'pages' => [
                            [
                                'label' => 'Page 1.1.1',
                                'uri'   => '#',
                            ],
                            [
                                'label' => 'Page 1.1.2',
                                'uri'   => '#',
                            ],
                        ],
                    ],
                    [
                        'label' => 'Page 1.2',
                        'uri'   => '#',
                    ],
                ],
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
                'pages' => [
                    [
                        'label' => 'Page 2.1',
                        'uri'   => '#',
                    ],
                ],
            ],
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
        ]);

        $page1 = $nav->findOneBy('label', 'Page 1');
        $this->assertTrue($page1->hasChildren(), "page1's first child has children 1.1.1 1.1.2");

        $page2 = $nav->findOneBy('label', 'Page 2');
        $this->assertFalse($page2->hasChildren(), "page2's first child doesn't have children");
    }

    public function testDetailedRecursiveIteration(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
                'pages' => [
                    [
                        'label' => 'Page 1.1',
                        'uri'   => '#',
                        'pages' => [
                            [
                                'label' => 'Page 1.1.1',
                                'uri'   => '#',
                            ],
                            [
                                'label' => 'Page 1.1.2',
                                'uri'   => '#',
                            ],
                        ],
                    ],
                    [
                        'label' => 'Page 1.2',
                        'uri'   => '#',
                    ],
                ],
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
                'pages' => [
                    [
                        'label' => 'Page 2.1',
                        'uri'   => '#',
                    ],
                ],
            ],
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
        ]);

        $expected = [
            'beginIteration',
            'Page 1',
            'beginChildren',
            'Page 1.1',
            'beginChildren',
            'Page 1.1.1',
            'Page 1.1.2',
            'endChildren',
            'Page 1.2',
            'endChildren',
            'Page 2',
            'beginChildren',
            'Page 2.1',
            'endChildren',
            'Page 3',
            'endIteration',
        ];

        $iterator         = new TestAsset\RecursiveIteratorIterator($nav, RecursiveIteratorIterator::SELF_FIRST);
        $iterator->logger = [];
        $iterator->rewind();
        //#4517 logging with walking through RecursiveIterator
        while ($iterator->valid()) {
            $iterator->current();
            $iterator->next();
        }
        $this->assertEquals($expected, $iterator->logger);
    }

    public function testSettingPageOrderShouldUpdateContainerOrder(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
            ],
        ]);

        $page3 = Page\AbstractPage::factory([
            'label' => 'Page 3',
            'uri'   => '#',
        ]);

        $nav->addPage($page3);

        $expected = [
            'before' => ['Page 1', 'Page 2', 'Page 3'],
            'after'  => ['Page 3', 'Page 1', 'Page 2'],
        ];

        $actual = [
            'before' => [],
            'after'  => [],
        ];

        foreach ($nav as $page) {
            $actual['before'][] = $page->getLabel();
        }

        $page3->setOrder(-1);

        foreach ($nav as $page) {
            $actual['after'][] = $page->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testAddPageShouldWorkWithArray(): void
    {
        $pageOptions = [
            'label' => 'From array',
            'uri'   => '#array',
        ];

        $nav = new Navigation\Navigation();
        $nav->addPage($pageOptions);

        $this->assertEquals(1, count($nav));
    }

    public function testAddPageShouldWorkWithConfig(): void
    {
        $pageOptions = [
            'label' => 'From config',
            'uri'   => '#config',
        ];

        $pageOptions = new Config\Config($pageOptions);

        $nav = new Navigation\Navigation();
        $nav->addPage($pageOptions);

        $this->assertEquals(1, count($nav));
    }

    public function testAddPageShouldWorkWithPageInstance(): void
    {
        $pageOptions = [
            'label' => 'From array 1',
            'uri'   => '#array',
        ];

        $nav = new Navigation\Navigation([$pageOptions]);

        $page = Page\AbstractPage::factory($pageOptions);
        $nav->addPage($page);

        $this->assertEquals(2, count($nav));
    }

    public function testAddPagesShouldWorkWithArray(): void
    {
        $nav = new Navigation\Navigation();
        $nav->addPages([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label'      => 'Page 2',
                'action'     => 'index',
                'controller' => 'index',
            ],
        ]);

        $this->assertEquals(
            2,
            count($nav),
            'Expected 2 pages, found ' . count($nav)
        );
    }

    public function testAddPagesShouldWorkWithConfig(): void
    {
        $nav = new Navigation\Navigation();
        $nav->addPages(new Config\Config([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label'      => 'Page 2',
                'action'     => 'index',
                'controller' => 'index',
            ],
        ]));

        $this->assertEquals(
            2,
            count($nav),
            'Expected 2 pages, found ' . count($nav)
        );
    }

    public function testAddPagesShouldWorkWithMixedArray(): void
    {
        $nav = new Navigation\Navigation();
        $nav->addPages(new Config\Config([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            new Config\Config([
                'label'      => 'Page 2',
                'action'     => 'index',
                'controller' => 'index',
            ]),
            Page\AbstractPage::factory([
                'label' => 'Page 3',
                'uri'   => '#',
            ]),
        ]));

        $this->assertEquals(
            3,
            count($nav),
            'Expected 3 pages, found ' . count($nav)
        );
    }

    public function testAddPagesShouldWorkWithNavigationContainer(): void
    {
        $nav = new Navigation\Navigation();
        $nav->addPages($this->_getFindByNavigation());

        $this->assertEquals(
            3,
            count($nav),
            'Expected 3 pages, found ' . count($nav)
        );

        $this->assertEquals(
            $this->_getFindByNavigation()->toArray(),
            $nav->toArray()
        );
    }

    public function testAddPagesShouldThrowExceptionWhenGivenString(): void
    {
        $nav = new Navigation\Navigation();

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $pages must be');
        /** @psalm-suppress InvalidArgument */
        $nav->addPages('this is a string');
    }

    public function testAddPagesShouldThrowExceptionWhenGivenAnArbitraryObject(): void
    {
        $nav = new Navigation\Navigation();

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $pages must be');
        /** @psalm-suppress InvalidArgument */
        $nav->addPages(new stdClass());
    }

    public function testRemovingAllPages(): void
    {
        $nav = new Navigation\Navigation();
        $nav->addPages([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
            ],
        ]);

        $nav->removePages();

        $this->assertEquals(
            0,
            count($nav),
            'Expected 0 pages, found ' . count($nav)
        );
    }

    public function testSettingPages(): void
    {
        $nav = new Navigation\Navigation();
        $nav->addPages([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
            ],
        ]);

        $nav->setPages([
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
        ]);

        $this->assertEquals(
            1,
            count($nav),
            'Expected 1 page, found ' . count($nav)
        );
    }

    public function testGetPagesShouldReturnAnArrayOfPages(): void
    {
        $nav = new Navigation\Navigation([
            [
                'uri' => 'Page 1',
            ],
            [
                'uri' => 'Page 2',
            ],
        ]);

        $pages = $nav->getPages();

        $expected = [
            'type'  => 'array',
            'count' => 2,
        ];

        $actual = [
            'type'  => gettype($pages),
            'count' => count($pages),
        ];

        $this->assertEquals($expected, $actual);
        $this->assertContainsOnlyInstancesOf(Uri::class, $pages);
    }

    public function testGetPagesShouldReturnUnorderedPages(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 2',
                'uri'   => '#',
                'order' => -1,
            ],
            [
                'label' => 'Page 4',
                'uri'   => '#',
                'order' => 100,
            ],
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 5',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
        ]);

        $expected = ['Page 2', 'Page 4', 'Page 1', 'Page 5', 'Page 3'];
        $actual   = [];
        foreach ($nav->getPages() as $page) {
            $actual[] = $page->getLabel();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testRemovingPageByOrder(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
                'order' => 32,
            ],
            [
                'label' => 'Page 3',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 4',
                'uri'   => '#',
            ],
        ]);

        $expected = [
            'remove0'      => true,
            'remove32'     => true,
            'remove0again' => true,
            'remove1000'   => false,
            'count'        => 1,
            'current'      => 'Page 4',
        ];

        $actual = [
            'remove0'      => $nav->removePage(0),
            'remove32'     => $nav->removePage(32),
            'remove0again' => $nav->removePage(0),
            'remove1000'   => $nav->removePage(1000),
            'count'        => $nav->count(),
            'current'      => $nav->current()->getLabel(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testRemovingPageByInstance(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
            ],
        ]);

        $page3 = Page\AbstractPage::factory([
            'label' => 'Page 3',
            'uri'   => '#',
        ]);

        $nav->addPage($page3);

        $this->assertTrue($nav->removePage($page3));
    }

    public function testRemovingPageByInstanceShouldReturnFalseIfPageIsNotInContainer(): void
    {
        $nav = new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => '#',
            ],
            [
                'label' => 'Page 2',
                'uri'   => '#',
            ],
        ]);

        $page = Page\AbstractPage::factory([
            'label' => 'Page lol',
            'uri'   => '#',
        ]);

        $this->assertFalse($nav->removePage($page));
    }

    public function testHasPage(): void
    {
        $page0 = Page\AbstractPage::factory([
            'label' => 'Page 0',
            'uri'   => '#',
        ]);

        $page1 = Page\AbstractPage::factory([
            'label' => 'Page 1',
            'uri'   => '#',
        ]);

        $page11 = Page\AbstractPage::factory([
            'label' => 'Page 1.1',
            'uri'   => '#',
        ]);

        $page12 = Page\AbstractPage::factory([
            'label' => 'Page 1.2',
            'uri'   => '#',
        ]);

        $page121 = Page\AbstractPage::factory([
            'label' => 'Page 1.2.1',
            'uri'   => '#',
        ]);

        $page13 = Page\AbstractPage::factory([
            'label' => 'Page 1.3',
            'uri'   => '#',
        ]);

        $page2 = Page\AbstractPage::factory([
            'label' => 'Page 2',
            'uri'   => '#',
        ]);

        $page3 = Page\AbstractPage::factory([
            'label' => 'Page 3',
            'uri'   => '#',
        ]);

        $nav = new Navigation\Navigation([$page1, $page2, $page3]);

        $page1->addPage($page11);
        $page1->addPage($page12);
        $page12->addPage($page121);
        $page1->addPage($page13);

        $expected = [
            'haspage0'            => false,
            'haspage2'            => true,
            'haspage1_1'          => false,
            'haspage1_1recursive' => true,
        ];

        $actual = [
            'haspage0'            => $nav->hasPage($page0),
            'haspage2'            => $nav->hasPage($page2),
            'haspage1_1'          => $nav->hasPage($page11),
            'haspage1_1recursive' => $nav->hasPage($page11, true),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testHasPages(): void
    {
        $nav1 = new Navigation\Navigation();
        $nav2 = new Navigation\Navigation();
        $nav3 = new Navigation\Navigation();
        $nav4 = new Navigation\Navigation();
        $nav2->addPage([
            'label' => 'Page 1',
            'uri'   => '#',
        ]);
        $nav3->addPage([
            'label'   => 'Page 1',
            'uri'     => '#',
            'visible' => true,
        ]);
        $nav4->addPage([
            'label'   => 'Page 1',
            'uri'     => '#',
            'visible' => false,
        ]);

        $expected = [
            'empty'      => false,
            'notempty'   => true,
            'visible'    => true,
            'notvisible' => false,
        ];

        $actual = [
            'empty'      => $nav1->hasPages(),
            'notempty'   => $nav2->hasPages(),
            'visible'    => $nav3->hasPages(false),
            'notvisible' => $nav4->hasPages(true),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testSetParentShouldWorkWithPage(): void
    {
        $page1 = Page\AbstractPage::factory([
            'label' => 'Page 1',
            'uri'   => '#',
        ]);

        self::assertInstanceOf(Uri::class, $page1);

        $page2 = Page\AbstractPage::factory([
            'label' => 'Page 2',
            'uri'   => '#',
        ]);

        self::assertInstanceOf(Uri::class, $page2);

        $page2->setParent($page1);

        $expected = [
            'parent'   => 'Page 1',
            'hasPages' => true,
        ];

        $parent = $page2->getParent();
        self::assertSame($page1, $parent);

        $actual = [
            'parent'   => $parent->getLabel(),
            'hasPages' => $page1->hasPages(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testSetParentShouldWorkWithNull(): void
    {
        $page1 = Page\AbstractPage::factory([
            'label' => 'Page 1',
            'uri'   => '#',
        ]);

        $page2 = Page\AbstractPage::factory([
            'label' => 'Page 2',
            'uri'   => '#',
        ]);

        $page2->setParent($page1);
        $page2->setParent(null);

        $this->assertNull($page2->getParent());
    }

    public function testSetParentShouldRemoveFromOldParentPage(): void
    {
        $page1 = Page\AbstractPage::factory([
            'label' => 'Page 1',
            'uri'   => '#',
        ]);

        $page2 = Page\AbstractPage::factory([
            'label' => 'Page 2',
            'uri'   => '#',
        ]);

        $page2->setParent($page1);
        $page2->setParent(null);

        $expected = [
            'parent'   => null,
            'haspages' => false,
        ];

        $actual = [
            'parent'   => $page2->getParent(),
            'haspages' => $page2->hasPages(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testFinderMethodsShouldWorkWithCustomProperties(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findOneBy('page2', 'page2');
        $this->assertInstanceOf(AbstractPage::class, $found);
        $this->assertEquals('Page 2', $found->getLabel());
    }

    public function testFindOneByShouldReturnOnlyOnePage(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findOneBy('id', 'page_2_and_3');
        $this->assertInstanceOf(AbstractPage::class, $found);
        $this->assertEquals('Page 2', $found->getLabel());
    }

    public function testFindOneByShouldReturnNullIfNotFound(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findOneBy('id', 'non-existent');
        $this->assertNull($found);
    }

    public function testFindOneByWithIntegerAsStringValueShouldReturnPage(): void
    {
        $nav = $this->_getFindByNavigation();
        $nav->addPage(
            [
                'label'      => 'Page 4',
                'module'     => 'page4',
                'controller' => 'index',
                'action'     => 'about',
                'integer'    => 1000,
            ]
        );

        $found = $nav->findOneBy('integer', '1000');
        $this->assertInstanceOf(AbstractPage::class, $found);
        $this->assertEquals('Page 4', $found->getLabel());
    }

    public function testFindAllByShouldReturnAllMatchingPages(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findAllBy('id', 'page_2_and_3');
        $this->assertContainsOnlyInstancesOf(AbstractPage::class, $found);

        $expected = ['Page 2', 'Page 3'];
        $actual   = [];

        foreach ($found as $page) {
            $actual[] = $page->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testFindAllByShouldReturnEmptyArrayifNotFound(): void
    {
        $nav   = $this->_getFindByNavigation();
        $found = $nav->findAllBy('id', 'non-existent');

        $expected = ['type' => 'array', 'count' => 0];
        $actual   = ['type' => gettype($found), 'count' => count($found)];
        $this->assertEquals($expected, $actual);
    }

    public function testFindAllByWithIntegerAsStringValueShouldReturnPage(): void
    {
        $nav = $this->_getFindByNavigation();
        $nav->addPage(
            [
                'label'      => 'Page 4',
                'module'     => 'page4',
                'controller' => 'index',
                'action'     => 'about',
                'integer'    => 1000,
            ]
        );
        $nav->addPage(
            [
                'label'      => 'Page 5',
                'module'     => 'page5',
                'controller' => 'index',
                'action'     => 'about',
                'integer'    => 1000,
            ]
        );

        $found = $nav->findAllBy('integer', '1000');
        $this->assertContainsOnlyInstancesOf(AbstractPage::class, $found);

        $expected = ['Page 4', 'Page 5'];
        $actual   = [];

        foreach ($found as $page) {
            $actual[] = $page->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testFindByShouldDefaultToFindOneBy(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findBy('id', 'page_2_and_3');
        $this->assertInstanceOf(AbstractPage::class, $found);
    }

    public function testFindOneByMagicMethodNativeProperty(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findOneById('page_2_and_3');
        $this->assertInstanceOf(AbstractPage::class, $found);
        $this->assertEquals('Page 2', $found->getLabel());
    }

    public function testFindOneByMagicMethodCustomProperty(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findOneBypage2('page2');
        $this->assertInstanceOf(AbstractPage::class, $found);
        $this->assertEquals('Page 2', $found->getLabel());
    }

    public function testFindAllByWithMagicMethodNativeProperty(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findAllById('page_2_and_3');
        $this->assertContainsOnlyInstancesOf(AbstractPage::class, $found);

        $expected = ['Page 2', 'Page 3'];
        $actual   = [];
        foreach ($found as $page) {
            $actual[] = $page->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testFindAllByMagicUcfirstPropDoesNotFindCustomLowercaseProps(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findAllByAction('about');
        $this->assertContainsOnlyInstancesOf(AbstractPage::class, $found);

        $expected = ['Page 3'];
        $actual   = [];
        foreach ($found as $page) {
            $actual[] = $page->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testFindAllByMagicLowercaseFindsBothNativeAndCustomProps(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findAllByaction('about');
        $this->assertContainsOnlyInstancesOf(AbstractPage::class, $found);

        $expected = ['Page 1.3', 'Page 3'];
        $actual   = [];
        foreach ($found as $page) {
            $actual[] = $page->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testFindByMagicMethodIsEquivalentToFindOneBy(): void
    {
        $nav = $this->_getFindByNavigation();

        $found = $nav->findById('page_2_and_3');
        $this->assertInstanceOf(AbstractPage::class, $found);
        $this->assertEquals('Page 2', $found->getLabel());
    }

    public function testInvalidMagicFinderMethodShouldThrowException(): void
    {
        $nav = $this->_getFindByNavigation();

        $this->expectException(Navigation\Exception\BadMethodCallException::class);
        $this->expectExceptionMessage('Bad method call');
        $nav->findSomeById('page_2_and_3');
    }

    public function testInvalidMagicMethodShouldThrowException(): void
    {
        $nav = $this->_getFindByNavigation();

        $this->expectException(Navigation\Exception\BadMethodCallException::class);
        $this->expectExceptionMessage('Bad method call');
        $nav->getPagez();
    }

    // @codingStandardsIgnoreStart
    /**
     * @return Navigation\Navigation<AbstractPage>
     */
    protected function _getFindByNavigation(): Navigation\Navigation
    {
        // @codingStandardsIgnoreEnd
        // findAllByFoo('bar')         // Page 1, Page 1.1
        // findById('page_2_and_3')    // Page 2
        // findOneById('page_2_and_3') // Page 2
        // findAllById('page_2_and_3') // Page 2, Page 3
        // findAllByAction('about')    // Page 1.3, Page 3
        return new Navigation\Navigation([
            [
                'label' => 'Page 1',
                'uri'   => 'page-1',
                'foo'   => 'bar',
                'pages' => [
                    [
                        'label' => 'Page 1.1',
                        'uri'   => 'page-1.1',
                        'foo'   => 'bar',
                        'title' => 'The given title',
                    ],
                    [
                        'label' => 'Page 1.2',
                        'uri'   => 'page-1.2',
                        'title' => 'The given title',
                    ],
                    [
                        'type'   => 'uri',
                        'label'  => 'Page 1.3',
                        'uri'    => 'page-1.3',
                        'title'  => 'The given title',
                        'action' => 'about',
                    ],
                ],
            ],
            [
                'id'         => 'page_2_and_3',
                'label'      => 'Page 2',
                'module'     => 'page2',
                'controller' => 'index',
                'action'     => 'page1',
                'page2'      => 'page2',
            ],
            [
                'id'         => 'page_2_and_3',
                'label'      => 'Page 3',
                'module'     => 'page3',
                'controller' => 'index',
                'action'     => 'about',
            ],
        ]);
    }

    public function testCurrent(): void
    {
        $container = new Navigation\Navigation([
            [
                'label' => 'Page 2',
                'type'  => 'uri',
            ],
            [
                'label' => 'Page 1',
                'type'  => 'uri',
                'order' => -1,
            ],
        ]);

        $page = $container->current();
        $this->assertEquals('Page 1', $page->getLabel());
    }

    public function testCurrentShouldThrowExceptionIfIndexIsInvalid(): void
    {
        $container = new AbstractContainer();
        $container->addPage([
            'label' => 'Page 1',
            'type'  => 'uri',
        ]);

        $this->expectException(Navigation\Exception\OutOfBoundsException::class);
        $this->expectExceptionMessage('Corruption detected');
        $container->current();
    }

    public function testKeyWhenContainerIsEmpty(): void
    {
        $container = new Navigation\Navigation();
        $this->assertNull($container->key());
    }

    public function testKeyShouldReturnCurrentPageHash(): void
    {
        $container = new Navigation\Navigation();
        $page      = Page\AbstractPage::factory([
            'type' => 'uri',
        ]);
        $container->addPage($page);

        $this->assertEquals($page->hashCode(), $container->key());
    }

    public function testGetChildrenShouldReturnTheCurrentPage(): void
    {
        $container = new Navigation\Navigation();
        $page      = Page\AbstractPage::factory([
            'type' => 'uri',
        ]);
        $container->addPage($page);

        $this->assertEquals($page, $container->getChildren());
    }

    public function testGetChildrenShouldReturnNullWhenContainerIsEmpty(): void
    {
        $container = new Navigation\Navigation();

        $this->assertNull($container->getChildren());
    }

    #[Group('GH-5929')]
    public function testRemovePageRecursively(): void
    {
        $container = new Navigation\Navigation([
            [
                'route' => 'foo',
                'pages' => [
                    [
                        'route' => 'bar',
                        'pages' => [
                            [
                                'route' => 'baz',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->removePage($container->findOneBy('route', 'baz'), true);
        $this->assertNull($container->findOneBy('route', 'baz'));
        $container->removePage($container->findOneBy('route', 'bar'), true);
        $this->assertNull($container->findOneBy('route', 'bar'));
    }

    public function testModifyOrderUpdatedTriggersResort(): void
    {
        $container = new Navigation\Navigation();

        $page1 = Page\AbstractPage::factory(['label' => 'Page 1', 'uri' => '#', 'order' => 10]);
        $page2 = Page\AbstractPage::factory(['label' => 'Page 2', 'uri' => '#', 'order' => 5]);

        $container->addPage($page1);
        $container->addPage($page2);

        $container->rewind();
        $this->assertSame('Page 2', $container->current()->getLabel());

        $page2->setOrder(20);

        $container->rewind();
        $this->assertSame('Page 1', $container->current()->getLabel());
    }

    public function testHasChildrenReturnsTrueWhenCurrentPageHasChildren(): void
    {
        $container = new Navigation\Navigation([
            [
                'label' => 'Parent',
                'uri'   => '#',
                'pages' => [
                    ['label' => 'Child', 'uri' => '#'],
                ],
            ],
        ]);

        $container->rewind();
        $this->assertTrue($container->hasChildren());
    }

    public function testHasChildrenReturnsFalseWhenCurrentPageHasNoChildren(): void
    {
        $container = new Navigation\Navigation([
            ['label' => 'Page without children', 'uri' => '#'],
        ]);

        $container->rewind();
        $this->assertFalse($container->hasChildren());
    }

    public function testGetChildrenReturnsCurrentPage(): void
    {
        $container = new Navigation\Navigation([
            [
                'label' => 'Parent',
                'uri'   => '#',
                'pages' => [
                    ['label' => 'Child', 'uri' => '#'],
                ],
            ],
        ]);

        $container->rewind();
        $children = $container->getChildren();

        $this->assertInstanceOf(Page\AbstractPage::class, $children);
        $this->assertSame('Parent', $children->getLabel());
    }

    public function testAddPageThrowsExceptionWhenAddingContainerToItself(): void
    {
        $container = new Navigation\Navigation();

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('A page cannot have itself as a parent');
        $container->addPage($container);
    }

    public function testAddPageThrowsExceptionForInvalidType(): void
    {
        $container = new Navigation\Navigation();

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $options must be an array or Traversable');
        $container->addPage('invalid string');
    }

    public function testAddPageReturnsSameInstanceWhenAddingDuplicatePage(): void
    {
        $container = new Navigation\Navigation();
        $page      = Page\AbstractPage::factory(['label' => 'Test', 'uri' => '#']);

        $result1 = $container->addPage($page);
        $result2 = $container->addPage($page);

        $this->assertSame($container, $result1);
        $this->assertSame($container, $result2);
        $this->assertCount(1, $container);
    }

    public function testRemovePageReturnsFalseForInvalidType(): void
    {
        $container = new Navigation\Navigation([
            ['label' => 'Page 1', 'uri' => '#'],
        ]);

        $this->assertFalse($container->removePage('invalid string'));
    }

    public function testHasPagesWithOnlyVisibleReturnsTrue(): void
    {
        $container = new Navigation\Navigation([
            ['label' => 'Visible Page', 'uri' => '#', 'visible' => true],
        ]);

        $this->assertTrue($container->hasPages(true));
    }

    public function testFindByWithAllTrueReturnsArray(): void
    {
        $container = new Navigation\Navigation([
            ['label' => 'Page 1', 'uri' => '#', 'class' => 'nav-item'],
            ['label' => 'Page 2', 'uri' => '#', 'class' => 'nav-item'],
            ['label' => 'Page 3', 'uri' => '#', 'class' => 'other'],
        ]);

        $result = $container->findBy('class', 'nav-item', true);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
