<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\View\Compilers\Concerns;

trait CompilesInjections
{
    /**
     * Compile the inject statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInject($expression)
    {
        $segments = explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression));

        $variable = trim($segments[0]);

        $service = trim($segments[1]);

        return "<?php \${$variable} = app('{$service}'); ?>";
    }
}