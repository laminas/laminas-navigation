<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Navigation\View;

use Laminas\Permissions\Acl\AclInterface;
use Laminas\View\Helper\Navigation\AbstractHelper;
use Psr\Container\ContainerInterface;

class PermissionAclDelegatorFactory
{
    /** @var string */
    private $aclName;

    public function __construct(string $aclName = AclInterface::class)
    {
        $this->aclName = $aclName;
    }

    public static function __set_state(array $state): self
    {
        return new self($state['aclName'] ?? AclInterface::class);
    }

    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        array $options = null
    ) {
        /** @var AbstractHelper|mixed $instance */
        $helper = $callback();

        if (! $helper instanceof AbstractHelper) {
            return $helper;
        }
        if (! $container->has($this->aclName)) {
            return $helper;
        }

        $acl = $container->get($this->aclName);
        if (! $acl instanceof AclInterface) {
            return $helper;
        }

        $helper->setAcl($acl);

        return $helper;
    }
}
