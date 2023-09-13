<?php
declare (strict_types = 1);

namespace Yng\Blade\Facade;

use Yng\Facade;

class View extends Facade
{
    protected static function getFacadeClass()
    {
    	return 'blade.view';
    }
}