<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\TestAsset;

class InvalidPage
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
