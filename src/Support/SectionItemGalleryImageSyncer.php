<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use IvanBaric\Pages\Models\SectionItem;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class SectionItemGalleryImageSyncer
{
    public function sync(SectionItem $item, mixed $upload, bool $removeImage, string $collection = 'image', ?string $title = null): void
    {
        if (! method_exists($item, 'gallery')) {
            return;
        }

        $gallery = $item->gallery($collection);

        if ($removeImage && ! $upload instanceof TemporaryUploadedFile) {
            if ($gallery && method_exists($gallery, 'clearMediaCollection')) {
                $gallery->clearMediaCollection($collection);
                $gallery->delete();
            }

            return;
        }

        if (! $upload instanceof TemporaryUploadedFile || ! method_exists($item, 'getOrCreateGallery')) {
            return;
        }

        $title = $this->imageTitle($item, $title);
        $gallery = $item->getOrCreateGallery($collection, ['title' => $title]);
        $gallery->clearMediaCollection($collection);

        $media = $gallery
            ->addMedia($upload->getRealPath())
            ->usingFileName($upload->hashName())
            ->usingName(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: $upload->hashName())
            ->withCustomProperties([
                'alt' => $title,
                'title' => $title,
                'caption' => '',
                'description' => '',
                'credit' => '',
                'source_url' => '',
                'license' => '',
                'is_decorative' => false,
            ])
            ->toMediaCollection($collection);

        $gallery->forceFill([
            'title' => $title,
            'featured_media_id' => $media->id,
        ])->save();
    }

    private function imageTitle(SectionItem $item, ?string $title): string
    {
        if (filled($title)) {
            return (string) $title;
        }

        $localizedTitle = method_exists($item, 'localized') ? $item->localized('title') : null;

        return filled($localizedTitle) ? (string) $localizedTitle : __('Slika stavke');
    }
}
