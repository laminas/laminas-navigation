<?php

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Navigation\Page\AbstractPage;

class Page extends AbstractPage
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
