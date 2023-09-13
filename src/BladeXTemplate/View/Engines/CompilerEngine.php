<?php

namespace Yng\View\BladeXTemplate\View\Engines;

use ErrorException;
use Exception;
use Throwable;
use Yng\View\BladeXTemplate\View\Compilers\CompilerInterface;
use Yng\View\Exception\ViewException;
use Yng\Exception\HttpException;
use Yng\Exception\HttpResponseException;

use function Yng\View\BladeXTemplate\Support\last;
use function Yng\View\BladeXTemplate\Support\str;

class CompilerEngine extends PhpEngine
{
    /**
     * The Blade compiler instance.
     *
     * @var \Yng\View\BladeXTemplate\View\Compilers\CompilerInterface
     */
    protected $compiler;

    /**
     * A stack of the last compiled templates.
     *
     * @var array
     */
    protected $lastCompiled = [];

    /**
     * The view paths that were compiled or are not expired, keyed by the path.
     *
     * @var array<string, true>
     */
    protected $compiledOrNotExpired = [];

    /**
     * Create a new compiler engine instance.
     *
     * @param  \Yng\View\BladeXTemplate\View\Compilers\CompilerInterface  $compiler
     * @return void
     */
    public function __construct(CompilerInterface $compiler)
    {
        parent::__construct();

        $this->compiler = $compiler;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        $this->lastCompiled[] = $path;

        // If this given view has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the views so we can evaluate a
        // fresh copy of the view. We'll pass the compiler the path of the view.
        if (! isset($this->compiledOrNotExpired[$path]) && $this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        // Once we have the path to the compiled file, we will evaluate the paths with
        // typical PHP just like any other templates. We also keep a stack of views
        // which have been rendered for right exception messages to be generated.

        try {
            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        } catch (ViewException $e) {
            if (! str($e->getMessage())->contains(['No such file or directory', 'File does not exist at path'])) {
                throw $e;
            }

            if (! isset($this->compiledOrNotExpired[$path])) {
                throw $e;
            }

            $this->compiler->compile($path);

            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        }

        $this->compiledOrNotExpired[$path] = true;

        array_pop($this->lastCompiled);

        return $results;
    }

    /**
     * Handle a view exception.
     *
     * @param  \Throwable  $e
     * @param  int  $obLevel
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleViewException(Throwable $e, $obLevel)
    {
        if ($e instanceof HttpException || $e instanceof HttpResponseException) {
            parent::handleViewException($e, $obLevel);
        }

        $e = new ViewException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

        parent::handleViewException($e, $obLevel);
    }


    /**
     * Get the exception message for an exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function getMessage(Throwable $e)
    {
        return $e->getMessage().' (View: '.realpath(last($this->lastCompiled)).')';
    }

    /**
     * Get the compiler implementation.
     *
     * @return \Yng\View\BladeXTemplate\View\Compilers\CompilerInterface
     */
    public function getCompiler()
    {
        return $this->compiler;
    }

    /**
     * Clear the cache of views that were compiled or not expired.
     *
     * @return void
     */
    public function forgetCompiledOrNotExpired()
    {
        $this->compiledOrNotExpired = [];
    }
}