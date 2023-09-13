<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\Contracts\Support;

interface Htmlable
{
    /**
     * 获取HTML字符串形式的内容
     *
     * @return string
     */
    public function toHtml();
}
