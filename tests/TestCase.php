<?php

namespace IvanBaric\Pages\Tests;

use IvanBaric\Corexis\CorexisServiceProvider;
use IvanBaric\Pages\PagesServiceProvider;
use IvanBaric\TemplateEngine\TemplateEngineServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CorexisServiceProvider::class,
            TemplateEngineServiceProvider::class,
            LivewireServiceProvider::class,
            PagesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
