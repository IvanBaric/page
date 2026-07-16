<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

final class YouTubeVideo
{
    /** @return array{video_id: string, embed_url: string, thumbnail_url: string, youtube_url: string}|null */
    public static function fromUrl(?string $url): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $videoId = self::videoId($url);

        if ($videoId === null) {
            return null;
        }

        return [
            'video_id' => $videoId,
            'embed_url' => 'https://www.youtube.com/embed/'.$videoId,
            'thumbnail_url' => 'https://img.youtube.com/vi/'.$videoId.'/hqdefault.jpg',
            'youtube_url' => $url,
        ];
    }

    public static function videoId(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $query = (string) ($parts['query'] ?? '');

        if (self::matchesHost($host, 'youtu.be')) {
            return self::normalizeVideoId(strtok($path, '/') ?: $path);
        }

        if (self::matchesHost($host, 'youtube.com')) {
            if ($query !== '') {
                parse_str($query, $queryParameters);

                if (isset($queryParameters['v']) && is_string($queryParameters['v'])) {
                    return self::normalizeVideoId($queryParameters['v']);
                }
            }

            $segments = array_values(array_filter(explode('/', $path)));
            $knownPrefixes = ['embed', 'shorts', 'live', 'v'];

            if (isset($segments[0], $segments[1]) && in_array($segments[0], $knownPrefixes, true)) {
                return self::normalizeVideoId($segments[1]);
            }
        }

        return null;
    }

    private static function matchesHost(string $host, string $domain): bool
    {
        return $host === $domain || str_ends_with($host, '.'.$domain);
    }

    private static function normalizeVideoId(string $value): ?string
    {
        $value = trim($value);

        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $value) === 1 ? $value : null;
    }
}
