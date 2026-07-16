<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin;

final class Tab
{
    /** @var array<int, Field> */
    private array $fields = [];

    /** @var array<int, Action> */
    private array $actions = [];

    /** @var array<int, LayoutVariant> */
    private array $variants = [];

    /** @var array<string, mixed> */
    private array $options = [];

    private function __construct(
        private readonly string $key,
        private readonly string $type,
        private string $label = '',
    ) {}

    public static function add(string $key, string $label, string $type = 'custom'): self
    {
        return new self($key, $type, $label);
    }

    public static function items(string $label = 'Content', string $key = 'content'): self
    {
        return (new self($key, 'items', $label))
            ->actions([Action::edit(), Action::delete()])
            ->modalFlyout()
            ->singleColumn()
            ->hideSortOrder()
            ->showVisibility();
    }

    public static function form(string $label = 'Content', string $key = 'content'): self
    {
        return new self($key, 'form', $label);
    }

    public static function layout(string $label = 'Layout', string $key = 'design'): self
    {
        return (new self($key, 'layout', $label))
            ->storage('settings.layout_variant');
    }

    public static function settings(string $label = 'Settings', string $key = 'settings'): self
    {
        return new self($key, 'settings', $label);
    }

    public static function source(string $label = 'Content', string $key = 'content'): self
    {
        return new self($key, 'source', $label);
    }

    public static function view(string $label, string $view, string $key = 'content'): self
    {
        return (new self($key, 'view', $label))->option('view', $view);
    }

    public static function livewire(string $label, string $component, string $key = 'content'): self
    {
        return (new self($key, 'livewire', $label))->option('component', $component);
    }

    /** @param array<string, mixed> $parameters */
    public function parameters(array $parameters): self
    {
        return $this->option('parameters', $parameters);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $tab = new self(
            key: (string) ($data['key'] ?? ''),
            type: (string) ($data['type'] ?? 'custom'),
            label: (string) ($data['label'] ?? ''),
        );

        $tab->options((array) ($data['options'] ?? []));
        $tab->fields(array_map(
            static fn (mixed $field): Field => $field instanceof Field ? $field : Field::fromArray((array) $field),
            (array) ($data['fields'] ?? []),
        ));
        $tab->actions(array_map(
            static fn (mixed $action): Action => $action instanceof Action ? $action : Action::fromArray((array) $action),
            (array) ($data['actions'] ?? []),
        ));
        $tab->variants(array_map(
            static fn (mixed $variant): LayoutVariant => $variant instanceof LayoutVariant ? $variant : LayoutVariant::fromArray((array) $variant),
            (array) ($data['variants'] ?? []),
        ));

        return $tab;
    }

    public function heading(string $heading): self
    {
        return $this->option('heading', $heading);
    }

    public function description(string $description): self
    {
        return $this->option('description', $description);
    }

    public function formTitle(string $editTitle, ?string $createTitle = null): self
    {
        return $this
            ->option('edit_form_title', $editTitle)
            ->option('create_form_title', $createTitle ?? $editTitle);
    }

    public function addLabel(string $label): self
    {
        return $this->option('add_label', $label);
    }

    public function emptyText(string $text): self
    {
        return $this->option('empty_text', $text);
    }

    public function submitLabel(string $label): self
    {
        return $this->option('submit_label', $label);
    }

    public function modalDescription(string $description): self
    {
        return $this->option('modal_description', $description);
    }

    public function modalFlyout(bool $enabled = true): self
    {
        return $this->option('modal_flyout', $enabled);
    }

    public function inlineForm(bool $enabled = true, ?string $submitLabel = null): self
    {
        $this->option('inline_form', $enabled);

        if ($submitLabel !== null) {
            $this->option('inline_submit_label', $submitLabel);
        }

        return $this;
    }

    public function singleColumn(bool $enabled = true): self
    {
        return $this->option('single_column', $enabled);
    }

    public function hideSortOrder(): self
    {
        return $this->option('show_sort_order', false);
    }

    public function showSortOrder(bool $enabled = true): self
    {
        return $this->option('show_sort_order', $enabled);
    }

    public function showVisibility(bool $enabled = true): self
    {
        return $this->option('show_visibility', $enabled);
    }

    public function maxItems(int $max): self
    {
        return $this->option('max_items', max(0, $max));
    }

    public function storage(string $path): self
    {
        return $this->option('storage', $path);
    }

    public function default(mixed $value): self
    {
        return $this->option('default', $value);
    }

    public function visibleWhen(string $field, mixed $value): self
    {
        return $this->option('visible_when', [
            'field' => $field,
            'value' => $value,
        ]);
    }

    /** @param array<int, Field> $fields */
    public function fields(array $fields): self
    {
        $this->fields = array_values($fields);

        return $this;
    }

    /** @param array<int, Action> $actions */
    public function actions(array $actions): self
    {
        $this->actions = array_values($actions);

        return $this;
    }

    /** @param array<int, LayoutVariant> $variants */
    public function variants(array $variants): self
    {
        $this->variants = array_values($variants);

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

    public function type(): string
    {
        return $this->type;
    }

    public function labelValue(): string
    {
        return $this->label;
    }

    /** @return array<int, Field> */
    public function fieldsValue(): array
    {
        return $this->fields;
    }

    public function field(string $key): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->key() === $key) {
                return $field;
            }
        }

        return null;
    }

    /** @return array<int, Action> */
    public function actionsValue(): array
    {
        return $this->actions;
    }

    public function action(string $key): ?Action
    {
        foreach ($this->actions as $action) {
            if ($action->key() === $key) {
                return $action;
            }
        }

        return null;
    }

    /** @return array<int, LayoutVariant> */
    public function variantsValue(): array
    {
        return $this->variants;
    }

    /** @return array<int, string> */
    public function variantKeys(): array
    {
        return array_map(static fn (LayoutVariant $variant): string => $variant->key(), $this->variants);
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
            'fields' => array_map(static fn (Field $field): array => $field->toArray(), $this->fields),
            'actions' => array_map(static fn (Action $action): array => $action->toArray(), $this->actions),
            'variants' => array_map(static fn (LayoutVariant $variant): array => $variant->toArray(), $this->variants),
            'options' => $this->options,
        ];
    }
}
