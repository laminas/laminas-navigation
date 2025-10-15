<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Navigation\Service\AbstractNavigationFactory;

final class FileNavigationFactory extends AbstractNavigationFactory
{
    protected function getName(): string
    {
        return 'file';
    }
}
