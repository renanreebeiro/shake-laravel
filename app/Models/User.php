<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const AREAS = [
        'documents' => 'Documentos',
        'events' => 'Eventos',
        'templates' => 'Modelos',
    ];

    private const DEFAULT_AREAS = ['documents', 'events'];

    protected $fillable = ['name', 'email', 'password', 'is_admin'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_admin' => 'boolean', 'password' => 'hashed', 'area_access' => 'array'];

    public function hasAreaAccess(string $area): bool
    {
        return isset(self::AREAS[$area])
            && ($this->is_admin || in_array($area, $this->area_access ?? self::DEFAULT_AREAS, true));
    }
}
