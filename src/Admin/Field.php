<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin;

use IvanBaric\Corexis\Rules\SafePublicUrl;
use IvanBaric\Pages\Admin\Contracts\FieldOptionsProvider;

final class Field
{
    /** @var array<int, mixed> */
    private array $rules = [];

    /** @var array<string, mixed> */
    private array $options = [];

    /**
     * @param  array<int, mixed>  $rules
     * @param  array<string, mixed>  $options
     */
    private function __construct(
        private readonly string $key,
        private readonly string $type,
        private string $label = '',
        array $rules = [],
        array $options = [],
    ) {
        $this->rules = array_values($rules);
        $this->options = $options;
    }

    public static function add(string $key, string $type = 'text'): self
    {
        return new self($key, $type);
    }

    public static function text(string $key): self
    {
        return new self($key, 'text', rules: ['nullable', 'string']);
    }

    public static function textarea(string $key): self
    {
        return new self($key, 'textarea', rules: ['nullable', 'string']);
    }

    public static function image(string $key): self
    {
        return new self($key, 'image', rules: corexis_image_upload()->rules());
    }

    public static function select(string $key): self
    {
        return new self($key, 'select');
    }

    public static function checkboxList(string $key): self
    {
        return new self($key, 'checkbox_list', rules: ['array', 'max:100']);
    }

    public static function toggle(string $key): self
    {
        return new self($key, 'boolean', rules: ['boolean']);
    }

    public static function boolean(string $key): self
    {
        return self::toggle($key);
    }

    public static function number(string $key): self
    {
        return new self($key, 'number', rules: ['nullable', 'numeric']);
    }

    public static function date(string $key): self
    {
        return new self($key, 'date', rules: ['nullable', 'date']);
    }

    public static function time(string $key): self
    {
        return new self($key, 'time', rules: ['nullable', 'date_format:H:i']);
    }

    public static function url(string $key): self
    {
        return new self($key, 'url', rules: ['nullable', 'string', new SafePublicUrl]);
    }

    public static function icon(string $key): self
    {
        return new self($key, 'icon', rules: ['nullable', 'string']);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            type: (string) ($data['type'] ?? 'text'),
            label: (string) ($data['label'] ?? ''),
            rules: (array) ($data['rules'] ?? []),
            options: (array) ($data['options'] ?? []),
        );
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function required(): self
    {
        $this->removeRules(['nullable']);
        $this->prependRule('required');

        return $this;
    }

    public function nullable(): self
    {
        $this->removeRules(['required']);
        $this->prependRule('nullable');

        return $this;
    }

    public function max(int $max): self
    {
        $this->removeRulesStartingWith('max:');
        $this->rules[] = 'max:'.$max;

        return $this->option('max', $max);
    }

    public function rows(int $rows): self
    {
        return $this->option('rows', $rows);
    }

    public function size(string $size): self
    {
        return $this->option('size', $size);
    }

    public function fit(string $fit): self
    {
        return $this->option('fit', $fit);
    }

    public function help(string $help): self
    {
        return $this->option('help', $help);
    }

    /** @param array<mixed> $options */
    public function options(array $options): self
    {
        return $this->option('options', $options);
    }

    /** @param class-string<FieldOptionsProvider> $provider */
    public function optionsProvider(string $provider): self
    {
        return $this->option('options_provider', $provider);
    }

    public function default(mixed $value): self
    {
        return $this->option('default', $value);
    }

    public function defaultFrom(string $attribute): self
    {
        return $this->option('default_from', $attribute);
    }

    public function storage(string $path): self
    {
        return $this->option('storage', $path);
    }

    public function visibleWhen(string $field, mixed $value): self
    {
        return $this->option('visible_when', [
            'field' => $field,
            'value' => $value,
        ]);
    }

    public function mediaCollection(string $collection): self
    {
        return $this->option('media_collection', $collection);
    }

    public function storeAsGalleryMedia(bool $store = true): self
    {
        return $this->option('store_as_gallery_media', $store);
    }

    /** @param array<int, mixed> $rules */
    public function rules(array $rules): self
    {
        $this->rules = array_values($rules);

        return $this;
    }

    public function rule(mixed $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function labelValue(): string
    {
        return $this->label;
    }

    /** @return array<int, mixed> */
    public function rulesValue(): array
    {
        return $this->rules;
    }

    public function optionValue(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'rules' => $this->rules,
            'options' => $this->options,
        ];
    }

    /** @param array<int, string> $rules */
    private function removeRules(array $rules): void
    {
        $this->rules = array_values(array_filter(
            $this->rules,
            static fn (mixed $rule): bool => ! is_string($rule) || ! in_array($rule, $rules, true),
        ));
    }

    private function removeRulesStartingWith(string $prefix): void
    {
        $this->rules = array_values(array_filter(
            $this->rules,
            static fn (mixed $rule): bool => ! is_string($rule) || ! str_starts_with($rule, $prefix),
        ));
    }

    private function prependRule(string $rule): void
    {
        if (in_array($rule, $this->rules, true)) {
            return;
        }

        array_unshift($this->rules, $rule);
    }
}
