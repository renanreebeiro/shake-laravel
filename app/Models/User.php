<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLES = [
        'programmer' => 'Programador',
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'member' => 'Membro',
    ];

    public static function teamRoles(): array
    {
        return array_diff_key(self::ROLES, ['programmer' => true]);
    }

    protected $fillable = ['name', 'email', 'password', 'is_admin'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_admin' => 'boolean', 'password' => 'hashed'];

    public function role(): string
    {
        return match (true) {
            $this->team_role === 'programmer' => 'programmer',
            $this->is_admin => 'admin',
            $this->team_role === 'manager' => 'manager',
            default => 'member',
        };
    }

    public function assignRole(string $role): void
    {
        $this->is_admin = in_array($role, ['admin', 'programmer'], true);
        $this->team_role = in_array($role, ['manager', 'programmer'], true) ? $role : 'member';
    }

    public function hasAreaAccess(string $area): bool
    {
        return match ($area) {
            'events' => true,
            'finance' => in_array($this->role(), ['programmer', 'admin', 'manager'], true),
            'documents' => $this->is_admin,
            'templates' => $this->role() === 'programmer',
            default => false,
        };
    }
}
