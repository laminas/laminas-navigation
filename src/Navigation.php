<?php

declare(strict_types=1);

namespace Laminas\Navigation;

use Laminas\Navigation\Page\AbstractPage;
use Traversable;

use function is_array;

/**
 * A simple container class for {@link Page} pages
 *
 * @psalm-import-type PageOptions from Page\AbstractPage
 * @template TPage of AbstractPage
 * @template-extends AbstractContainer<TPage>
 * @final
 */
class Navigation extends AbstractContainer
{
    /**
     * Creates a new navigation container
     *
     * @param iterable<array-key, TPage|PageOptions|Traversable<string, mixed>|null>|null $pages pages to add
     * @throws Exception\InvalidArgumentException  If $pages is invalid.
     */
    public function __construct($pages = null)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($pages !== null && (! is_array($pages) && ! $pages instanceof Traversable)) {
            throw new Exception\InvalidArgumentException(
                'Invalid argument: $pages must be an array, an '
                . 'instance of Traversable, or null'
            );
        }

        if ($pages !== null) {
            $this->addPages($pages);
        }
    }
}
