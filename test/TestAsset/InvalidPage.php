<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

final class InvalidPage
{
    /**
     * Returns the page's href
     *
     * @return string
     */
    public function getHref()
    {
        return '#';
    }
}
