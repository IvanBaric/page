<?php

namespace IvanBaric\Pages\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use IvanBaric\Pages\Support\SlugGenerator;
use IvanBaric\Pages\Support\TeamResolver;

class Section extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('pages.tables.sections', 'sections');
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
        static::creating(function (self $section): void {
            $section->team_id ??= $section->page?->team_id ?? app(TeamResolver::class)->resolve();
            $section->is_visible ??= config('pages.defaults.section_visible', true);
        });

        static::saving(function (self $section): void {
            if ($section->page && $section->team_id !== $section->page->team_id) {
                $section->team_id = $section->page->team_id;
            }

            if (! $section->slug || $section->isDirty('title') || $section->isDirty('type')) {
                $section->slug = app(SlugGenerator::class)->generate($section, $section->slugSource());
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
            'subtitle' => 'array',
            'description' => 'array',
            'content' => 'array',
            'button_text' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(config('pages.models.page', Page::class));
    }

    public function items(): HasMany
    {
        return $this->hasMany(config('pages.models.section_item', SectionItem::class));
    }

    public function visibleItems(): HasMany
    {
        return $this->items()->visible()->ordered();
    }

    public function orderedItems(): HasMany
    {
        return $this->items()->ordered();
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    #[Scope]
    protected function type(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('created_at');
    }

    #[Scope]
    protected function forPage(Builder $query, Page|string $page): void
    {
        $page instanceof Page
            ? $query->where('page_id', $page->getKey())
            : $query->whereHas('page', fn (Builder $query) => $query->where('uuid', $page));
    }

    #[Scope]
    protected function forTeam(Builder $query, ?int $teamId): void
    {
        $teamId === null ? $query->whereNull('team_id') : $query->where('team_id', $teamId);
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
            'team_id' => $this->team_id,
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

        $locale ??= app()->getLocale();
        $fallback = config('pages.translatable.default_locale') ?: config('app.fallback_locale', 'en');

        return (string) ($value[$locale] ?? $value[$fallback] ?? reset($value) ?: '');
    }

    private function slugSource(): string
    {
        return $this->localized('title') ?: $this->type ?: (string) $this->uuid;
    }
}
