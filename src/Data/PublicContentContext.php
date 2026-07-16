<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Data;

use Illuminate\Database\Eloquent\Collection;
use IvanBaric\Pages\Models\Page;

final readonly class PublicContentContext
{
    /** @param Collection<int, Page> $publicPages */
    public function __construct(
        public PublicSiteSubject $subject,
        public Page $page,
        public Collection $publicPages,
        public string $contentSlug,
    ) {}

    public function pageKey(): string
    {
        $pageKey = trim((string) $this->page->getAttribute('page_key'));

        return $pageKey !== '' ? $pageKey : trim((string) $this->page->slug);
    }
}
