<?php

declare(strict_types=1);

namespace Laminas\Navigation\Page;

use Laminas\Http\Request;
use Laminas\Navigation\Exception;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

use function array_merge;
use function is_string;
use function str_ends_with;

/**
 * Represents a page that is defined by specifying a URI
 */
final class Uri extends AbstractPage
{
    /**
     * Page URI
     */
    protected ?string $uri = null;

    /**
     * Request object used to determine uri path
     */
    protected ?Request $request = null;

    /**
     * Sets page URI
     *
     * @param  string|null $uri page URI, must a string or null
     * @throws Exception\InvalidArgumentException  If $uri is invalid.
     */
    public function setUri($uri): static
    {
        if (null !== $uri && ! is_string($uri)) {
            throw new Exception\InvalidArgumentException(
                'Invalid argument: $uri must be a string or null'
            );
        }

        $this->uri = $uri;
        return $this;
    }

    /**
     * Returns URI
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Returns href for this page
     *
     * Includes the fragment identifier if it is set.
     */
    public function getHref(): string
    {
        $uri = $this->getUri() ?? '';

        $fragment = $this->getFragment();
        if (null !== $fragment) {
            if (str_ends_with($uri, '#')) {
                return $uri . $fragment;
            } else {
                return $uri . '#' . $fragment;
            }
        }

        return $uri;
    }

    /**
     * Returns whether page should be considered active or not
     *
     * This method will compare the page properties against the request uri.
     *
     * @param bool $recursive
     *            [optional] whether page should be considered
     *            active if any child pages are active. Default is
     *            false.
     * @return bool whether page should be considered active or not
     */
    public function isActive($recursive = false): bool
    {
        if (! $this->active) {
            if ($this->getRequest() instanceof Request) {
                if ($this->getRequest()->getUri()->getPath() === $this->getUri()) {
                    $this->active = true;
                    return true;
                }
            }
        }

        return parent::isActive($recursive);
    }

    /**
     * Get the request
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Sets request for assembling URLs
     */
    public function setRequest(?Request $request = null): static
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Returns an array representation of the page
     *
     * @see ResourceInterface
     *
     * @psalm-return array{
     *     label: string|null,
     *     fragment: string|null,
     *     id: string|null,
     *     class: string|null,
     *     title: string|null,
     *     target: string|null,
     *     rel: array|null,
     *     rev: array|null,
     *     order: int|null,
     *     resource: ResourceInterface|string|null,
     *     privilege: string|null,
     *     permission: mixed|null,
     *     active: bool,
     *     visible: bool,
     *     pages: list<array>,
     *     uri: string|null,
     *     ...
     * }
     */
    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'uri' => $this->getUri(),
            ]
        );
    }
}
