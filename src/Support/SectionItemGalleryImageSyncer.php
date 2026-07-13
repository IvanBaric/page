<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use IvanBaric\Gallery\Support\OptimizedMediaUpload;
use IvanBaric\Pages\Models\SectionItem;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class SectionItemGalleryImageSyncer
{
    public function sync(SectionItem $item, mixed $upload, bool $removeImage, string $collection = 'image', ?string $title = null): void
    {
        $gallery = $item->gallery($collection);

        if ($removeImage && ! $upload instanceof TemporaryUploadedFile) {
            if ($gallery) {
                $gallery->clearMediaCollection($collection);
                $gallery->delete();
            }

            return;
        }

        if (! $upload instanceof TemporaryUploadedFile) {
            return;
        }

        $title = $this->imageTitle($item, $title);
        $gallery = $item->getOrCreateGallery($collection, ['title' => $title]);
        $gallery->clearMediaCollection($collection);

        $media = app(OptimizedMediaUpload::class)
            ->addUploadToGallery($gallery, $upload, $collection, pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: $upload->hashName(), [
                'alt' => $title,
                'title' => $title,
                'caption' => '',
                'description' => '',
                'credit' => '',
                'source_url' => '',
                'license' => '',
                'is_decorative' => false,
            ]);

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

        $localizedTitle = $item->localized('title');

        return filled($localizedTitle) ? (string) $localizedTitle : __('Slika stavke');
    }
}
