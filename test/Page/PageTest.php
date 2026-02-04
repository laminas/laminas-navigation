<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\Page;

use Laminas\Config;
use Laminas\Navigation;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Navigation\Page\Uri;
use Laminas\Permissions\Acl\Resource\GenericResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

use function ksort;

/**
 * Tests the class Laminas_Navigation_Page
 */
#[Group('Laminas_Navigation')]
final class PageTest extends TestCase
{
    public function testSetShouldMapToNativeProperties(): void
    {
        $page = AbstractPage::factory([
            'type' => 'mvc',
        ]);

        self::assertInstanceOf(Navigation\Page\Mvc::class, $page);

        $page->set('action', 'foo');
        $this->assertEquals('foo', $page->getAction());

        $page->set('Action', 'bar');
        $this->assertEquals('bar', $page->getAction());
    }

    public function testGetShouldMapToNativeProperties(): void
    {
        $page = AbstractPage::factory([
            'type' => 'mvc',
        ]);

        self::assertInstanceOf(Navigation\Page\Mvc::class, $page);

        $page->setAction('foo');
        $this->assertEquals('foo', $page->get('action'));

        $page->setAction('bar');
        $this->assertEquals('bar', $page->get('Action'));
    }

    public function testShouldSetAndGetShouldMapToProperties(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $page->set('action', 'Laughing Out Loud');
        $this->assertEquals('Laughing Out Loud', $page->get('action'));
    }

