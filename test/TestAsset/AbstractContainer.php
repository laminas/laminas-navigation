<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

class AbstractContainer extends \Laminas\Navigation\AbstractContainer
{
    public function addPage($page)
    {
        parent::addPage($page);
        $this->pages = [];
    }
}
