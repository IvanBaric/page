<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Data;

final readonly class PublicManagementPanel
{
    /** @param array<string, mixed> $parameters */
    public function __construct(
        public string $key,
        public string $title,
        public string $icon,
        public ?string $permission = null,
        public ?string $component = null,
        public ?string $view = null,
        public ?string $event = null,
        public ?string $dataProvider = null,
        public array $parameters = [],
    ) {}

    public function translatedTitle(): string
    {
        return __($this->title);
    }
}
