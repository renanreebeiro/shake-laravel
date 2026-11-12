<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShakeEvent extends Model
{
    public const STATUSES = [
        'planning' => 'Em planejamento',
        'confirmed' => 'Confirmado',
        'completed' => 'Realizado',
        'cancelled' => 'Cancelado',
    ];

    public const MASCOT = [
        'pending' => 'A definir',
        'yes' => 'Confirmado',
        'no' => 'Sem presença',
    ];

    protected $fillable = [
        'title', 'event_date', 'starts_at', 'ends_at', 'address', 'quantity',
        'quantity_unit', 'mascot', 'status', 'responsible', 'notes',
    ];

    protected $casts = ['event_date' => 'immutable_date'];

    public function schedule(): string
    {
        $start = substr($this->starts_at, 0, 5);

        return $this->ends_at ? $start.' às '.substr($this->ends_at, 0, 5) : $start;
    }
}
