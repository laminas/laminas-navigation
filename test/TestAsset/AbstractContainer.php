<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation\TestAsset;

/**
 * @category   Laminas
 * @package    Laminas_Navigation
 * @subpackage UnitTests
 */
class AbstractContainer extends \Laminas\Navigation\AbstractContainer
{
    public function addPage($page)
    {
        parent::addPage($page);
        $this->pages = array();
    }
}
