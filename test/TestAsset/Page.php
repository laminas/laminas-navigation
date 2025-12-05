<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Navigation\Page\AbstractPage;

final class Page extends AbstractPage
{
    /**
     * Returns the page's href
     */
    public function getHref(): string
    {
        return '#';
    }
}