    public function testSetShouldNotMapToSetOptionsToPreventRecursion(): void
    {
        $page = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'foo',
        ]);

        $options = ['label' => 'bar'];
        $page->set('options', $options);

        $this->assertEquals('foo', $page->getLabel());
        $this->assertEquals($options, $page->get('options'));
    }

    public function testSetShouldNotMapToSetConfigToPreventRecursion(): void
    {
        $page = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'foo',
        ]);

        $options = ['label' => 'bar'];
        $page->set('config', $options);

        $this->assertEquals('foo', $page->getLabel());
        $this->assertEquals($options, $page->get('config'));
    }

    public function testSetShouldThrowExceptionIfPropertyIsNotString(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $this->expectException(Exception\InvalidArgumentException::class);
        /** @psalm-suppress InvalidArgument */
        $page->set([], true);
    }

    public function testSetShouldThrowExceptionIfPropertyIsEmpty(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $this->expectException(Exception\InvalidArgumentException::class);
        $page->set('', true);
    }

    public function testGetShouldThrowExceptionIfPropertyIsNotString(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $this->expectException(Exception\InvalidArgumentException::class);
        /** @psalm-suppress InvalidArgument */
        $page->get([]);
    }

    public function testGetShouldThrowExceptionIfPropertyIsEmpty(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $this->expectException(Exception\InvalidArgumentException::class);
        $page->get('');
    }

    public function testSetAndGetLabel(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertEquals('foo', $page->getLabel());
        $page->setLabel('bar');
        $this->assertEquals('bar', $page->getLabel());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidLabelProvider(): array
    {
        return [
            'integer' => [42],
            'object'  => [(object) null],
        ];
    }

    #[DataProvider('invalidLabelProvider')]
    public function testSetLabelThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $label');
        $page->setLabel($invalid);
    }

    #[Group('Laminas-8922')]
    public function testSetAndGetFragmentIdentifier(): void
    {
        $page = AbstractPage::factory([
            'uri'      => '#',
            'fragment' => 'foo',
        ]);

        $this->assertEquals('foo', $page->getFragment());

        $page->setFragment('bar');
        $this->assertEquals('bar', $page->getFragment());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidFragmentProvider(): array
    {
        return [
            'integer' => [42],
            'object'  => [(object) null],
        ];
    }

    #[Group('Laminas-8922')]
    #[DataProvider('invalidFragmentProvider')]
    public function testSetFragmentThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'uri'      => '#',
            'fragment' => 'foo',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $fragment');
        $page->setFragment($invalid);
    }

    public function testSetAndGetId(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertNull($page->getId());

        $page->setId('bar');
        $this->assertEquals('bar', $page->getId());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidIdProvider(): array
    {
        return [
            'boolean' => [true],
            'object'  => [(object) null],
        ];
    }

    #[DataProvider('invalidIdProvider')]
    public function testSetIdThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $id');
        $page->setId($invalid);
    }

    public function testIdCouldBeAnInteger(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
            'id'    => 10,
        ]);

        $this->assertEquals(10, $page->getId());
    }

    public function testSetAndGetClass(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertNull($page->getClass());
        $page->setClass('bar');
        $this->assertEquals('bar', $page->getClass());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidStringPropertyProvider(): array
    {
        return [
            'integer' => [42],
            'boolean' => [true],
            'object'  => [(object) null],
        ];
    }

    #[DataProvider('invalidStringPropertyProvider')]
    public function testSetClassThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $class');
        $page->setClass($invalid);
    }

    public function testSetAndGetTitle(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertNull($page->getTitle());
        $page->setTitle('bar');
        $this->assertEquals('bar', $page->getTitle());
    }

    #[DataProvider('invalidStringPropertyProvider')]
    public function testSetTitleThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $title');
        $page->setTitle($invalid);
    }

    public function testSetAndGetTarget(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertNull($page->getTarget());
        $page->setTarget('bar');
        $this->assertEquals('bar', $page->getTarget());
    }

    #[DataProvider('invalidStringPropertyProvider')]
    public function testSetTargetThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $target');
        $page->setTarget($invalid);
    }

    public function testConstructingWithRelationsInArray(): void
    {
        $page = AbstractPage::factory([
            'label' => 'bar',
            'uri'   => '#',
            'rel'   => [
                'prev' => 'foo',
                'next' => 'baz',
            ],
            'rev'   => [
                'alternate' => 'bat',
            ],
        ]);

        $expected = [
            'rel' => [
                'prev' => 'foo',
                'next' => 'baz',
            ],
            'rev' => [
                'alternate' => 'bat',
            ],
        ];

        $actual = [
            'rel' => $page->getRel(),
            'rev' => $page->getRev(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testConstructingWithRelationsInConfig(): void
    {
        $page = AbstractPage::factory(new Config\Config([
            'label' => 'bar',
            'uri'   => '#',
            'rel'   => [
                'prev' => 'foo',
                'next' => 'baz',
            ],
            'rev'   => [
                'alternate' => 'bat',
            ],
        ]));

        $expected = [
            'rel' => [
                'prev' => 'foo',
                'next' => 'baz',
            ],
            'rev' => [
                'alternate' => 'bat',
            ],
        ];

        $actual = [
            'rel' => $page->getRel(),
            'rev' => $page->getRev(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testConstructingWithTraversableOptions(): void
    {
        $options = ['label' => 'bar'];

        $page = new Uri(new Config\Config($options));

        $actual = ['label' => $page->getLabel()];

        $this->assertEquals($options, $actual);
    }

    public function testGettingSpecificRelations(): void
    {
        $page = AbstractPage::factory([
            'label' => 'bar',
            'uri'   => '#',
            'rel'   => [
                'prev' => 'foo',
                'next' => 'baz',
            ],
            'rev'   => [
                'next' => 'foo',
            ],
        ]);

        $expected = [
            'foo',
            'foo',
        ];

        $actual = [
            $page->getRel('prev'),
            $page->getRev('next'),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetOrder(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertNull($page->getOrder());

        $page->setOrder('1');
        $this->assertEquals(1, $page->getOrder());

        $page->setOrder(1337);
        $this->assertEquals(1337, $page->getOrder());

        $page->setOrder('-25');
        $this->assertEquals(-25, $page->getOrder());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidOrderProvider(): array
    {
        return [
            'float'        => [3.14],
            'non-numeric'  => ['e'],
            'newline'      => ["\n"],
            'comma-format' => ['0,4'],
            'boolean'      => [true],
            'object'       => [(object) null],
        ];
    }

    #[DataProvider('invalidOrderProvider')]
    public function testSetOrderThrowsExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $order');
        $page->setOrder($invalid);
    }

    public function testSetResourceString(): void
    {
        $page = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'hello',
        ]);

        $page->setResource('foo');
        $this->assertEquals('foo', $page->getResource());
    }

    public function testSetResourceNoParam(): void
    {
        $page = AbstractPage::factory([
            'type'     => 'uri',
            'label'    => 'hello',
            'resource' => 'foo',
        ]);

        $page->setResource();
        $this->assertNull($page->getResource());
    }

    public function testSetResourceNull(): void
    {
        $page = AbstractPage::factory([
            'type'     => 'uri',
            'label'    => 'hello',
            'resource' => 'foo',
        ]);

        $page->setResource(null);
        $this->assertNull($page->getResource());
    }

    public function testSetResourceInterface(): void
    {
        $page = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'hello',
        ]);

        $resource = new GenericResource('bar');

        $page->setResource($resource);
        $this->assertEquals($resource, $page->getResource());
    }

    /** @return array<string, array{0: mixed}> */
    public static function invalidResourceProvider(): array
    {
        return [
            'integer' => [0],
            'object'  => [new stdClass()],
        ];
    }

    #[DataProvider('invalidResourceProvider')]
    public function testSetResourceShouldThrowExceptionOnInvalidValue(mixed $invalid): void
    {
        $page = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'hello',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $resource');
        $page->setResource($invalid);
    }

    public function testSetPrivilegeNoParams(): void
    {
        $page = AbstractPage::factory([
            'type'      => 'uri',
            'label'     => 'hello',
            'privilege' => 'foo',
        ]);

        $page->setPrivilege();
        $this->assertNull($page->getPrivilege());
    }

    public function testSetPrivilegeNull(): void
    {
        $page = AbstractPage::factory([
            'type'      => 'uri',
            'label'     => 'hello',
            'privilege' => 'foo',
        ]);

        $page->setPrivilege(null);
        $this->assertNull($page->getPrivilege());
    }

    public function testSetPrivilegeString(): void
    {
        $page = AbstractPage::factory([
            'type'      => 'uri',
            'label'     => 'hello',
            'privilege' => 'foo',
        ]);

        $page->setPrivilege('bar');
        $this->assertEquals('bar', $page->getPrivilege());
    }

    public function testGetActiveOnNewlyConstructedPageShouldReturnFalse(): void
    {
        $page = new Uri();
        $this->assertFalse($page->getActive());
    }

    public function testIsActiveOnNewlyConstructedPageShouldReturnFalse(): void
    {
        $page = new Uri();
        $this->assertFalse($page->isActive());
    }

    public function testIsActiveRecursiveOnNewlyConstructedPageShouldReturnFalse(): void
    {
        $page = new Uri();
        $this->assertFalse($page->isActive(true));
    }

    public function testGetActiveShouldReturnTrueIfPageIsActive(): void
    {
        $page = new Uri(['active' => true]);
        $this->assertTrue($page->getActive());
    }

    public function testIsActiveShouldReturnTrueIfPageIsActive(): void
    {
        $page = new Uri(['active' => true]);
        $this->assertTrue($page->isActive());
    }

    public function testIsActiveWithRecursiveTrueShouldReturnTrueIfChildActive(): void
    {
        $page = new Uri([
            'label'  => 'Page 1',
            'active' => false,
            'pages'  => [
                new Uri([
                    'label'  => 'Page 1.1',
                    'active' => false,
                    'pages'  => [
                        new Uri([
                            'label'  => 'Page 1.1',
                            'active' => true,
                        ]),
                    ],
                ]),
            ],
        ]);

        $this->assertFalse($page->isActive(false));
        $this->assertTrue($page->isActive(true));
    }

    public function testGetActiveWithRecursiveTrueShouldReturnTrueIfChildActive(): void
    {
        $page = new Uri([
            'label'  => 'Page 1',
            'active' => false,
            'pages'  => [
                new Uri([
                    'label'  => 'Page 1.1',
                    'active' => false,
                    'pages'  => [
                        new Uri([
                            'label'  => 'Page 1.1',
                            'active' => true,
                        ]),
                    ],
                ]),
            ],
        ]);

        $this->assertFalse($page->getActive(false));
        $this->assertTrue($page->getActive(true));
    }

    public function testSetActiveWithNoParamShouldSetFalse(): void
    {
        $page = new Uri();
        $page->setActive();
        $this->assertTrue($page->getActive());
    }

    public function testSetActiveShouldJuggleValue(): void
    {
        $page = new Uri();

        $page->setActive(1);
        $this->assertTrue($page->getActive());

        $page->setActive('true');
        $this->assertTrue($page->getActive());

        $page->setActive(0);
        $this->assertFalse($page->getActive());

        $page->setActive([]);
        $this->assertFalse($page->getActive());
    }

    public function testIsVisibleOnNewlyConstructedPageShouldReturnTrue(): void
    {
        $page = new Uri();
        $this->assertTrue($page->isVisible());
    }

    public function testGetVisibleOnNewlyConstructedPageShouldReturnTrue(): void
    {
        $page = new Uri();
        $this->assertTrue($page->getVisible());
    }

    public function testIsVisibleShouldReturnFalseIfPageIsNotVisible(): void
    {
        $page = new Uri(['visible' => false]);
        $this->assertFalse($page->isVisible());
    }

    public function testGetVisibleShouldReturnFalseIfPageIsNotVisible(): void
    {
        $page = new Uri(['visible' => false]);
        $this->assertFalse($page->getVisible());
    }

    public function testIsVisibleRecursiveTrueShouldReturnFalseIfParentInivisble(): void
    {
        $page = new Uri([
            'label'   => 'Page 1',
            'visible' => false,
            'pages'   => [
                new Uri([
                    'label' => 'Page 1.1',
                    'pages' => [
                        new Uri([
                            'label' => 'Page 1.1',
                        ]),
                    ],
                ]),
            ],
        ]);

        $childPage = $page->findOneByLabel('Page 1.1');
        $this->assertTrue($childPage->isVisible(false));
        $this->assertFalse($childPage->isVisible(true));
    }

    public function testGetVisibleRecursiveTrueShouldReturnFalseIfParentInivisble(): void
    {
        $page = new Uri([
            'label'   => 'Page 1',
            'visible' => false,
            'pages'   => [
                new Uri([
                    'label' => 'Page 1.1',
                    'pages' => [
                        new Uri([
                            'label' => 'Page 1.1',
                        ]),
                    ],
                ]),
            ],
        ]);

        $childPage = $page->findOneByLabel('Page 1.1');
        $this->assertTrue($childPage->getVisible(false));
        $this->assertFalse($childPage->getVisible(true));
    }

    public function testSetVisibleWithNoParamShouldSetVisble(): void
    {
        $page = new Uri(['visible' => false]);
        $page->setVisible();
        $this->assertTrue($page->isVisible());
    }

    public function testSetVisibleShouldJuggleValue(): void
    {
        $page = new Uri();

        $page->setVisible(1);
        $this->assertTrue($page->isVisible());

        $page->setVisible('true');
        $this->assertTrue($page->isVisible());

        $page->setVisible(0);
        $this->assertFalse($page->isVisible());

        /**
         * Laminas-10146
         *
         * @link https://getlaminas.org/issues/browse/Laminas-10146
         */
        $page->setVisible('False');
        $this->assertFalse($page->isVisible());

        $page->setVisible([]);
        $this->assertFalse($page->isVisible());
    }

    public function testSetTranslatorTextDomainString(): void
    {
        $page = AbstractPage::factory([
            'type'  => 'uri',
            'label' => 'hello',
        ]);

        $page->setTextdomain('foo');
        $this->assertEquals('foo', $page->getTextdomain());
    }

    public function testMagicOverLoadsShouldSetAndGetNativeProperties(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => 'foo',
        ]);

        self::assertInstanceOf(Uri::class, $page);

        $this->assertSame('foo', $page->getUri());
        $this->assertSame('foo', $page->uri);

        $page->uri = 'bar';
        $this->assertSame('bar', $page->getUri());
        $this->assertSame('bar', $page->uri);
    }

    public function testMagicOverLoadsShouldCheckNativeProperties(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => 'foo',
        ]);

        $this->assertTrue(isset($page->uri));
    }

    public function testUnsetNativePropertyShouldThrowException(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => 'foo',
        ]);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsetting native property');
        unset($page->uri);
    }

    public function testMagicOverLoadsShouldHandleCustomProperties(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => 'foo',
        ]);

        $this->assertFalse(isset($page->category));

        $page->category = 'music';
        $this->assertTrue(isset($page->category));
        $this->assertSame('music', $page->category);

        unset($page->category);
        $this->assertFalse(isset($page->category));
    }

    public function testMagicToStringMethodShouldReturnLabel(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertEquals('foo', (string) $page);
    }

    public function testSetOptionsShouldTranslateToAccessor(): void
    {
        $page = AbstractPage::factory([
            'label'      => 'foo',
            'action'     => 'index',
            'controller' => 'index',
        ]);

        self::assertInstanceOf(Navigation\Page\Mvc::class, $page);

        $options = [
            'label'      => 'bar',
            'action'     => 'baz',
            'controller' => 'bat',
            'id'         => 'foo-test',
        ];

        $page->setOptions($options);

        $expected = [
            'label'      => 'bar',
            'action'     => 'baz',
            'controller' => 'bat',
            'id'         => 'foo-test',
        ];

        $actual = [
            'label'      => $page->getLabel(),
            'action'     => $page->getAction(),
            'controller' => $page->getController(),
            'id'         => $page->getId(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testSetOptionsShouldSetCustomProperties(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $options = [
            'test'    => 'test',
            'meaning' => 42,
        ];

        $page->setOptions($options);

        $actual = [
            'test'    => $page->test,
            'meaning' => $page->meaning,
        ];

        $this->assertEquals($options, $actual);
    }

    public function testAddingRelations(): void
    {
        $page = AbstractPage::factory([
            'label' => 'page',
            'uri'   => '#',
        ]);

        $page->addRel('alternate', 'foo');
        $page->addRev('alternate', 'bar');

        $expected = [
            'rel' => ['alternate' => 'foo'],
            'rev' => ['alternate' => 'bar'],
        ];

        $actual = [
            'rel' => $page->getRel(),
            'rev' => $page->getRev(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testRemovingRelations(): void
    {
        $page = AbstractPage::factory([
            'label' => 'page',
            'uri'   => '#',
        ]);

        $page->addRel('alternate', 'foo');
        $page->addRev('alternate', 'bar');
        $page->removeRel('alternate');
        $page->removeRev('alternate');

        $expected = [
            'rel' => [],
            'rev' => [],
        ];

        $actual = [
            'rel' => $page->getRel(),
            'rev' => $page->getRev(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testSetRelShouldWorkWithArray(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rel'  => [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        ]);

        $value = ['alternate' => 'format/xml'];
        $page->setRel($value);
        $this->assertEquals($value, $page->getRel());
    }

    public function testSetRelShouldWorkWithConfig(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rel'  => [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        ]);

        $value = ['alternate' => 'format/xml'];
        $page->setRel(new Config\Config($value));
        $this->assertEquals($value, $page->getRel());
    }

    public function testSetRelShouldWithNoParamsShouldResetRelations(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rel'  => [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        ]);

        $value = [];
        $page->setRel();
        $this->assertEquals($value, $page->getRel());
    }

    public function testSetRelShouldThrowExceptionWhenNotNullOrArrayOrConfig(): void
    {
        $page = AbstractPage::factory(['type' => 'uri']);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $relations');
        $page->setRel('alternate');
    }

    public function testSetRevShouldWorkWithArray(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rev'  => [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        ]);

        $value = ['alternate' => 'format/xml'];
        $page->setRev($value);
        $this->assertEquals($value, $page->getRev());
    }

    public function testSetRevShouldWorkWithConfig(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rev'  => [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        ]);

        $value = ['alternate' => 'format/xml'];
        $page->setRev(new Config\Config($value));
        $this->assertEquals($value, $page->getRev());
    }

    public function testSetRevShouldWithNoParamsShouldResetRelations(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rev'  => [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        ]);

        $value = [];
        $page->setRev();
        $this->assertEquals($value, $page->getRev());
    }

    public function testSetRevShouldThrowExceptionWhenNotNullOrArrayOrConfig(): void
    {
        $page = AbstractPage::factory(['type' => 'uri']);

        $this->expectException(Navigation\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument: $relations');
        $page->setRev('alternate');
    }

    public function testGetRelWithArgumentShouldRetrieveSpecificRelation(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rel'  => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertEquals('bar', $page->getRel('foo'));
    }

    public function testGetRevWithArgumentShouldRetrieveSpecificRelation(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rev'  => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertEquals('bar', $page->getRev('foo'));
    }

    public function testGetDefinedRel(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rel'  => [
                'alternate' => 'foo',
                'foo'       => 'bar',
            ],
        ]);

        $expected = ['alternate', 'foo'];
        $this->assertEquals($expected, $page->getDefinedRel());
    }

    public function testGetDefinedRev(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
            'rev'  => [
                'alternate' => 'foo',
                'foo'       => 'bar',
            ],
        ]);

        $expected = ['alternate', 'foo'];
        $this->assertEquals($expected, $page->getDefinedRev());
    }

    public function testGetCustomProperties(): void
    {
        $page = AbstractPage::factory([
            'label' => 'foo',
            'uri'   => '#',
            'baz'   => 'bat',
        ]);

        $options = [
            'test'    => 'test',
            'meaning' => 42,
        ];

        $page->setOptions($options);

        $expected = [
            'baz'     => 'bat',
            'test'    => 'test',
            'meaning' => 42,
        ];

        $this->assertEquals($expected, $page->getCustomProperties());
    }

    public function testToArrayMethod(): void
    {
        $options = [
            'label'      => 'foo',
            'uri'        => 'http://www.example.com/foo.html',
            'fragment'   => 'bar',
            'id'         => 'my-id',
            'class'      => 'my-class',
            'title'      => 'my-title',
            'target'     => 'my-target',
            'rel'        => [],
            'rev'        => [],
            'order'      => 100,
            'active'     => true,
            'visible'    => false,
            'resource'   => 'joker',
            'privilege'  => null,
            'permission' => null,
            'foo'        => 'bar',
            'meaning'    => 42,
            'pages'      => [
                [
                    'type'       => Uri::class,
                    'label'      => 'foo.bar',
                    'fragment'   => null,
                    'id'         => null,
                    'class'      => null,
                    'title'      => null,
                    'target'     => null,
                    'rel'        => [],
                    'rev'        => [],
                    'order'      => null,
                    'resource'   => null,
                    'privilege'  => null,
                    'permission' => null,
                    'active'     => null,
                    'visible'    => 1,
                    'pages'      => [],
                    'uri'        => 'http://www.example.com/foo.html',
                ],
                [
                    'label'      => 'foo.baz',
                    'type'       => Uri::class,
                    'fragment'   => null,
                    'id'         => null,
                    'class'      => null,
                    'title'      => null,
                    'target'     => null,
                    'rel'        => [],
                    'rev'        => [],
                    'order'      => null,
                    'resource'   => null,
                    'privilege'  => null,
                    'permission' => null,
                    'active'     => null,
                    'visible'    => 1,
                    'pages'      => [],
                    'uri'        => 'http://www.example.com/foo.html',
                ],
            ],
        ];

        $page    = AbstractPage::factory($options);
        $toArray = $page->toArray();

        // tweak options to what we expect toArray() to contain
        $options['type'] = Uri::class;

        ksort($options);
        ksort($toArray);
        $this->assertEquals($options, $toArray);
    }

    public function testSetPermission(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $page->setPermission('my_permission');
        $this->assertEquals('my_permission', $page->getPermission());
    }

    public function testSetArrayPermission(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $page->setPermission(['my_permission', 'other_permission']);
        $this->assertIsArray($page->getPermission());
        $this->assertCount(2, $page->getPermission());
    }

    public function testSetObjectPermission(): void
    {
        $page = AbstractPage::factory([
            'type' => 'uri',
        ]);

        $permission       = new stdClass();
        $permission->name = 'my_permission';

        $page->setPermission($permission);
        $this->assertInstanceOf('stdClass', $page->getPermission());
        $this->assertEquals('my_permission', $page->getPermission()->name);
    }

    public function testSetParentShouldThrowExceptionIfPageItselfIsParent(): void
    {
        $page = AbstractPage::factory(
            [
                'type' => 'uri',
            ]
        );

        $this->expectException(Exception\InvalidArgumentException::class);
        $page->setParent($page);
    }
}
