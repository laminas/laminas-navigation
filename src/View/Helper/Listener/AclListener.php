<?php

declare(strict_types=1);

namespace Laminas\Navigation\View\Helper\Listener;

use Laminas\EventManager\Event;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Role\RoleInterface;

use function assert;
use function is_string;

final class AclListener
{
    /**
     * Determines whether a page should be accepted by ACL when iterating
     *
     * - If helper has no ACL, page is accepted
     * - If page has a resource or privilege defined, page is accepted if the
     *   ACL allows access to it using the helper's role
     * - If page has no resource or privilege, page is accepted
     * - If helper has ACL and role:
     *      - Page is accepted if it has no resource or privilege.
     *      - Page is accepted if ACL allows page's resource or privilege.
     *
     * @return bool
     */
    public static function accept(Event $event)
    {
        $accepted = true;
        $params   = $event->getParams();
        $acl      = $params['acl'] ?? null;
        $page     = $params['page'] ?? null;
        $role     = $params['role'] ?? null;

        if (! $acl instanceof Acl) {
            return true;
        }

        assert($page instanceof AbstractPage);
        assert($role instanceof RoleInterface || is_string($role) || $role === null);

        $resource  = $page->getResource();
        $privilege = $page->getPrivilege();

        if ($resource !== null) {
            $accepted = $acl->hasResource($resource)
                && $acl->isAllowed($role, $resource, $privilege);
        }

        return $accepted;
    }
}
