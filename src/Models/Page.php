<?php

namespace IvanBaric\Pages\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use IvanBaric\Corexis\Concerns\BelongsToTenant;
use IvanBaric\Corexis\Concerns\HasLockVersion;
use IvanBaric\Corexis\Concerns\HasUniqueSlug;
use IvanBaric\Corexis\Concerns\HasUuid;
use IvanBaric\Pages\Support\PagesConfigResolver;
use IvanBaric\Pages\Support\PagesModels;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $uuid
 * @property string $slug
 * @property string|null $page_key
 * @property array<string, mixed>|string $title
 * @property array<string, mixed>|string|null $excerpt
 * @property array<string, mixed>|string|null $content
 * @property string $status
 * @property string|null $template
 * @property bool $is_home
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property int $sort_order
 * @property array<string, mixed>|null $settings
 */
class Page extends Model
{
    use BelongsToTenant, HasLockVersion, HasUniqueSlug, HasUuid, SoftDeletes;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return PagesConfigResolver::pagesTable();
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->status ??= config('pages.default_status', 'draft');
            $page->template ??= config('pages.default_template', 'classic');
        });

        static::saving(function (self $page): void {
            if (! $page->is_home) {
                return;
            }

            $query = $page->newQueryWithoutRelationships()
                ->withoutGlobalScopes()
                ->where('team_id', $page->team_id)
                ->where('is_home', true);

            if ($page->exists) {
                $query->whereKeyNot($page->getKey());
            }

            $query->update(['is_home' => false]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'content' => 'array',
            'is_home' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
            'settings' => 'array',
            'lock_version' => 'integer',
        ];
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(PagesModels::section());
    }

    /** @return HasMany<Section, $this> */
    public function visibleSections(): HasMany
    {
        return $this->sections()->visible()->ordered();
    }

    /** @return HasMany<Section, $this> */
    public function orderedSections(): HasMany
    {
        return $this->sections()->ordered();
    }

    /** @param Builder<Page> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** @param Builder<Page> $query */
    #[Scope]
    protected function home(Builder $query): void
    {
        $query->where('is_home', true);
    }

    /** @param Builder<Page> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('title')->orderByDesc('created_at');
    }

    /** @param Builder<Page> $query */
    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /** @param Builder<Page> $query */
    public function scopeTemplate(Builder $query, string $template): void
    {
        $query->where('template', $template);
    }

    public static function forSlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }

    public static function findByUuid(string $uuid): ?self
    {
        return static::query()->where('uuid', $uuid)->first();
    }

    public static function forUuid(string $uuid): ?self
    {
        return static::findByUuid($uuid);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createHome(array $data = []): self
    {
        return static::query()->create(array_merge([
            'title' => [static::currentLocaleCode() => __('pages::pages.home')],
            'is_home' => true,
        ], $data));
    }

    public function isHome(): bool
    {
        return $this->is_home;
    }

    public function slugSource(): string
    {
        return $this->localized('title');
    }

    public function markAsHome(): bool
    {
        return $this->getConnection()->transaction(function (): bool {
            $this->newQuery()
                ->where('is_home', true)
                ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
                ->update(['is_home' => false]);

            return $this->forceFill(['is_home' => true])->save();
        });
    }

    public function isPublished(): bool
    {
        return $this->is_published && $this->status === 'published' && $this->published_at !== null && $this->published_at->lte(now());
    }

    public function publish(?Carbon $publishedAt = null): bool
    {
        return $this->forceFill([
            'status' => 'published',
            'is_published' => true,
            'published_at' => $publishedAt ?? now(),
        ])->save();
    }

    public function unpublish(): bool
    {
        return $this->forceFill([
            'status' => config('pages.default_status', 'draft'),
            'is_published' => false,
            'published_at' => null,
        ])->save();
    }

    public function archive(): bool
    {
        $this->forceFill(['is_published' => false])->save();

        return (bool) $this->delete();
    }

    public function section(string $type): ?Section
    {
        return $this->sections()->type($type)->ordered()->first();
    }

    /** @return HasMany<Section, $this> */
    public function sectionsOfType(string $type): HasMany
    {
        return $this->sections()->type($type)->ordered();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addSection(string $type, array $data = []): Section
    {
        return $this->sections()->create(array_merge([
            'type' => $type,
            'sort_order' => $this->sections()->max('sort_order') + 1,
        ], $data));
    }

    public function moveSection(string $sectionUuid, int $position): bool
    {
        $section = $this->sections()->where('uuid', $sectionUuid)->first();

        if (! $section) {
            return false;
        }

        $section->forceFill(['sort_order' => max(0, $position)])->save();

        return true;
    }

    public function hideSection(string $sectionUuid): bool
    {
        return (bool) $this->sections()->where('uuid', $sectionUuid)->first()?->hide();
    }

    public function showSection(string $sectionUuid): bool
    {
        return (bool) $this->sections()->where('uuid', $sectionUuid)->first()?->show();
    }

    public function localized(string $field, ?string $locale = null): string
    {
        $value = $this->getAttribute($field);

        if (! is_array($value)) {
            return (string) $value;
        }

        $locale ??= static::currentLocaleCode();
        $fallback = config('pages.translatable.default_locale') ?: config('app.fallback_locale', 'en');

        return (string) ($value[$locale] ?? $value[$fallback] ?? reset($value) ?: '');
    }

    protected static function currentLocaleCode(): string
    {
        return corexis_locale_code() ?: config('app.locale', 'en');
    }
}
