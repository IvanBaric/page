<?php

namespace IvanBaric\Pages\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use IvanBaric\Pages\Support\SlugGenerator;
use IvanBaric\Pages\Support\TeamResolver;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $uuid
 * @property string $slug
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
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('pages.tables.pages', 'pages');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->status ??= config('pages.default_status', 'draft');
            $page->template ??= config('pages.default_template', 'classic');
            $page->team_id ??= app(TeamResolver::class)->resolve();
        });

        static::saving(function (self $page): void {
            if (! $page->slug || $page->isDirty('title')) {
                $page->slug = app(SlugGenerator::class)->generate($page, $page->localized('title'));
            }

            if ($page->is_home) {
                $page->newQuery()
                    ->forTeam($page->team_id)
                    ->where('is_home', true)
                    ->when($page->exists, fn (Builder $query) => $query->whereKeyNot($page->getKey()))
                    ->update(['is_home' => false]);
            }
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
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(config('pages.models.section', Section::class));
    }

    public function visibleSections(): HasMany
    {
        return $this->sections()->visible()->ordered();
    }

    public function orderedSections(): HasMany
    {
        return $this->sections()->ordered();
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    protected function home(Builder $query): void
    {
        $query->where('is_home', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('title')->orderByDesc('created_at');
    }

    #[Scope]
    protected function forTeam(Builder $query, ?int $teamId): void
    {
        $teamId === null ? $query->whereNull('team_id') : $query->where('team_id', $teamId);
    }

    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeTemplate(Builder $query, string $template): void
    {
        $query->where('template', $template);
    }

    public static function findByUuid(string $uuid): ?self
    {
        return static::query()->where('uuid', $uuid)->first();
    }

    public static function forUuid(string $uuid): ?self
    {
        return static::findByUuid($uuid);
    }

    public static function forSlug(string $slug, ?int $teamId = null): ?self
    {
        return static::query()->forTeam($teamId ?? app(TeamResolver::class)->resolve())->where('slug', $slug)->first();
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

    public function section(string $type): ?Section
    {
        return $this->sections()->type($type)->ordered()->first();
    }

    public function sectionsOfType(string $type): HasMany
    {
        return $this->sections()->type($type)->ordered();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addSection(string $type, array $data = []): Section
    {
        $model = config('pages.models.section', Section::class);

        return $this->sections()->create(array_merge([
            'team_id' => $this->team_id,
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

    private static function currentLocaleCode(): string
    {
        return corexis_locale_code() ?: config('app.locale', 'en');
    }
}
