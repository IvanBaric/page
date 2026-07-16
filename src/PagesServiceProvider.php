<?php

namespace IvanBaric\Pages;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Contracts\PublicPageViewTracker;
use IvanBaric\Pages\Contracts\PublicSiteSubjectResolver;
use IvanBaric\Pages\Http\Controllers\PublicContentController;
use IvanBaric\Pages\Http\Controllers\PublicPageController;
use IvanBaric\Pages\Livewire\Admin\ConfiguredItemsEditor;
use IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor;
use IvanBaric\Pages\Livewire\Admin\PageArchive;
use IvanBaric\Pages\Livewire\Admin\PageForm;
use IvanBaric\Pages\Livewire\Admin\PageIndex;
use IvanBaric\Pages\Livewire\Admin\SectionItemsManager;
use IvanBaric\Pages\Livewire\Admin\SectionsManager;
use IvanBaric\Pages\Livewire\PublicSite\PageStructureFlyout;
use IvanBaric\Pages\Livewire\PublicSite\PublicManagementFlyout;
use IvanBaric\Pages\Livewire\PublicSite\SectionEditorFlyout;
use IvanBaric\Pages\Livewire\PublicSite\TemplatePartEditorFlyout;
use IvanBaric\Pages\Support\EloquentPublicSiteSubjectResolver;
use IvanBaric\Pages\Support\NullPublicPageViewTracker;
use IvanBaric\Pages\Support\PublicContentProviderRegistry;
use IvanBaric\Pages\Support\PublicManagementRegistry;
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
        $this->app->singleton(PublicContentProviderRegistry::class);
        $this->app->singleton(PublicManagementRegistry::class);
        $this->app->singleton(PublicSiteSubjectResolver::class, function ($app): PublicSiteSubjectResolver {
            $resolver = config('pages.public_site.subject_resolver');

            return $app->make(is_string($resolver) && $resolver !== ''
                ? $resolver
                : EloquentPublicSiteSubjectResolver::class);
        });
        $this->app->singleton(PublicPageViewTracker::class, function ($app): PublicPageViewTracker {
            $tracker = config('pages.public_site.view_tracker');

            return $app->make(is_string($tracker) && $tracker !== ''
                ? $tracker
                : NullPublicPageViewTracker::class);
        });
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
        Livewire::component('pages.public.section-editor-flyout', SectionEditorFlyout::class);
        Livewire::component('pages.public.template-part-editor-flyout', TemplatePartEditorFlyout::class);
        Livewire::component('pages.public.page-structure-flyout', PageStructureFlyout::class);

        if (config('pages.public_management.enabled', false)) {
            Livewire::component('pages.public.management-flyout', PublicManagementFlyout::class);
        }

        if (config('pages.features.admin_routes', true) && config('pages.admin.routes', true)) {
            $this->loadAdminRoutes();
        }

        if (config('pages.public_site.enabled', false)) {
            $this->loadPublicRoutes();
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

    private function loadPublicRoutes(): void
    {
        if (config('pages.public_site.content_route.enabled', false)) {
            $this->registerPublicRoute('content_route', PublicContentController::class);
        }

        if (config('pages.public_site.route.enabled', false)) {
            $this->registerPublicRoute('route', PublicPageController::class);
        }
    }

    /** @param class-string $controller */
    private function registerPublicRoute(string $configKey, string $controller): void
    {
        $prefix = 'pages.public_site.'.$configKey;
        $defaultUri = $configKey === 'content_route'
            ? '/{subjectSlug}/{pageSlug}/{contentSlug}'
            : '/{subjectSlug}/{pageSlug?}';
        $route = Route::middleware((array) config($prefix.'.middleware', ['web']))
            ->get((string) config($prefix.'.uri', $defaultUri), $controller);
        $where = config($prefix.'.where', []);

        if (is_array($where) && $where !== []) {
            $route->where($where);
        }

        $name = config($prefix.'.name');

        if (is_string($name) && $name !== '') {
            $route->name($name);
        }
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
