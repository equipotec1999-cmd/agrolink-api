<?php

namespace App\Http\Resources;

use App\Support\NotificationText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $d = $this->datos ?? [];
        [$heading, $body] = NotificationText::render($this->tipo, $d);

        return [
            'id' => $this->id,
            'type' => $this->tipo,
            'title' => $heading,
            'body' => $body,
            'data' => $d,
            'read_at' => $this->leido_en,
            'created_at' => $this->creado_en,
        ];
    }
}
