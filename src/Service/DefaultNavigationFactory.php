<?php

namespace Laminas\Navigation\Service;

/**
 * Default navigation factory.
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
