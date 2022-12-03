<?php

declare(strict_types=1);

namespace Laminas\Navigation;

use Laminas\Navigation\Page\AbstractPage;
use Traversable;

use function is_array;

/**
 * A simple container class for {@link Laminas\Navigation\Page} pages
 *
 * @template TPage of AbstractPage
 * @template-extends AbstractContainer<TPage>
 */
class Navigation extends AbstractContainer
{
    /**
     * Creates a new navigation container
     *
     * @param  array|Traversable $pages    [optional] pages to add
     * @throws Exception\InvalidArgumentException  If $pages is invalid.
     */
    public function __construct($pages = null)
    {
        if ($pages && (! is_array($pages) && ! $pages instanceof Traversable)) {
            throw new Exception\InvalidArgumentException(
                'Invalid argument: $pages must be an array, an '
                . 'instance of Traversable, or null'
            );
        }

        if ($pages) {
            $this->addPages($pages);
        }
    }
}
