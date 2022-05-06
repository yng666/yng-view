<?php
declare(strict_types=1);

namespace Yng\View\Engines;

use Yng\View\Contracts\ViewEngineInterface;

/**
 * @class   AbstractEngine
 * @author  Yng
 * @date    2022/01/23
 * @time    18:13
 * @package Yng\View\Engines
 */
abstract class AbstractEngine implements ViewEngineInterface
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        foreach ($options as $key => $option) {
            $this->{$key} = $option;
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
