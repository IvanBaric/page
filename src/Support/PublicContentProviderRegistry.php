<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use IvanBaric\Pages\Contracts\PublicContentProvider;
use IvanBaric\Pages\Models\Page;

final readonly class PublicContentProviderRegistry
{
    public function __construct(private Container $container) {}

    public function forPage(Page $page): ?PublicContentProvider
    {
        $providers = config('pages.public_site.content_providers', []);

        if (! is_array($providers)) {
            throw new InvalidArgumentException('Public content providers must be configured as an array.');
        }

        foreach ($this->pageKeys($page) as $key) {
            $provider = $providers[$key] ?? null;

            if ($provider === null) {
                continue;
            }

            if (! is_string($provider) || $provider === '') {
                throw new InvalidArgumentException("Public content provider [{$key}] must be a class name.");
            }

            $resolved = $this->container->make($provider);

            if (! $resolved instanceof PublicContentProvider) {
                throw new InvalidArgumentException(
                    "Public content provider [{$provider}] must implement ".PublicContentProvider::class.'.',
                );
            }

            return $resolved;
        }

        return null;
    }

    /** @return array<int, string> */
    private function pageKeys(Page $page): array
    {
        return array_values(array_unique(array_filter([
            trim((string) $page->getAttribute('page_key')),
            trim((string) $page->slug),
        ], static fn (string $key): bool => $key !== '')));
    }
}
