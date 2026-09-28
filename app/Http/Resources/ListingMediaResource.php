<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ListingMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            // La BD solo guarda la ruta (Fase 1 §16); aquí se resuelve a URL firmada/pública
            // del disco configurado (R2/S3), nunca se sirve el binario desde Laravel.
            'url' => Storage::disk(config('filesystems.default'))->url($this->storage_path),
            'position' => $this->position,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
