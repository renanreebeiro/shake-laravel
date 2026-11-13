<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCoverageDelivery extends Model
{
    public const STATUSES = [
        'sending' => 'Envio a verificar', 'sent' => 'Enviado',
        'failed' => 'Falhou', 'uncertain' => 'Envio a verificar',
    ];

    protected $fillable = ['fingerprint', 'professional', 'channel', 'status', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];
}
