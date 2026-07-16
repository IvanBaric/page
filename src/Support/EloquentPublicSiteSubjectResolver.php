<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use IvanBaric\Pages\Contracts\PublicSiteSubjectResolver;
use IvanBaric\Pages\Data\PublicSiteSubject;

final class EloquentPublicSiteSubjectResolver implements PublicSiteSubjectResolver
{
    public function resolve(Request $request, string $slug): ?PublicSiteSubject
    {
        $modelClass = config('pages.public_site.subject.model');

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $subject = $this->subjectFromRequest($request, $modelClass, $slug)
            ?? $this->subjectFromDatabase($modelClass, $slug);

        if (! $subject || ! $this->isActive($subject)) {
            return null;
        }

        $relationships = array_values(array_filter(
            (array) config('pages.public_site.subject.eager_load', []),
            static fn (mixed $relationship): bool => is_string($relationship) && $relationship !== '',
        ));

        if ($relationships !== []) {
            $subject->loadMissing($relationships);
        }

        $tenantColumn = (string) config('pages.public_site.subject.tenant_column', 'team_id');
        $tenantId = $subject->getAttribute($tenantColumn);

        if (! is_numeric($tenantId)) {
            return null;
        }

        return new PublicSiteSubject($subject, (int) $tenantId, $slug);
    }

    /** @param class-string<Model> $modelClass */
    private function subjectFromRequest(Request $request, string $modelClass, string $slug): ?Model
    {
        $attribute = config('pages.public_site.subject.request_attribute');

        if (! is_string($attribute) || $attribute === '') {
            return null;
        }

        $subject = $request->attributes->get($attribute);
        $slugColumn = (string) config('pages.public_site.subject.slug_column', 'slug');

        return $subject instanceof $modelClass
            && hash_equals((string) $subject->getAttribute($slugColumn), $slug)
                ? $subject
                : null;
    }

    /** @param class-string<Model> $modelClass */
    private function subjectFromDatabase(string $modelClass, string $slug): ?Model
    {
        $slugColumn = (string) config('pages.public_site.subject.slug_column', 'slug');

        return $modelClass::query()->where($slugColumn, $slug)->first();
    }

    private function isActive(Model $subject): bool
    {
        $activeColumn = config('pages.public_site.subject.active_column', 'is_active');

        if (! is_string($activeColumn) || $activeColumn === '') {
            return true;
        }

        return (bool) $subject->getAttribute($activeColumn)
            === (bool) config('pages.public_site.subject.active_value', true);
    }
}
