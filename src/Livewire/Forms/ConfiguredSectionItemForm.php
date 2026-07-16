<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Forms;

use Illuminate\Validation\ValidationException;
use IvanBaric\Corexis\Rules\SafePublicUrl;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\YouTubeVideo;
use Livewire\Form;

final class ConfiguredSectionItemForm extends Form
{
    public string $title = '';

    public ?string $subtitle = null;

    public ?string $content = null;

    public ?string $image = null;

    public mixed $imageUpload = null;

    public bool $removeImage = false;

    public ?string $icon = null;

    public ?string $url = null;

    public ?string $youtubeUrl = null;

    public ?string $buttonLabel = null;

    public ?string $buttonUrl = null;

    public bool $visible = true;

    public int $sortOrder = 0;

    public ?string $metaValue = null;

    public ?string $metaSuffix = null;

    public ?string $metaRating = null;

    /** @var array<string, mixed> */
    public array $customData = [];

    /** @var array<int, string> */
    protected array $customFieldKeys = [];

    /** @var array<string, array<int, mixed>> */
    protected array $customRules = [];

    /** @var array<string, string> */
    protected array $customValidationAttributes = [];

    /** @var array<string, string> */
    protected array $customMessages = [];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_replace([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'imageUpload' => corexis_image_upload()->rules(),
            'removeImage' => ['boolean'],
            'icon' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
            'youtubeUrl' => ['nullable', 'url', 'max:2048'],
            'buttonLabel' => ['nullable', 'string', 'max:255'],
            'buttonUrl' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
            'visible' => ['boolean'],
            'sortOrder' => ['integer', 'min:0'],
            'metaValue' => ['nullable', 'string', 'max:255'],
            'metaSuffix' => ['nullable', 'string', 'max:255'],
            'metaRating' => ['nullable', 'string', 'max:255'],
        ], $this->customRules);
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return array_replace([
            'title' => __('naziv'),
            'subtitle' => __('podnaslov'),
            'content' => __('sadržaj'),
            'image' => __('slika'),
            'imageUpload' => __('slika'),
            'url' => __('URL'),
            'youtubeUrl' => __('YouTube URL'),
            'visible' => __('vidljivost'),
            'sortOrder' => __('redoslijed'),
        ], $this->customValidationAttributes);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_replace([
            'required' => __('Obavezno polje'),
            'date' => __('Unesite ispravan datum.'),
            'date_format' => __('Unesite vrijeme u obliku HH:MM.'),
            'customData.ends_at.after_or_equal' => __('Vrijeme završetka ne smije biti ranije od vremena početka.'),
        ], corexis_image_upload()->messages('imageUpload'), corexis_image_upload()->messages('form.imageUpload'), $this->customMessages);
    }

    /**
     * @param  array<string, array<int, mixed>>  $rules
     * @param  array<string, string>  $attributes
     * @param  array<string, string>  $messages
     */
    public function configureValidation(array $rules = [], array $attributes = [], array $messages = []): void
    {
        $this->customRules = $rules;
        $this->customValidationAttributes = $attributes;
        $this->customMessages = $messages;
    }

    /** @param array<int, string> $keys */
    public function configureCustomFields(array $keys): void
    {
        $this->customFieldKeys = array_values(array_unique(array_filter(
            $keys,
            static fn (string $key): bool => $key !== '',
        )));

        $allowedKeys = array_flip($this->customFieldKeys);
        $this->customData = array_intersect_key($this->customData, $allowedKeys);

        foreach ($this->customFieldKeys as $key) {
            $this->customData[$key] ??= null;
        }
    }

    public function fillFromModel(SectionItem $item): void
    {
        $this->title = $item->localized('title');
        $this->subtitle = $item->localized('subtitle') ?: null;
        $this->content = $item->localized('content') ?: $item->localized('description') ?: null;
        $this->image = $item->imageUrl();
        $this->imageUpload = null;
        $this->removeImage = false;
        $this->icon = $item->getAttribute('icon');
        $this->url = $item->getAttribute('url');
        $this->youtubeUrl = data_get($item->getAttribute('settings'), 'youtube_url') ?: $item->getAttribute('url');
        $this->buttonLabel = $item->localized('button_text') ?: null;
        $this->buttonUrl = $item->getAttribute('button_url');
        $this->visible = (bool) $item->getAttribute('is_visible');
        $this->sortOrder = (int) $item->getAttribute('sort_order');
        $this->metaValue = data_get($item->getAttribute('settings'), 'value');
        $this->metaSuffix = data_get($item->getAttribute('settings'), 'suffix');
        $this->metaRating = data_get($item->getAttribute('settings'), 'rating');
        $this->customData = [];
    }

    public function fillCustomDataFromModel(SectionItem $item): void
    {
        $settings = (array) $item->getAttribute('settings');

        foreach ($this->customFieldKeys as $key) {
            $this->customData[$key] = data_get($settings, $key);
        }
    }

    public function resetForSection(Section $section): void
    {
        $this->title = '';
        $this->subtitle = null;
        $this->content = null;
        $this->image = null;
        $this->imageUpload = null;
        $this->removeImage = false;
        $this->icon = null;
        $this->url = null;
        $this->youtubeUrl = null;
        $this->buttonLabel = null;
        $this->buttonUrl = null;
        $this->visible = true;
        $this->sortOrder = ((int) $section->items()->max('sort_order')) + 1;
        $this->metaValue = null;
        $this->metaSuffix = null;
        $this->metaRating = null;
        $this->customData = [];
        $this->resetValidation();
    }

    public function removeImage(): void
    {
        $this->imageUpload = null;
        $this->image = null;
        $this->removeImage = true;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validate(messages: $this->messages(), attributes: $this->validationAttributes());
        $this->validateCustomTimeRange($validated);
        $locale = $this->locale();
        $youtubeUrl = trim((string) ($validated['youtubeUrl'] ?? ''));
        $youtubeData = null;

        if ($youtubeUrl !== '') {
            $youtubeData = YouTubeVideo::fromUrl($youtubeUrl);

            if ($youtubeData === null) {
                throw ValidationException::withMessages([
                    'youtubeUrl' => __('Unesite ispravan YouTube link.'),
                ]);
            }
        }

        $settings = array_filter([
            'value' => $validated['metaValue'],
            'suffix' => $validated['metaSuffix'],
            'rating' => $validated['metaRating'],
        ], static fn ($value): bool => filled($value));

        foreach ($this->customFieldKeys as $key) {
            $value = data_get($validated, 'customData.'.$key);
            $value = is_string($value) ? trim($value) : $value;

            if (filled($value)) {
                data_set($settings, $key, $value);
            }
        }

        if ($youtubeData !== null) {
            $settings = array_replace($settings, [
                'youtube_url' => $youtubeData['youtube_url'],
                'video_id' => $youtubeData['video_id'],
                'embed_url' => $youtubeData['embed_url'],
                'thumbnail_url' => $youtubeData['thumbnail_url'],
            ]);
        }

        return [
            'title' => [''.$locale => $validated['title']],
            'subtitle' => filled($validated['subtitle']) ? [''.$locale => $validated['subtitle']] : null,
            'content' => filled($validated['content']) ? [''.$locale => $validated['content']] : null,
            'description' => filled($validated['content']) ? [''.$locale => $validated['content']] : null,
            'icon' => $validated['icon'],
            'url' => $youtubeData !== null ? $youtubeData['youtube_url'] : $validated['url'],
            'button_text' => filled($validated['buttonLabel']) ? [''.$locale => $validated['buttonLabel']] : null,
            'button_url' => $validated['buttonUrl'],
            '_image_upload' => $validated['imageUpload'] ?? null,
            '_remove_image' => (bool) ($validated['removeImage'] ?? false),
            'settings' => $settings,
            'is_visible' => $validated['visible'],
            'sort_order' => $validated['sortOrder'],
        ];
    }

    private function locale(): string
    {
        return corexis_locale_code()
            ?: (string) config('pages.translatable.default_locale')
            ?: (string) config('app.locale', 'en');
    }

    /** @param array<string, mixed> $validated */
    private function validateCustomTimeRange(array $validated): void
    {
        $startsAt = data_get($validated, 'customData.starts_at');
        $endsAt = data_get($validated, 'customData.ends_at');

        if (! filled($startsAt) || ! filled($endsAt)) {
            return;
        }

        if ((string) $endsAt >= (string) $startsAt) {
            return;
        }

        throw ValidationException::withMessages([
            'customData.ends_at' => __('Vrijeme završetka ne smije biti ranije od vremena početka.'),
        ]);
    }
}
