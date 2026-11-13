<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLES = [
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'member' => 'Membro',
    ];

    protected $fillable = ['name', 'email', 'password', 'is_admin'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_admin' => 'boolean', 'password' => 'hashed'];

    public function role(): string
    {
        return $this->is_admin ? 'admin' : ($this->team_role === 'manager' ? 'manager' : 'member');
    }

    public function assignRole(string $role): void
    {
        $this->is_admin = $role === 'admin';
        $this->team_role = $role === 'manager' ? 'manager' : 'member';
    }

    public function hasAreaAccess(string $area): bool
    {
        return match ($area) {
            'events' => true,
            'finance' => $this->role() !== 'member',
            'documents', 'templates' => (bool) $this->is_admin,
            default => false,
        };
    }
}
