<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Navigation\View;

use Laminas\Navigation\View\PermissionAclDelegatorFactory;
use Laminas\Permissions\Acl\AclInterface;
use Laminas\View\Helper\Navigation;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class PermissionAclDelegatorFactoryTest extends TestCase
{
    public function testAclShouldBeSetForHelper(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AclInterface::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(AclInterface::class)
            ->willReturn($this->createMock(AclInterface::class));

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new PermissionAclDelegatorFactory())(
            $container,
            'name',
            $callback
        );

        $this->assertInstanceOf(
            AclInterface::class,
            $result->getAcl()
        );
    }

    public function testAclWithCustomNameShouldBeSetForHelper(): void
    {
        $customName = 'alternate-acl';

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with($customName)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($customName)
            ->willReturn($this->createMock(AclInterface::class));

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new PermissionAclDelegatorFactory($customName))(
            $container,
            'name',
            $callback
        );

        $this->assertInstanceOf(
            AclInterface::class,
            $result->getAcl()
        );
    }

    public function testNoneNavigationHelperPassedAsGiven(): void
    {
        $class = new stdClass();

        $callback = static function () use ($class) {
            return $class;
        };

        /** @var Navigation $result */
        $result = (new PermissionAclDelegatorFactory())(
            $this->createMock(ContainerInterface::class),
            'name',
            $callback
        );

        $this->assertSame($class, $result);
    }

    public function testNoneExistingAclWillHelperPassedAsGiven(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AclInterface::class)
            ->willReturn(false);

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new PermissionAclDelegatorFactory())(
            $container,
            'name',
            $callback
        );

        $this->assertNull($result->getAcl());
    }

    public function testWrongAclWillHelperPassedAsGiven(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AclInterface::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(AclInterface::class)
            ->willReturn(null);

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new PermissionAclDelegatorFactory())(
            $container,
            'name',
            $callback
        );

        $this->assertNull($result->getAcl());
    }

    public function testMagicMethodSetStateShouldContainAclClassName(): void
    {
        $this->assertStringContainsString(
            'Laminas\\\Permissions\\\Acl\\\AclInterface',
            var_export(new PermissionAclDelegatorFactory(), true)
        );
    }

    public function testMagicMethodSetStateShouldContainCustomNameIfSet(): void
    {
        $customName = 'alternate-name';

        $this->assertStringContainsString(
            $customName,
            var_export(new PermissionAclDelegatorFactory($customName), true)
        );
    }
}
