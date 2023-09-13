<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\View\Compilers\Concerns;

trait CompilesFragments
{
    /**
     * The last compiled fragment.
     *
     * @var string
     */
    protected $lastFragment;

    /**
     * Compile the fragment statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileFragment($expression)
    {
        $this->lastFragment = trim($expression, "()'\" ");

        return "<?php \$__env->startFragment{$expression}; ?>";
    }

    /**
     * Compile the end-fragment statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndfragment()
    {
        return '<?php echo $__env->stopFragment(); ?>';
    }
}