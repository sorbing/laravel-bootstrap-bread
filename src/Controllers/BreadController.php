<?php

namespace Sorbing\Bread\Controllers;

use Sorbing\Bread\Controllers\BreadControllerTrait;

class BreadController
{
    use BreadControllerTrait;

    protected $breadTable  = null;
    protected $breadLayout = null;

    public function __construct()
    {
        if (!app()->runningInConsole()) {
            $route = \Route::current();
            $this->breadTable = head(array_slice(explode('.', $route->getName()), -2, 1));
            throw_if(!$this->breadTable, new \Exception('Failed detect a bread table name by router info!'));

            // @todo Подставить стандартный $breadLayout
            $this->breadLayout = env('BREAD_DEFAULT_LAYOUT', 'admin.layout.layout');
        }
    }
}