<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nombre,
            'lastname' => $this->apellidos,
            // Nombre completo listo para mostrar; evita que la app concatene.
            'full_name' => trim(($this->nombre ?? '').' '.($this->apellidos ?? '')),
            'email' => $this->correo,
            'phone' => $this->telefono,
            'email_verified' => $this->correo_verificado_en !== null,
            'profile' => [
                'avatar_path' => $this->profile?->ruta_avatar,
                'avatar_url' => $this->profile?->ruta_avatar
                    ? \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->profile->ruta_avatar)
                    : null,
                'bio' => $this->profile?->biografia,
                'state' => $this->profile?->estado,
                'municipality' => $this->profile?->municipio,
            ],
            'seller_profile' => $this->when($this->sellerProfile, fn () => [
                'business_name' => $this->sellerProfile->nombre_negocio,
                'is_verified' => $this->sellerProfile->verificado,
                'completed_operations' => $this->sellerProfile->operaciones_completadas,
                'rating_accuracy' => $this->sellerProfile->calificacion_exactitud,
                'rating_fulfillment' => $this->sellerProfile->calificacion_cumplimiento,
                'rating_communication' => $this->sellerProfile->calificacion_comunicacion,
            ]),
            // La app muestra la sección de moderación solo si hay algún permiso de moderación
            // (se comprueba por permiso, nunca por nombre de rol).
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            // Cuenta administrativa: la verificación es obligatoria (y no se puede desactivar).
            'two_factor_required' => $this->needsTwoFactor(),
            'can_manage_rules' => $this->can('manage compliance rules'),
            'seller_verified' => (bool) $this->sellerProfile?->verificado,
            'can_review_documents' => $this->can('moderate documents'),
            'can_moderate' => $this->can('moderate listings') || $this->can('resolve reports'),
            'created_at' => $this->creado_en,
        ];
    }
}
