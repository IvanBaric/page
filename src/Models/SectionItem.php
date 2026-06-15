<?php

namespace IvanBaric\Pages\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use IvanBaric\Pages\Support\SlugGenerator;
use IvanBaric\Pages\Support\TeamResolver;

class SectionItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('pages.tables.section_items', 'section_items');
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
        static::creating(function (self $item): void {
            $item->team_id ??= $item->section?->team_id ?? app(TeamResolver::class)->resolve();
            $item->is_visible ??= config('pages.defaults.item_visible', true);
        });

        static::saving(function (self $item): void {
            if ($item->section && $item->team_id !== $item->section->team_id) {
                $item->team_id = $item->section->team_id;
            }

            if (! $item->slug || $item->isDirty('title') || $item->isDirty('icon')) {
                $item->slug = app(SlugGenerator::class)->generate($item, $item->slugSource());
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(config('pages.models.section', Section::class));
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('created_at');
    }

    #[Scope]
    protected function forSection(Builder $query, Section|string $section): void
    {
        $section instanceof Section
            ? $query->where('section_id', $section->getKey())
            : $query->whereHas('section', fn (Builder $query) => $query->where('uuid', $section));
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

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function hasImage(): bool
    {
        return filled($this->image);
    }

    public function buttonUrl(): ?string
    {
        return $this->button_url;
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
        return $this->localized('title') ?: $this->icon ?: (string) $this->uuid;
    }
}
