<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\Service\TestAsset;

use Laminas\Navigation\Service\AbstractNavigationFactory;

class TestNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @var string
     */
    private $factoryName;

    /**
     * @param string $factoryName
     */
    public function __construct($factoryName = 'test')
    {
        $this->factoryName = $factoryName;
    }

    /**
     * @return string
     */
    protected function getName()
    {
        return $this->factoryName;
    }
}
