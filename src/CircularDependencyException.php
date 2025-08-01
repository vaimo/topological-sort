<?php
/**
 * Copyright © Marc J. Schmidt. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vaimo\TopSort;

class CircularDependencyException extends \Exception
{
    protected $nodes;
    protected $end;

    /**
     * @param string     $message
     * @param int        $code
     * @param \Exception|null $previous
     * @param string[]   $nodes
     */
    public function __construct($message, $code, $previous, $nodes)
    {
        parent::__construct($message, $code, $previous);
        array_pop($nodes);
        $this->end = $nodes[count($nodes) - 1];
        $this->nodes = $nodes;
    }

    /**
     * @param string[] $nodes
     *
     * @return CircularDependencyException
     */
    public static function create($nodes)
    {
        $path = implode('->', $nodes);
        $message = sprintf('Circular dependency found: %s', $path);
        $exception = new static($message, 0, null, $nodes);
        return $exception;
    }

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->nodes[0];
    }

    /**
     * @return string
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @return mixed
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
