<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\Page;

use Laminas\Http\Request;
use Laminas\Navigation;
use Laminas\Navigation\Page;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests the class Laminas_Navigation_Page_Uri
 *
 * @group      Laminas_Navigation
 */
class UriTest extends TestCase
{
    public function testUriOptionAsString(): void
    {
        $page = new Page\Uri([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $this->assertEquals('#', $page->getUri());
    }

    public function testUriOptionAsNull(): void
    {
        $page = new Page\Uri([
            'label' => 'foo',
            'uri'   => null,
        ]);

        $this->assertNull($page->getUri(), 'getUri() should return null');
    }

    public function testUriOptionAsInteger(): void
    {
        $this->expectException(
            Navigation\Exception\InvalidArgumentException::class
        );

        new Page\Uri(['uri' => 1337]);
    }

    public function testUriOptionAsObject(): void
    {
        $this->expectException(
            Navigation\Exception\InvalidArgumentException::class
        );

        $uri      = new stdClass();
        $uri->foo = 'bar';

        new Page\Uri(['uri' => $uri]);
    }

    public function testSetAndGetUri(): void
    {
        $page = new Page\Uri([
            'label' => 'foo',
            'uri'   => '#',
        ]);

        $page->setUri('http://www.example.com/')->setUri('about:blank');

        $this->assertEquals('about:blank', $page->getUri());
    }

    public function testGetHref(): void
    {
        $uri = 'spotify:album:4YzcWwBUSzibRsqD9Sgu4A';

        $page = new Page\Uri();
        $page->setUri($uri);

        $this->assertEquals($uri, $page->getHref());
    }

    public function testIsActiveReturnsTrueWhenHasMatchingRequestUri(): void
    {
        $page = new Page\Uri([
            'label' => 'foo',
            'uri'   => '/bar',
        ]);

        $request = new Request();
        $request->setUri('/bar');
        $request->setMethod('GET');

        $page->setRequest($request);

        $this->assertInstanceOf(Request::class, $page->getRequest());

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsFalseOnNonMatchingRequestUri(): void
    {
        $page = new Page\Uri([
            'label' => 'foo',
            'uri'   => '/bar',
        ]);

        $request = new Request();
        $request->setUri('/baz');
        $request->setMethod('GET');

        $page->setRequest($request);

        $this->assertFalse($page->isActive());
    }

    /**
     * @group Laminas-8922
     */
    public function testGetHrefWithFragmentIdentifier(): void
    {
        $uri = 'http://www.example.com/foo.html';

        $page = new Page\Uri();
        $page->setUri($uri);
        $page->setFragment('bar');

        $this->assertEquals($uri . '#bar', $page->getHref());

        $page->setUri('#');

        $this->assertEquals('#bar', $page->getHref());
    }
}
