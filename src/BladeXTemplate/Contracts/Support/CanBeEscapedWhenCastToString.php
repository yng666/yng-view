<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\Contracts\Support;

interface CanBeEscapedWhenCastToString
{
    /**
     * 表明当__toString被调用时，对象的字符串表示应该被转义。
     *
     * @param  bool  $escape
     * @return $this
     */
    public function escapeWhenCastingToString($escape = true);
}
