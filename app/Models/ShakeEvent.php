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
        'quantity_unit', 'mascot', 'status', 'responsible', 'notes', 'all_day', 'ends_on',
    ];

    protected $casts = ['event_date' => 'immutable_date', 'ends_on' => 'immutable_date', 'all_day' => 'boolean'];

    public function startLabel(): string
    {
        return $this->all_day ? 'Dia inteiro' : substr($this->starts_at, 0, 5);
    }

    public function schedule(): string
    {
        $start = $this->startLabel();
        $end = $this->ends_on?->gt($this->event_date) ? 'até '.$this->ends_on->format('d/m/Y') : '';
        if ($this->all_day) {
            return trim($start.' '.$end);
        }

        return $this->ends_at ? trim($start.' às '.substr($this->ends_at, 0, 5).' '.$end) : $start;
    }
}
