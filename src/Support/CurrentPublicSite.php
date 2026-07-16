<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

final readonly class CurrentPublicSite
{
    public function subject(): ?Model
    {
        $tenantId = corexis_tenant_id();
        $modelClass = config('pages.public_site.subject.model');

        if (! is_numeric($tenantId) || ! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $tenantColumn = (string) config('pages.public_site.subject.tenant_column', 'team_id');
        $activeColumn = config('pages.public_site.subject.active_column', 'is_active');
        $query = $modelClass::query()->where($tenantColumn, (int) $tenantId);

        if (is_string($activeColumn) && $activeColumn !== '') {
            $query->where($activeColumn, (bool) config('pages.public_site.subject.active_value', true));
        }

        return $query->first();
    }

    public function url(): ?string
    {
        $subject = $this->subject();
        $routeName = (string) config(
            'pages.public_site.route.name',
            config('pages.admin_index.public_route.name', ''),
        );
        $slugColumn = (string) config('pages.public_site.subject.slug_column', 'slug');
        $slug = $subject?->getAttribute($slugColumn);

        if (! is_string($slug) || $slug === '' || $routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        return route($routeName, [
            (string) config('pages.public_site.route.subject_parameter', 'subjectSlug') => $slug,
        ]);
    }
}
