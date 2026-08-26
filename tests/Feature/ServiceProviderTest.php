<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Sifrious\Menard\MenardServiceProvider;

it('registers the service provider', function (): void {
    expect($this->app->getLoadedProviders())->toHaveKey(MenardServiceProvider::class);
});

it('merges the package configuration', function (): void {
    expect(config('menard'))->toBeArray();
});

it('publishes the package configuration under its own tag', function (): void {
    expect(ServiceProvider::pathsToPublish(MenardServiceProvider::class, 'menard-config'))->not->toBeEmpty();
});
