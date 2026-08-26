<?php

declare(strict_types=1);

namespace Sifrious\Menard;

use Illuminate\Support\ServiceProvider;

class MenardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/menard.php', 'menard');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/menard.php' => $this->app->configPath('menard.php'),
            ], 'menard-config');
        }
    }
}
