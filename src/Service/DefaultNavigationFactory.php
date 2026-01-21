<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

use Override;

/**
 * Default navigation factory.
 *
 * @psalm-suppress DeprecatedInterface
 * @final
 */
class DefaultNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @return string
     */
    #[Override]
    protected function getName()
    {
        return 'default';
    }
}
