<?php

namespace IvanBaric\Pages;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use IvanBaric\Pages\Livewire\Admin\PageForm;
use IvanBaric\Pages\Livewire\Admin\PageIndex;
use IvanBaric\Pages\Livewire\Admin\SectionItemsManager;
use IvanBaric\Pages\Livewire\Admin\SectionsManager;
use Livewire\Livewire;

final class PagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pages.php', 'pages');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pages');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'pages');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Livewire::component('pages::admin.pages.index', PageIndex::class);
        Livewire::component('pages::admin.pages.form', PageForm::class);
        Livewire::component('pages::admin.sections.manager', SectionsManager::class);
        Livewire::component('pages::admin.section-items.manager', SectionItemsManager::class);

        if (config('pages.features.admin_routes', true) && config('pages.admin.routes', true)) {
            $this->loadAdminRoutes();
        }

        $this->publishes([
            __DIR__.'/../config/pages.php' => config_path('pages.php'),
        ], 'pages-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'pages-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/pages'),
        ], 'pages-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/pages'),
        ], 'pages-translations');
    }

    private function loadAdminRoutes(): void
    {
        Route::middleware(config('pages.admin.middleware', config('pages.routes.middleware', ['web', 'auth'])))
            ->prefix(config('pages.admin.prefix', 'admin/pages'))
            ->name(config('pages.admin.name_prefix', 'admin.pages.'))
            ->group(__DIR__.'/../routes/admin.php');
    }
}
