<?php
/**
 * Copyright © Marc J. Schmidt. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vaimo\TopSort\Implementations;

use Vaimo\TopSort\CircularDependencyException;

abstract class BaseImplementation
{
    /**
     * @var bool
     */
    protected $detectCircularRefs = true;

    /**
     * @var callable
     */
    protected $circularInterceptor;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param array $elements
     * @param bool $detectCircularRefs
     */
    public function __construct(array $elements = array(), $detectCircularRefs = true)
    {
        $this->set($elements);
        $this->detectCircularRefs = $detectCircularRefs;
    }

    /**
     * @param callable $circularInterceptor
     */
    public function setCircularInterceptor($circularInterceptor)
    {
        $this->circularInterceptor = $circularInterceptor;
    }

    abstract public function set(array $elements);

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param object   $element
     * @param object[] $parents
     *
     * @throws CircularDependencyException
     */
    protected function throwCircularExceptionIfNeeded($element, $parents)
    {
        if (!$this->isThrowCircularDependency()) {
            return;
        }

        if (isset($parents[$element->id])) {
            $nodes = array_keys($parents);
            $nodes[] = $element->id;

            if (!$this->circularInterceptor) {
                throw CircularDependencyException::create($nodes);
            }

            call_user_func($this->circularInterceptor, $nodes);
        }
    }

    /**
     * @return boolean
     */
    public function isThrowCircularDependency()
    {
        return $this->detectCircularRefs;
    }

    /**
     * @param boolean $detectCircularRefs
     */
    public function setThrowCircularDependency($detectCircularRefs)
    {
        $this->detectCircularRefs = $detectCircularRefs;
    }
}
