<?php

namespace Yng\View\BladeXTemplate\Contracts\View;

use Yng\View\BladeXTemplate\Contracts\Support\Renderable;

interface View extends Renderable
{
    /**
     * 获取视图的名称
     *
     * @return string
     */
    public function name();

    /**
     * 向视图添加一段数据
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return $this
     */
    public function with($key, $value = null);

    /**
     * 获取视图数据数组
     *
     * @return array
     */
    public function getData();
}
