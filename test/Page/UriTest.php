<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\Page;

use Laminas\Http\Request;
use Laminas\Navigation;
use Laminas\Navigation\Page;

/**
 * Tests the class Laminas_Navigation_Page_Uri
 *
 * @group      Laminas_Navigation
 */
class UriTest extends \PHPUnit_Framework_TestCase
{
    public function testUriOptionAsString()
    {
        $page = new Page\Uri(array(
            'label' => 'foo',
            'uri' => '#'
        ));

        $this->assertEquals('#', $page->getUri());
    }

    public function testUriOptionAsNull()
    {
        $page = new Page\Uri(array(
            'label' => 'foo',
            'uri' => null
        ));

        $this->assertNull($page->getUri(), 'getUri() should return null');
    }

    public function testUriOptionAsInteger()
    {
        try {
            $page = new Page\Uri(array('uri' => 1337));
            $this->fail('An invalid \'uri\' was given, but ' .
                        'a Laminas\Navigation\Exception\InvalidArgumentException was not thrown');
        } catch (Navigation\Exception\InvalidArgumentException $e) {
        }
    }

    public function testUriOptionAsObject()
    {
        try {
            $uri = new \stdClass();
            $uri->foo = 'bar';

            $page = new Page\Uri(array('uri' => $uri));
            $this->fail('An invalid \'uri\' was given, but ' .
                        'a Laminas\Navigation\Exception\InvalidArgumentException was not thrown');
        } catch (Navigation\Exception\InvalidArgumentException $e) {
        }
    }

    public function testSetAndGetUri()
    {
        $page = new Page\Uri(array(
            'label' => 'foo',
            'uri' => '#'
        ));

        $page->setUri('http://www.example.com/')->setUri('about:blank');

        $this->assertEquals('about:blank', $page->getUri());
    }

    public function testGetHref()
    {
        $uri = 'spotify:album:4YzcWwBUSzibRsqD9Sgu4A';

        $page = new Page\Uri();
        $page->setUri($uri);

        $this->assertEquals($uri, $page->getHref());
    }

    public function testIsActiveReturnsTrueWhenHasMatchingRequestUri()
    {
        $page = new Page\Uri(array(
            'label' => 'foo',
            'uri' => '/bar'
        ));

        $request = new Request();
        $request->setUri('/bar');
        $request->setMethod('GET');

        $page->setRequest($request);

        $this->assertInstanceOf('Laminas\Http\Request', $page->getRequest());

        $this->assertTrue($page->isActive());
    }

    public function testIsActiveReturnsFalseOnNonMatchingRequestUri()
    {
        $page = new Page\Uri(array(
            'label' => 'foo',
            'uri' => '/bar'
        ));

        $request = new Request();
        $request->setUri('/baz');
        $request->setMethod('GET');

        $page->setRequest($request);

        $this->assertFalse($page->isActive());
    }

    /**
     * @group Laminas-8922
     */
    public function testGetHrefWithFragmentIdentifier()
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
