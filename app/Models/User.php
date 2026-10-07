<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    protected $table = 'usuarios';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'contrasena',
    ];

    protected $hidden = [
        'contrasena',
        'token_recordar',
        'secreto_dos_factores',
        'codigos_recuperacion_dos_factores',
    ];

    // Columnas de autenticación en español (Laravel asume password / remember_token).
    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function getRememberTokenName(): string
    {
        return 'token_recordar';
    }

    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'dos_factores_confirmado_en' => 'datetime',
            'contrasena' => 'hashed',
        ];
    }

    /** Permisos que convierten a una cuenta en "administrativa" y exigen 2FA. */
    private const ADMIN_PERMISSIONS = ['manage users', 'manage catalog', 'manage compliance rules', 'view audit logs'];

    public function needsTwoFactor(): bool
    {
        foreach (self::ADMIN_PERMISSIONS as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->dos_factores_confirmado_en !== null;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'usuario_id');
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class, 'usuario_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'usuario_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'usuario_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'usuario_id');
    }

    public function followedSellers(): HasMany
    {
        return $this->hasMany(FollowedSeller::class, 'usuario_id');
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class, 'usuario_id');
    }

    public function conversationsAsBuyer(): HasMany
    {
        return $this->hasMany(Conversation::class, 'comprador_id');
    }

    public function conversationsAsSeller(): HasMany
    {
        return $this->hasMany(Conversation::class, 'vendedor_id');
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reportante_id');
    }
}
