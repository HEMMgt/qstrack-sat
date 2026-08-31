<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Eloquent no relee los valores por omisión de la base tras un insert, así
     * que un usuario recién creado quedaría con role e is_active en null.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'operador',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Credenciales SAT asignadas. La tabla pivote tiene unique(user_id), así que
     * a lo sumo hay una; `satCredential()` es la forma de acceder a ella.
     */
    public function satCredentials(): BelongsToMany
    {
        return $this->belongsToMany(SatCredential::class, 'sat_credential_user')
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    public function satCredential(): ?SatCredential
    {
        return $this->satCredentials()->first();
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Autorización basada en el rol. No la consultes directamente desde
     * controladores ni vistas: usa Gate/`can`, para que el día que los permisos
     * dejen de derivarse solo del rol no haya que tocar nada más que esto.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->role instanceof Role
            && $this->role->hasPermission($permission);
    }
}
