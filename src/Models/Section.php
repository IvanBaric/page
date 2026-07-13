<?php

namespace IvanBaric\Pages\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use IvanBaric\Corexis\Concerns\BelongsToTenant;
use IvanBaric\Corexis\Concerns\HasLockVersion;
use IvanBaric\Corexis\Concerns\HasUniqueSlug;
use IvanBaric\Corexis\Concerns\HasUuid;
use IvanBaric\Gallery\Concerns\HasGalleries;
use IvanBaric\Pages\Support\PagesConfigResolver;
use IvanBaric\Pages\Support\PagesModels;

/**
 * @property int $id
 * @property int $page_id
 * @property int|null $team_id
 * @property string $uuid
 * @property string $slug
 * @property string $type
 * @property bool $is_visible
 * @property array<string, mixed>|string|null $title
 * @property array<string, mixed>|string|null $subtitle
 * @property array<string, mixed>|string|null $description
 * @property int $sort_order
 * @property int $lock_version
 * @property array<string, mixed>|null $settings
 */
class Section extends Model
{
    use BelongsToTenant, HasGalleries, HasLockVersion, HasUniqueSlug, HasUuid, SoftDeletes;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return PagesConfigResolver::sectionsTable();
    }

    protected static function booted(): void
    {
        static::creating(function (self $section): void {
            $section->is_visible ??= config('pages.defaults.section_visible', true);
        });

        static::saving(function (self $section): void {
            $section->is_visible ??= config('pages.defaults.section_visible', true);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'description' => 'array',
            'content' => 'array',
            'button_text' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(PagesModels::page());
    }

    /** @return HasMany<SectionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PagesModels::sectionItem());
    }

    /** @return HasMany<SectionItem, $this> */
    public function visibleItems(): HasMany
    {
        return $this->items()->visible()->ordered();
    }

    /** @return HasMany<SectionItem, $this> */
    public function orderedItems(): HasMany
    {
        return $this->items()->ordered();
    }

    /** @param Builder<Section> $query */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /** @param Builder<Section> $query */
    public function scopeType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /** @param Builder<Section> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('created_at');
    }

    /** @param Builder<Section> $query */
    #[Scope]
    protected function forPage(Builder $query, Page|string $page): void
    {
        $page instanceof Page
            ? $query->where('page_id', $page->getKey())
            : $query->whereHas('page', fn (Builder $query) => $query->where('uuid', $page));
    }

    public function isVisible(): bool
    {
        return $this->is_visible;
    }

    public function show(): bool
    {
        return $this->forceFill(['is_visible' => true])->save();
    }

    public function hide(): bool
    {
        return $this->forceFill(['is_visible' => false])->save();
    }

    public function archive(): bool
    {
        $this->forceFill(['is_visible' => false])->save();

        return (bool) $this->delete();
    }

    public function hasItems(): bool
    {
        return $this->items()->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addItem(array $data = []): SectionItem
    {
        return $this->items()->create(array_merge([
            'sort_order' => $this->items()->max('sort_order') + 1,
        ], $data));
    }

    public function moveItem(string $itemUuid, int $position): bool
    {
        $item = $this->items()->where('uuid', $itemUuid)->first();

        if (! $item) {
            return false;
        }

        $item->forceFill(['sort_order' => max(0, $position)])->save();

        return true;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
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

    public function slugSource(): string
    {
        return $this->localized('title') ?: $this->type ?: (string) $this->uuid;
    }
}
