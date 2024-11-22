<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View\Helper;

use Laminas\Navigation\Navigation;
use Laminas\Navigation\View\Helper\Breadcrumbs;
use Laminas\View\Exception\InvalidArgumentException;

use function extension_loaded;
use function strlen;
use function substr;
use function trim;

class BreadcrumbsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->helper = new Breadcrumbs();
        parent::setUp();
    }

    public function testCanRenderStraightFromServiceAlias(): void
    {
        $this->helper->setServiceLocator($this->serviceManager);

        $returned = $this->helper->renderStraight('Navigation');
        $this->assertEquals($returned, $this->getExpectedFileContents('bc/default.html'));
    }

    public function testCanRenderPartialFromServiceAlias(): void
    {
        $this->helper->setPartial('bc.phtml');
        $this->helper->setServiceLocator($this->serviceManager);

        $returned = $this->helper->renderPartial('Navigation');
        $this->assertEquals($returned, $this->getExpectedFileContents('bc/partial.html'));
    }

    public function testHelperEntryPointWithoutAnyParams(): void
    {
        $returned = $this->helper->__invoke();
        $this->assertEquals($this->helper, $returned);
        $this->assertEquals($this->nav1, $returned->getContainer());
    }

    public function testHelperEntryPointWithContainerParam(): void
    {
        $returned = $this->helper->__invoke($this->nav2);
        $this->assertEquals($this->helper, $returned);
        $this->assertEquals($this->nav2, $returned->getContainer());
    }

    public function testHelperEntryPointWithContainerStringParam(): void
    {
        $this->helper->setServiceLocator($this->serviceManager);

        $returned = $this->helper->__invoke('nav1');
        $this->assertEquals($this->helper, $returned);
        $this->assertEquals($this->nav1, $returned->getContainer());
    }

    public function testNullOutContainer(): void
    {
        $old = $this->helper->getContainer();
        $this->helper->setContainer();
        $new = $this->helper->getContainer();

        $this->assertNotEquals($old, $new);
    }

    public function testSetSeparator(): void
    {
        $this->helper->setSeparator('foo');

        $expected = $this->getExpectedFileContents('bc/separator.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testSetMaxDepth(): void
    {
        $this->helper->setMaxDepth(1);

        $expected = $this->getExpectedFileContents('bc/maxdepth.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testSetMinDepth(): void
    {
        $this->helper->setMinDepth(1);

        $expected = '';
        $this->assertEquals($expected, $this->helper->render($this->nav2));
    }

    public function testLinkLastElement(): void
    {
        $this->helper->setLinkLast(true);

        $expected = $this->getExpectedFileContents('bc/linklast.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testSetIndent(): void
    {
        $this->helper->setIndent(8);

        $expected = '        <a';
        $actual   = substr($this->helper->render(), 0, strlen($expected));

        $this->assertEquals($expected, $actual);
    }

    public function testRenderSuppliedContainerWithoutInterfering(): void
    {
        $this->helper->setMinDepth(0);

        $rendered1 = $this->getExpectedFileContents('bc/default.html');
        $rendered2 = '<span aria-current="page">Site 2</span>';

        $expected = [
            'registered'       => $rendered1,
            'supplied'         => $rendered2,
            'registered_again' => $rendered1,
        ];

        $actual = [
            'registered'       => $this->helper->render(),
            'supplied'         => $this->helper->render($this->nav2),
            'registered_again' => $this->helper->render(),
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testUseAclResourceFromPages(): void
    {
        $acl = $this->getAcl();
        $this->helper->setAcl($acl['acl']);
        $this->helper->setRole($acl['role']);

        $expected = $this->getExpectedFileContents('bc/acl.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testTranslationUsingLaminasTranslate(): void
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $this->helper->setTranslator($this->getTranslator());

        $expected = $this->getExpectedFileContents('bc/translated.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testTranslationUsingLaminasTranslateAndCustomTextDomain(): void
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $this->helper->setTranslator($this->getTranslatorWithTextDomain());

        $expected = $this->getExpectedFileContents('bc/textdomain.html');
        $test     = $this->helper->render($this->nav3);

        $this->assertEquals(trim($expected), trim($test));
    }

    public function testTranslationUsingLaminasTranslateAdapter(): void
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $translator = $this->getTranslator();
        $this->helper->setTranslator($translator);

        $expected = $this->getExpectedFileContents('bc/translated.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testDisablingTranslation(): void
    {
        $translator = $this->getTranslator();
        $this->helper->setTranslator($translator);
        $this->helper->setTranslatorEnabled(false);

        $expected = $this->getExpectedFileContents('bc/default.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testRenderingPartial(): void
    {
        $this->helper->setPartial('bc.phtml');

        $expected = $this->getExpectedFileContents('bc/partial.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testRenderingPartialWithSeparator(): void
    {
        $this->helper->setPartial('bc_separator.phtml')->setSeparator(' / ');

        $expected = trim($this->getExpectedFileContents('bc/partialwithseparator.html'));
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testRenderingPartialBySpecifyingAnArrayAsPartial(): void
    {
        $this->helper->setPartial(['bc.phtml', 'application']);

        $expected = $this->getExpectedFileContents('bc/partial.html');
        $this->assertEquals($expected, $this->helper->render());
    }

    public function testRenderingPartialShouldFailOnInvalidPartialArray(): void
    {
        $this->helper->setPartial(['bc.phtml']);
        $this->expectException(InvalidArgumentException::class);
        $this->helper->render();
    }

    public function testRenderingPartialWithParams(): void
    {
        $this->helper->setPartial('bc_with_partial_params.phtml')->setSeparator(' / ');
        $expected = $this->getExpectedFileContents('bc/partial_with_params.html');
        $actual   = $this->helper->renderPartialWithParams(['variable' => 'test value']);
        $this->assertEquals($expected, $actual);
    }

    public function testLastBreadcrumbShouldBeEscaped(): void
    {
        $container = new Navigation([
            [
                'label'  => 'Live & Learn',
                'uri'    => '#',
                'active' => true,
            ],
        ]);

        $expected = '<span aria-current="page">Live &amp; Learn</span>';
        $actual   = $this->helper->setMinDepth(0)->render($container);

        $this->assertEquals($expected, $actual);
    }
}
