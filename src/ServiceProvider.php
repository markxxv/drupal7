<?php

namespace Knowfox\Drupal7;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Knowfox\Drupal7\Commands\ImportDrupal7;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportDrupal7::class,
            ]);
        }
    }

    public function register()
    {
    }
}