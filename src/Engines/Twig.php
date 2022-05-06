<?php
declare(strict_types=1);

namespace Yng\View\Engines;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @class   Twig
 * @author  Yng
 * @date    2022/01/23
 * @time    18:13
 * @package Yng\View\Engines
 */
class Twig extends AbstractEngine
{
    /**
     * 后缀
     *
     * @var string
     */
    protected string $suffix = '';

    /**
     * 调试
     *
     * @var bool
     */
    protected bool $debug = false;

    /**
     * 缓存
     *
     * @var bool
     */
    protected bool $cache = false;

    /**
     * 渲染模板
     *
     * @param array $arguments
     *
     * @return mixed
     */
    public function render(string $template, array $arguments = [])
    {
        $loader        = new FilesystemLoader($this->path);
        $this->handler = new Environment($loader, [
            'debug' => $this->debug,
            'cache' => $this->cache,
        ]);
        return $this->handler->render($template . $this->suffix, $arguments);
    }
}
