<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShakeEvent extends Model
{
    use SoftDeletes;

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
        'title', 'event_date', 'starts_at', 'ends_at', 'address', 'maps_url', 'maps_embed_url', 'maps_place_id', 'quantity',
        'quantity_unit', 'mascot', 'status', 'responsible', 'notes', 'all_day', 'ends_on',
    ];

    protected $casts = ['event_date' => 'immutable_date', 'ends_on' => 'immutable_date', 'all_day' => 'boolean'];

    public function coverage(): HasOne
    {
        return $this->hasOne(EventCoverage::class);
    }

    public function coverageDeliveries(): HasMany
    {
        return $this->hasMany(EventCoverageDelivery::class);
    }

    public function mapsLink(): ?string
    {
        if ($this->maps_place_id) {
            return 'https://www.google.com/maps/search/?'.http_build_query([
                'api' => 1, 'query' => $this->address, 'query_place_id' => $this->maps_place_id,
            ]);
        }

        return $this->maps_url ?: ($this->address
            ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->address)
            : null);
    }

    public function mapsPreview(): ?string
    {
        if ($this->maps_place_id && config('shake-maps.browser_key')) {
            return 'https://www.google.com/maps/embed/v1/place?'.http_build_query([
                'key' => config('shake-maps.browser_key'), 'q' => 'place_id:'.$this->maps_place_id,
            ]);
        }

        return $this->maps_embed_url;
    }

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
