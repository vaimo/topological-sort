<?php
/**
 * Copyright © Marc J. Schmidt. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vaimo\TopSort;

class ElementNotFoundException extends \Exception
{
    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $target;

    /**
     * @param string     $message
     * @param int        $code
     * @param \Exception|null $previous
     * @param string     $source
     * @param string     $target
     */
    public function __construct($message, $code, $previous, $source, $target)
    {
        parent::__construct($message, $code, $previous);
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return static
     */
    public static function create($source, $target)
    {
        $message = sprintf('Dependency `%s` not found, required by `%s`', $target, $source);
        $exception = new static($message, 0, null, $source, $target);

        return $exception;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }
}
