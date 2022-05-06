<?php

namespace Yng\View\Contracts;

interface ViewEngineInterface
{
    public function setPath(string $path);

    public function render(string $template, array $arguments = []);
}
