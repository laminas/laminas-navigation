<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Navigation\View;

use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Navigation\View\RoleFromAuthenticationIdentityDelegator;
use Laminas\View\Helper\Navigation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class RoleFromAuthenticationIdentityDelegatorTest extends TestCase
{
    public function testRoleShouldBeSetForHelper(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn($this->getAuthenticationServiceMockObject());

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator())(
            $container,
            'name',
            $callback
        );

        $this->assertSame('test', $result->getRole());
    }

    public function testRoleShouldBeSetForHelperWithCustomAuthenticationServiceName(): void
    {
        $customName = 'alternate-authentication';

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with($customName)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($customName)
            ->willReturn($this->getAuthenticationServiceMockObject());

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator($customName))(
            $container,
            'name',
            $callback
        );

        $this->assertSame('test', $result->getRole());
    }

    public function testRoleShouldBeSetForHelperWithCustomGetRoleMethodName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn($this->getAuthenticationServiceMockObject(false));

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator(
            AuthenticationServiceInterface::class,
            'alternateName'
        ))(
            $container,
            'name',
            $callback
        );

        $this->assertSame('test', $result->getRole());
    }

    public function testNoneNavigationHelperPassedAsGiven(): void
    {
        $class = new stdClass();

        $callback = static function () use ($class) {
            return $class;
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator())(
            $this->createMock(ContainerInterface::class),
            'name',
            $callback
        );

        $this->assertSame($class, $result);
    }

    public function testNoneExistingAuthenticationServiceWillHelperPassedAsGiven(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn(false);

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator())(
            $container,
            'name',
            $callback
        );

        $this->assertNull($result->getRole());
    }

    public function testWrongAuthenticationServiceWillHelperPassedAsGiven(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn(null);

        $callback = static function () {
                    return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator())(
            $container,
            'name',
            $callback
        );

        $this->assertNull($result->getRole());
    }

    public function testNoIdentityWillHelperPassedAsGiven(): void
    {
        $authenticationService = $this->createMock(
            AuthenticationServiceInterface::class
        );
        $authenticationService->expects($this->once())
            ->method('hasIdentity')
            ->willReturn(false);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(AuthenticationServiceInterface::class)
            ->willReturn($authenticationService);

        $callback = static function () {
            return new Navigation();
        };

        /** @var Navigation $result */
        $result = (new RoleFromAuthenticationIdentityDelegator())(
            $container,
            'name',
            $callback
        );

        $this->assertNull($result->getRole());
    }

    private function getAuthenticationServiceMockObject(
        bool $useDefaultGetRoleMethodName = true
    ): MockObject {
        if ($useDefaultGetRoleMethodName) {
            $identity = new class() {
                public function getRole(): string
                {
                    return 'test';
                }
            };
        } else {
            $identity = new class() {
                public function alternateName(): string
                {
                    return 'test';
                }
            };
        }

        $authenticationService = $this->createMock(
            AuthenticationServiceInterface::class
        );
        $authenticationService->expects($this->once())
            ->method('hasIdentity')
            ->willReturn(true);
        $authenticationService->expects($this->once())
            ->method('getIdentity')
            ->willReturn($identity);

        return $authenticationService;
    }
}
