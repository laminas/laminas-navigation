<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Navigation\View;

use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\View\Helper\Navigation\AbstractHelper;
use Psr\Container\ContainerInterface;

class RoleFromAuthenticationIdentityDelegator
{
    /** @var string */
    private $authenticationServiceName;

    /** @var string */
    private $getRoleMethodName;

    public function __construct(
        string $authenticationServiceName = AuthenticationServiceInterface::class,
        string $getRoleMethodName = 'getRole'
    ) {
        $this->authenticationServiceName = $authenticationServiceName;
        $this->getRoleMethodName         = $getRoleMethodName;
    }

    public static function __set_state(array $state): self
    {
        return new self(
            $state['authenticationServiceName'] ??
            AuthenticationServiceInterface::class,
            $state['getRoleMethodName'] ?? 'getRole',
        );
    }

    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        array $options = null
    ) {
        $helper = $callback();

        if (! $helper instanceof AbstractHelper) {
            return $helper;
        }

        if (! $container->has($this->authenticationServiceName)) {
            return $helper;
        }

        $authenticationService = $container->get(
            $this->authenticationServiceName
        );
        if (! $authenticationService instanceof
              AuthenticationServiceInterface) {
            return $helper;
        }

        if (! $authenticationService->hasIdentity()) {
            return $helper;
        }

        $identity = $authenticationService->getIdentity();

        $role = null;
        if (is_object($identity)
            && method_exists($identity, $this->getRoleMethodName)
        ) {
            /** @var mixed $role */
            $role = $identity->{$this->getRoleMethodName}();
        }

        $helper->setRole($role);

        return $helper;
    }
}
