<?php

namespace Yng\View\BladeXTemplate\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Yng\View\BladeXTemplate\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue();
}
