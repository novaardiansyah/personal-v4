<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Models\Gallery;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageGalleries extends ManageRecords
{
  protected static string $resource = GalleryResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::ThreeExtraLarge)
        ->after(function (Gallery $record) {
          $has_optimized = $record->has_optimized;

          if ($has_optimized) {
            $optimized = uploadAndOptimize($record->file_path, 'public', 'images/gallery');

            foreach ($optimized as $key => $image) {
              if ($key === 'original') continue;

              $gallery = $record->replicate();

              $gallery->file_path     = $image;
              $gallery->has_optimized = false;
              $gallery->subject_id    = $record->id;
              $gallery->subject_type  = Gallery::class;

              $gallery->save();
            }
          }
        }),
    ];
  }
}
