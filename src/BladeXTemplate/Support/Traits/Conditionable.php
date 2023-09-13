<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\Support\Traits;

use Closure;
use Yng\View\BladeXTemplate\Support\HigherOrderWhenProxy;

trait Conditionable
{
    /**
     * 如果给定的“值”为真(或解析为真)，则应用回调。
     *
     * @template TWhenReturnType
     *
     * @param  bool  $value
     * @param  (callable($this): TWhenReturnType)|null  $callback
     * @param  (callable($this): TWhenReturnType)|null  $default
     * @return $this|TWhenReturnType
     */
    public function when($value, callable $callback = null, callable $default = null)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (! $callback) {
            return new HigherOrderWhenProxy($this, $value);
        }

        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * 如果给定的“值”为(或解析为)false，则应用回调。
     *
     * @template TUnlessReturnType
     *
     * @param  bool  $value
     * @param  (callable($this): TUnlessReturnType)  $callback
     * @param  (callable($this): TUnlessReturnType)|null  $default
     * @return $this|TUnlessReturnType
     */
    public function unless($value, callable $callback = null, callable $default = null)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (! $callback) {
            return new HigherOrderWhenProxy($this, ! $value);
        }

        if (! $value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }
}
