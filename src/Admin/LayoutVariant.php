<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin;

final class LayoutVariant
{
    /** @param array<string, mixed> $options */
    private function __construct(
        private readonly string $key,
        private string $label = '',
        private string $description = '',
        private array $options = [],
    ) {}

    public static function add(string $key): self
    {
        return new self($key);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? $data['value'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            options: (array) ($data['options'] ?? []),
        );
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function preview(string $view): self
    {
        return $this->option('preview', $view);
    }

    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function visibleWhen(string $field, mixed $value): self
    {
        return $this->option('visible_when', [
            'field' => $field,
            'value' => $value,
        ]);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function labelValue(): string
    {
        return $this->label;
    }

    public function descriptionValue(): string
    {
        return $this->description;
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
            'value' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'options' => $this->options,
        ];
    }
}
