<?php

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Navigation\Service\AbstractNavigationFactory;

class FileNavigationFactory extends AbstractNavigationFactory
{
    protected function getName()
    {
        return 'file';
    }
}
