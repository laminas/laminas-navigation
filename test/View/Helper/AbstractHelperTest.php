<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\View\Helper;

use Laminas\Navigation\Navigation;
use Laminas\Navigation\View\Helper as NavigationHelper;

class AbstractHelperTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->helper = new NavigationHelper\Breadcrumbs();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->helper->setDefaultAcl(null);
        $this->helper->setAcl(null);
        $this->helper->setDefaultRole(null);
        $this->helper->setRole(null);
    }

    public function testHasACLChecksDefaultACL(): void
    {
        $aclContainer = $this->getAcl();
        $acl          = $aclContainer['acl'];

        $this->assertEquals(false, $this->helper->hasACL());
        $this->helper->setDefaultAcl($acl);
        $this->assertEquals(true, $this->helper->hasAcl());
    }

    public function testHasACLChecksMemberVariable(): void
    {
        $aclContainer = $this->getAcl();
        $acl          = $aclContainer['acl'];

        $this->assertEquals(false, $this->helper->hasAcl());
        $this->helper->setAcl($acl);
        $this->assertEquals(true, $this->helper->hasAcl());
    }

    public function testHasRoleChecksDefaultRole(): void
    {
        $aclContainer = $this->getAcl();
        $role         = $aclContainer['role'];

        $this->assertEquals(false, $this->helper->hasRole());
        $this->helper->setDefaultRole($role);
        $this->assertEquals(true, $this->helper->hasRole());
    }

    public function testHasRoleChecksMemberVariable(): void
    {
        $aclContainer = $this->getAcl();
        $role         = $aclContainer['role'];

        $this->assertEquals(false, $this->helper->hasRole());
        $this->helper->setRole($role);
        $this->assertEquals(true, $this->helper->hasRole());
    }

    public function testEventManagerIsNullByDefault(): void
    {
        $this->assertNull($this->helper->getEventManager());
    }

    public function testFallBackForContainerNames(): void
    {
        // Register navigation service with name equal to the documentation
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setService(
            'navigation',
            $this->serviceManager->get('Navigation')
        );
        $this->serviceManager->setAllowOverride(false);

        $this->helper->setServiceLocator($this->serviceManager);

        $this->helper->setContainer('navigation');
        $this->assertInstanceOf(
            Navigation::class,
            $this->helper->getContainer()
        );

        $this->helper->setContainer('default');
        $this->assertInstanceOf(
            Navigation::class,
            $this->helper->getContainer()
        );
    }
}
