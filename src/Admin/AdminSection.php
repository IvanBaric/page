<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin;

final class AdminSection
{
    /** @var array<int, Tab> */
    private array $tabs = [];

    /** @var array<string, string> */
    private array $messages = [];

    /** @var array<string, mixed> */
    private array $options = [];

    private function __construct(
        private readonly string $key,
        private string $label = '',
    ) {}

    public static function add(string $key): self
    {
        return new self($key);
    }

    public static function make(string $key): self
    {
        return self::add($key);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $section = self::add((string) ($data['key'] ?? ''))
            ->label((string) ($data['label'] ?? ''));

        $section->messages((array) ($data['messages'] ?? []));
        $section->options((array) ($data['options'] ?? []));

        $section->tabs(array_map(
            static fn (mixed $tab): Tab => $tab instanceof Tab ? $tab : Tab::fromArray((array) $tab),
            (array) ($data['tabs'] ?? []),
        ));

        return $section;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /** @param array<int, Tab> $tabs */
    public function tabs(array $tabs): self
    {
        $this->tabs = array_values($tabs);

        return $this;
    }

    public function addTab(Tab $tab): self
    {
        $this->tabs[] = $tab;

        return $this;
    }

    /** @param array<string, string> $messages */
    public function messages(array $messages): self
    {
        $this->messages = array_replace($this->messages, $messages);

        return $this;
    }

    /** @param array<string, mixed> $options */
    public function options(array $options): self
    {
        $this->options = array_replace($this->options, $options);

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

    public function labelValue(): string
    {
        return $this->label;
    }

    /** @return array<int, Tab> */
    public function tabsValue(): array
    {
        return $this->tabs;
    }

    public function tab(string $key): ?Tab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->key() === $key) {
                return $tab;
            }
        }

        return null;
    }

    public function tabOfType(string $type): ?Tab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->type() === $type) {
                return $tab;
            }
        }

        return null;
    }

    public function itemsTab(): ?Tab
    {
        return $this->tabOfType('items');
    }

    public function layoutTab(): ?Tab
    {
        return $this->tabOfType('layout');
    }

    public function settingsTab(): ?Tab
    {
        return $this->tabOfType('settings');
    }

    /** @return array<int, Tab> */
    public function settingsTabs(): array
    {
        return array_values(array_filter(
            $this->tabs,
            static fn (Tab $tab): bool => $tab->type() === 'settings',
        ));
    }

    /** @return array<string, string> */
    public function messagesValue(): array
    {
        return $this->messages;
    }

    public function message(string $key, ?string $default = null): ?string
    {
        return $this->messages[$key] ?? $default;
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
            'tabs' => array_map(static fn (Tab $tab): array => $tab->toArray(), $this->tabs),
            'messages' => $this->messages,
            'options' => $this->options,
        ];
    }
}
