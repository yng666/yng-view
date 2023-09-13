<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\View\Compilers\Concerns;

trait CompilesClasses
{
    /**
     * Compile the conditional class statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileClass($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "class=\"<?php echo \Yng\View\BladeXTemplate\Support\Arr::toCssClasses{$expression}; ?>\"";
    }
}
