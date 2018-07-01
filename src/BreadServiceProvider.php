<?php

namespace Sorbing\Bread;

use Illuminate\Support\ServiceProvider;
//use Sorbing\Bread\...;

class BreadServiceProvider extends ServiceProvider
{
    protected function getPackageIdentity()
    {
        return 'bread';
    }

    public function boot()
    {
        //$identity = $this->getPackageIdentity();
        $this->loadViewsFrom(__DIR__.'/views', 'bread');
    }

    public function provides()
    {
        return ['bread'];
    }

    public function register()
    {
        $this->app->singleton('bread', function () {
            return ['BREAD'];
        });
    }
}
