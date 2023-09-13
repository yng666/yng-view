<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\View\Compilers\Concerns;

use Yng\View\BladeXTemplate\Support\Str;

/**
 * auth相关需要自行实现一个挂载进app的auth服务且实现如下方法
 * 
 * @method auth->check 判断当前用户是否登录
 * @method auth->guest 判断当前用户是否为游客
 */
trait CompilesConditionals
{
    /**
     * Identifier for the first case in switch statement.
     *
     * @var bool
     */
    protected $firstCaseInSwitch = true;

    /**
     * Compile the if-auth statements into valid PHP.
     *
     * @param  string|null  $guard
     * @return string
     */
    protected function compileAuth()
    {
        return "<?php if(app('auth')->check()): ?>";
    }

    /**
     * Compile the else-auth statements into valid PHP.
     *
     * @param  string|null  $guard
     * @return string
     */
    protected function compileElseAuth()
    {
        return "<?php elseif(app('auth')->check()): ?>";
    }

    /**
     * Compile the end-auth statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndAuth()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the if-guest statements into valid PHP.
     *
     * @param  string|null  $guard
     * @return string
     */
    protected function compileGuest()
    {
        return "<?php if(app('auth')->guest()): ?>";
    }

    /**
     * Compile the else-guest statements into valid PHP.
     *
     * @param  string|null  $guard
     * @return string
     */
    protected function compileElseGuest()
    {
        return "<?php elseif(app('auth')->guest()): ?>";
    }

    /**
     * Compile the end-guest statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndGuest()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the has-section statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileHasSection($expression)
    {
        return "<?php if (! empty(trim(\$__env->yieldContent{$expression}))): ?>";
    }
    
    /**
     * Compile the section-missing statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSectionMissing($expression)
    {
        return "<?php if (empty(trim(\$__env->yieldContent{$expression}))): ?>";
    }

    /**
     * Compile the if statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIf($expression)
    {
        return "<?php if{$expression}: ?>";
    }

    /**
     * Compile the unless statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileUnless($expression)
    {
        return "<?php if (! {$expression}): ?>";
    }

    /**
     * Compile the else-if statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElseif($expression)
    {
        return "<?php elseif{$expression}: ?>";
    }

    /**
     * Compile the else statements into valid PHP.
     *
     * @return string
     */
    protected function compileElse()
    {
        return '<?php else: ?>';
    }

    /**
     * Compile the end-if statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndif()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-unless statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndunless()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the if-isset statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIsset($expression)
    {
        return "<?php if(isset{$expression}): ?>";
    }

    /**
     * Compile the end-isset statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndIsset()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the switch statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSwitch($expression)
    {
        $this->firstCaseInSwitch = true;

        return "<?php switch{$expression}:";
    }

    /**
     * Compile the case statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCase($expression)
    {
        if ($this->firstCaseInSwitch) {
            $this->firstCaseInSwitch = false;

            return "case {$expression}: ?>";
        }

        return "<?php case {$expression}: ?>";
    }

    /**
     * Compile the default statements in switch case into valid PHP.
     *
     * @return string
     */
    protected function compileDefault()
    {
        return '<?php default: ?>';
    }

    /**
     * Compile the end switch statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndSwitch()
    {
        return '<?php endswitch; ?>';
    }

    /**
     * Compile a once block into valid PHP.
     *
     * @param  string|null  $id
     * @return string
     */
    protected function compileOnce($id = null)
    {
        $id = $id ? $this->stripParentheses($id) : "'".(string) Str::uuid()."'";

        return '<?php if (! $__env->hasRenderedOnce('.$id.')): $__env->markAsRenderedOnce('.$id.'); ?>';
    }

    /**
     * Compile an end-once block into valid PHP.
     *
     * @return string
     */
    public function compileEndOnce()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile a selected block into valid PHP.
     *
     * @param  string  $condition
     * @return string
     */
    protected function compileSelected($condition)
    {
        return "<?php if{$condition}: echo 'selected'; endif; ?>";
    }

    /**
     * Compile a checked block into valid PHP.
     *
     * @param  string  $condition
     * @return string
     */
    protected function compileChecked($condition)
    {
        return "<?php if{$condition}: echo 'checked'; endif; ?>";
    }

    /**
     * Compile a disabled block into valid PHP.
     *
     * @param  string  $condition
     * @return string
     */
    protected function compileDisabled($condition)
    {
        return "<?php if{$condition}: echo 'disabled'; endif; ?>";
    }

    /**
     * Compile a required block into valid PHP.
     *
     * @param  string  $condition
     * @return string
     */
    protected function compileRequired($condition)
    {
        return "<?php if{$condition}: echo 'required'; endif; ?>";
    }

    /**
     * Compile a readonly block into valid PHP.
     *
     * @param  string  $condition
     * @return string
     */
    protected function compileReadonly($condition)
    {
        return "<?php if{$condition}: echo 'readonly'; endif; ?>";
    }

    /**
     * class current
     *
     * @param  string  $condition
     * @return string
     */
    protected function compileCurrent($expression)
    {
        [$condition, $simple, $class] = str_contains($expression, ',')
                    ? array_map('trim', explode(',', trim($expression, '()'), 3)) + ['', '', '']
                    : [trim($expression, '()'), '', ''];

        if ($class) {
            if (str_contains($class, '\'')) {
                $class = trim($class, '\'');
            }
        } else {
            $class = 'current';
        }

        if ($simple !== 'false') {
            $class = ' class="'. $class .'"';
        }

        return "<?php if({$condition}): echo '{$class}'; endif; ?>";
    }


    /**
     * Compile the push statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePushIf($expression)
    {
        $parts = explode(',', $this->stripParentheses($expression), 2);

        return "<?php if({$parts[0]}): \$__env->startPush({$parts[1]}); ?>";
    }

    /**
     * Compile the end-push statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndPushIf()
    {
        return '<?php $__env->stopPush(); endif; ?>';
    }
}

