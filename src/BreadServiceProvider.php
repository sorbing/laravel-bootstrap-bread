<?php

namespace Sorbing\Bread;

use Illuminate\Support\ServiceProvider;

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

        // Register a Commands
        if ($this->app->runningInConsole()) {
            $this->commands([Commands\BreadControllerCommand::class]);
        }
    }

    public function provides()
    {
        return ['bread'];
    }

    public function register()
    {
        $this->app->singleton('bread', \Sorbing\Bread\Services\BreadService::class);
    }
}
