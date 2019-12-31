<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\TestAsset;

class RecursiveIteratorIterator extends \RecursiveIteratorIterator
{
    /**
     *
     * @var \ArrayAccess|array
     */
    public $logger = [];

    public function beginIteration()
    {
        $this->logger[] = 'beginIteration';
    }

    public function endIteration()
    {
        $this->logger[] = 'endIteration';
    }

    public function beginChildren()
    {
        $this->logger[] = 'beginChildren';
    }

    public function endChildren()
    {
        $this->logger[] = 'endChildren';
    }

    public function current()
    {
        $this->logger[] = parent::current()->getLabel();
    }
}
