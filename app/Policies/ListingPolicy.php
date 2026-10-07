<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Autorización por dueño del recurso; el RBAC por permisos (spatie) cubre aparte
     * las acciones de staff/moderación (p.ej. "moderate listings"), que no pasan por
     * esta policy sino por un Gate/permiso explícito en el controller de moderación
     * (Fase 6). Regla del proyecto §7: nunca `if user.role == 'admin'` aquí.
     */
    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->usuario_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->usuario_id;
    }

    public function publish(User $user, Listing $listing): bool
    {
        return $user->id === $listing->usuario_id;
    }
}
