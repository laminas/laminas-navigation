<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

/**
 * Default navigation factory.
 *
 * @final
 */
class DefaultNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @return string
     */
    protected function getName()
    {
        return 'default';
    }
}
