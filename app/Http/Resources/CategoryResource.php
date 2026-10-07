<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nombre,
            'slug' => $this->slug,
            'color_key' => $this->clave_color,
            'icon' => $this->icono,
            'product_types' => ProductTypeResource::collection($this->whenLoaded('productTypes')),
        ];
    }
}
