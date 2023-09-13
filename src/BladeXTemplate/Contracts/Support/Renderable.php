<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\Contracts\Support;

interface Renderable
{
    /**
     * 获取对象的求值内容
     *
     * @return string
     */
    public function render();
}
