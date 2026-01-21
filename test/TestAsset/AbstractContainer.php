<?php

declare(strict_types=1);

namespace LaminasTest\Navigation\TestAsset;

use Laminas\Navigation\Page\AbstractPage;
use Traversable;

/** @template-extends \Laminas\Navigation\AbstractContainer<AbstractPage> */
final class AbstractContainer extends \Laminas\Navigation\AbstractContainer
{
    /**
     * @param AbstractPage|array|Traversable $page
     */
    public function addPage($page): static
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        parent::addPage($page);
        $this->pages = [];
        return $this;
    }
}
