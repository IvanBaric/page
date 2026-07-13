<?php

namespace IvanBaric\Pages;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Livewire\Admin\ConfiguredItemsEditor;
use IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor;
use IvanBaric\Pages\Livewire\Admin\PageArchive;
use IvanBaric\Pages\Livewire\Admin\PageForm;
use IvanBaric\Pages\Livewire\Admin\PageIndex;
use IvanBaric\Pages\Livewire\Admin\SectionItemsManager;
use IvanBaric\Pages\Livewire\Admin\SectionsManager;
use Livewire\Livewire;

final class PagesServiceProvider extends ServiceProvider
{
    /** @var array<int, string> */
    private const REPLACE_CONFIG_KEYS = [
        'admin_pages',
        'admin_section_definitions',
        'admin_sections',
        'permissions',
        'public_slug_aliases',
        'public_slugs',
        'section_editor_aliases',
        'section_editors',
        'section_types',
        'templates',
    ];

    public function register(): void
    {
        $this->mergeConfigRecursivelyFrom(__DIR__.'/../config/pages.php', 'pages');

        $this->app->singleton(AdminSectionRegistry::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pages');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'pages');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Livewire::component('pages.admin.pages.index', PageIndex::class);
        Livewire::component('pages.admin.pages.archive', PageArchive::class);
        Livewire::component('pages.admin.pages.form', PageForm::class);
        Livewire::component('pages.admin.sections.manager', SectionsManager::class);
        Livewire::component('pages.admin.section-items.manager', SectionItemsManager::class);
        Livewire::component('admin.pages.sections.configured-items-editor', ConfiguredItemsEditor::class);
        Livewire::component('admin.pages.configured-singleton-editor', ConfiguredSingletonEditor::class);

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

    private function mergeConfigRecursivelyFrom(string $path, string $key): void
    {
        $defaults = require $path;
        $config = $this->app->make(Repository::class);
        $configured = $config->get($key, []);

        $config->set($key, $this->mergeConfigValues($defaults, $configured, root: true));
    }

    /**
     * @param  array<mixed>  $defaults
     * @param  array<mixed>  $configured
     * @return array<mixed>
     */
    private function mergeConfigValues(array $defaults, array $configured, bool $root = false): array
    {
        foreach ($configured as $key => $value) {
            if ($root && is_string($key) && in_array($key, self::REPLACE_CONFIG_KEYS, true)) {
                $defaults[$key] = $value;

                continue;
            }

            if (
                is_array($value)
                && array_key_exists($key, $defaults)
                && is_array($defaults[$key])
                && ! array_is_list($value)
                && ! array_is_list($defaults[$key])
            ) {
                $defaults[$key] = $this->mergeConfigValues($defaults[$key], $value);

                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }
}
