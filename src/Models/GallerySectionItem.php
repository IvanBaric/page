<?php

namespace IvanBaric\Pages\Models;

use IvanBaric\Gallery\Concerns\HasGalleries;

class GallerySectionItem extends SectionItem
{
    use HasGalleries;

    public const IMAGE_COLLECTION = 'image';

    public function imageUrl(string $conversion = 'thumb'): ?string
    {
        return $this->galleryImageUrl(self::IMAGE_COLLECTION, $conversion);
    }

    public function getImageAttribute(mixed $value): ?string
    {
        return $this->imageUrl();
    }

    public function setImageAttribute(mixed $value): void
    {
        unset($this->attributes['image']);
    }

    public function hasImage(): bool
    {
        return $this->imageUrl() !== null;
    }
}
