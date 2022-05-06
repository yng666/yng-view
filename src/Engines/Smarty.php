<?php
declare(strict_types=1);

namespace Yng\View\Engines;

/**
 * @class   Smarty
 * @author  Yng
 * @date    2022/01/23
 * @time    18:13
 * @package Yng\View\Engines
 */
class Smarty extends AbstractEngine
{
    /**
     * 后缀
     *
     * @var string
     */
    protected string $suffix = '';

    /**
     * @var \Smarty
     */
    protected \Smarty $smarty;

    /**
     * Smarty配置
     */
    public function __construct(array $options)
    {
        $this->smarty                  = new \Smarty();
        $this->smarty->debugging       = $options['debug'];
        $this->smarty->caching         = $options['cache'];
        $this->smarty->left_delimiter  = $options['left_delimiter'];
        $this->smarty->right_delimiter = $options['right_delimiter'];
        $this->smarty->setTemplateDir($options['path'])
                     ->setCompileDir($options['compile_dir'])
                     ->setCacheDir($options['cache_dir']);
        $this->suffix = $options['suffix'] ?? '.html';
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setPath(string $path)
    {
        $this->smarty->setTemplateDir($path);
    }

    /**
     * @param string $template
     * @param array  $arguments
     *
     * @return mixed
     */
    public function render(string $template, array $arguments = [])
    {
        foreach ($arguments as $key => $value) {
            $this->smarty->assign($key, $value);
        }
        return $this->smarty->display($template . $this->suffix);
    }
}
