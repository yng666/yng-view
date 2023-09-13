<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\Contracts\Support;

interface Jsonable
{
    /**
     * 将对象转换为其JSON表示形式
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
}
