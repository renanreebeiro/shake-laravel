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

    public const DOCUMENT_FIELDS = [
        'nome' => 'Nome do contratante', 'estado' => 'Estado civil', 'tipo_doc' => 'Tipo de documento',
        'cpf' => 'CPF ou CNPJ', 'rg' => 'RG', 'endereco' => 'Endereço do contratante',
        'evento' => 'Tipo de evento', 'hora' => 'Horário da entrega',
    ];

    protected $fillable = [
        'title', 'event_date', 'starts_at', 'ends_at', 'address', 'maps_url', 'maps_place_id', 'quantity',
        'quantity_unit', 'mascot', 'status', 'responsible', 'notes', 'all_day', 'ends_on', 'document_total', 'document_data', 'payment_due_on',
    ];

    protected $hidden = ['document_data'];

    protected $casts = ['payment_due_on' => 'immutable_date', 'document_total' => 'integer', 'document_data' => 'encrypted:array', 'event_date' => 'immutable_date', 'ends_on' => 'immutable_date', 'all_day' => 'boolean'];

    public function payments(): HasMany
    {
        return $this->hasMany(EventPayment::class);
    }

    public function canConfirm(): bool
    {
        return $this->document_total > 0
            && $this->payments()->sum('amount') >= intdiv($this->document_total * 3 + 9, 10);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EventDocument::class);
    }

    public function paymentDue()
    {
        return $this->payment_due_on ?? $this->event_date?->subDays(10);
    }

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

    public function activities(): HasMany
    {
        return $this->hasMany(EventActivity::class);
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
