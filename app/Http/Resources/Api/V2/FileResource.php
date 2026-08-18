<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Enums\FileType as FileTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $typeLabel = null;
    if ($this->type_id) {
      $enum      = FileTypeEnum::tryFrom((int) $this->type_id);
      $typeLabel = $enum?->getLabel() ?? $this->type?->name;
    }

    return [
      'id'                  => $this->id,
      'uid'                 => $this->uid,
      'code'                => $this->code,
      'type_id'             => $this->type_id,
      'type_label'          => $typeLabel,
      'file_name'           => $this->file_name,
      'file_path'           => $this->file_path,
      'file_size'           => $this->file_size,
      'file_size_formatted' => sizeFormat((float) ($this->file_size ?? 0)),
      'file_alias'          => $this->file_alias,
      'description'         => $this->description,
      'user_id'             => $this->user_id,
      'download_url'        => $this->download_url,
      'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'          => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at'          => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}
