<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\Contracts\Support;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();
}
