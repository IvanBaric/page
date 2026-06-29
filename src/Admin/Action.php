<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin;

final class Action
{
    /** @param array<string, mixed> $options */
    private function __construct(
        private readonly string $key,
        private string $label = '',
        private ?string $icon = null,
        private ?string $variant = null,
        private array $options = [],
    ) {}

    public static function add(string $key): self
    {
        return new self($key);
    }

    public static function edit(): self
    {
        return self::add('edit')
            ->label('Edit')
            ->icon('pencil');
    }

    public static function delete(): self
    {
        return self::add('delete')
            ->label('Delete')
            ->icon('trash')
            ->variant('danger');
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            icon: isset($data['icon']) ? (string) $data['icon'] : null,
            variant: isset($data['variant']) ? (string) $data['variant'] : null,
            options: (array) ($data['options'] ?? []),
        );
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function icon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function variant(?string $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    /** @param array<string, mixed> $parameters */
    public function route(string $name, array $parameters = []): self
    {
        return $this
            ->option('route', $name)
            ->option('route_parameters', $parameters);
    }

    public function url(string $url): self
    {
        return $this->option('url', $url);
    }

    public function confirmTitle(string $title): self
    {
        return $this->option('confirm_title', $title);
    }

    public function confirmText(string $text): self
    {
        return $this->option('confirm_text', $text);
    }

    public function modalTitle(string $title): self
    {
        return $this->option('modal_title', $title);
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

    public function labelValue(): string
    {
        return $this->label;
    }

    public function iconValue(): ?string
    {
        return $this->icon;
    }

    public function variantValue(): ?string
    {
        return $this->variant;
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
            'label' => $this->label,
            'icon' => $this->icon,
            'variant' => $this->variant,
            'options' => $this->options,
        ];
    }
}
