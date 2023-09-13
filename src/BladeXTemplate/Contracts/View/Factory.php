<?php

namespace Yng\View\BladeXTemplate\Contracts\View;

interface Factory
{
    /**
     * 确定给定视图是否存在
     *
     * @param  string  $view
     * @return bool
     */
    public function exists(string $view): bool;

    /**
     * 获取给定路径的求值视图内容
     *
     * @param  string  $path
     * @param  \Yng\View\BladeXTemplate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Yng\View\BladeXTemplate\Contracts\View\View
     */
    public function file($path, $data = [], $mergeData = []);

    /**
     * 获取给定视图的求值视图内容
     *
     * @param  string  $view
     * @param  \Yng\View\BladeXTemplate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Yng\View\BladeXTemplate\Contracts\View\View
     */
    public function make($view, $data = [], $mergeData = []);

    /**
     * 向环境中添加一段共享数据
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function share($key, $value = null);
}
