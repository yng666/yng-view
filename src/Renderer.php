<?php
declare(strict_types=1);

namespace Yng\View;

use Yng\View\Contracts\ViewEngineInterface;

class Renderer
{
    /**
     * @var ViewEngineInterface
     */
    protected ViewEngineInterface $viewEngine;

    /**
     * Renderer constructor.
     *
     * @param ViewEngineInterface $viewEngine
     */
    public function __construct(ViewEngineInterface $viewEngine)
    {
        $this->viewEngine = $viewEngine;
    }

    /**
     * 设置模板目录
     *
     * @param string $path
     *
     * @return void
     */
    public function setPath(string $path)
    {
        $this->viewEngine->setPath($path);
    }

    /**
     * @param string $template
     * @param array  $arguments
     *
     * @return false|string
     */
    public function render(string $template, array $arguments = [])
    {
        ob_start();
        echo (string)$this->viewEngine->render($template, $arguments);
        return ob_get_clean();
    }
}
