<?php

namespace IvanBaric\Pages\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property int|null $team_id
 * @property string $uuid
 * @property string $slug
 * @property bool $is_visible
 * @property array<string, mixed>|string|null $title
 * @property array<string, mixed>|string|null $description
 * @property string|null $icon
 * @property string|null $url
 * @property string|null $button_url
 * @property int $sort_order
 * @property int $lock_version
 * @property array<string, mixed>|null $settings
 */
class SectionItem extends Model
{
    use BelongsToTenant, HasGalleries, HasLockVersion, HasUniqueSlug, HasUuid, SoftDeletes;

    public const IMAGE_COLLECTION = 'image';

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return PagesConfigResolver::sectionItemsTable();
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            $item->is_visible ??= config('pages.defaults.item_visible', true);
        });

        static::saving(function (self $item): void {
            $item->is_visible ??= config('pages.defaults.item_visible', true);
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

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(PagesModels::section());
    }

    /** @param Builder<SectionItem> $query */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /** @param Builder<SectionItem> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('created_at');
    }

    /** @param Builder<SectionItem> $query */
    #[Scope]
    protected function forSection(Builder $query, Section|string $section): void
    {
        $section instanceof Section
            ? $query->where('section_id', $section->getKey())
            : $query->whereHas('section', fn (Builder $query) => $query->where('uuid', $section));
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

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function buttonUrl(): ?string
    {
        return $this->button_url;
    }

    public function imageUrl(string $conversion = 'thumb'): ?string
    {
        return $this->galleryImageUrl(self::IMAGE_COLLECTION, $conversion);
    }

    public function hasImage(): bool
    {
        return $this->imageUrl() !== null;
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
        return $this->localized('title') ?: $this->icon ?: (string) $this->uuid;
    }
}
