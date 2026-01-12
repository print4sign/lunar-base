<?php

namespace Lunar\Base;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InspirationMediaDefinitions implements MediaDefinitionsInterface
{
    public function registerMediaConversions(HasMedia $model, ?Media $media = null): void
    {
        // Thumbnail for admin/listings
        $model->addMediaConversion('small')
            ->fit(Fit::Crop, 300, 300)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        // Medium for grid displays
        $model->addMediaConversion('medium')
            ->fit(Fit::Crop, 600, 600)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        // Large for lightbox/detail view
        $model->addMediaConversion('large')
            ->fit(Fit::Contain, 1200, 1200)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        // Gallery grid (square crop)
        $model->addMediaConversion('gallery')
            ->fit(Fit::Crop, 400, 400)
            ->sharpen(10)
            ->keepOriginalImageFormat();
    }

    public function registerMediaCollections(HasMedia $model): void
    {
        $model->addMediaCollection('inspiration_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getMediaCollectionTitles(): array
    {
        return [
            'inspiration_photos' => __('lunar::inspiration.media.photos'),
        ];
    }

    public function getMediaCollectionDescriptions(): array
    {
        return [
            'inspiration_photos' => __('lunar::inspiration.media.photos_description'),
        ];
    }
}
