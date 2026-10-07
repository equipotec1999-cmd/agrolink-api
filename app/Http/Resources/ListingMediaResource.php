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
            'type' => $this->tipo,
            // La BD solo guarda la ruta (Fase 1 §16); aquí se resuelve a URL firmada/pública
            // del disco configurado (R2/S3), nunca se sirve el binario desde Laravel.
            'url' => Storage::disk(config('filesystems.default'))->url($this->ruta_almacenamiento),
            'position' => $this->posicion,
            'width' => $this->ancho,
            'height' => $this->alto,
        ];
    }
}
