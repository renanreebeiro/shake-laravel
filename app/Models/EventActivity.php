<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventActivity extends Model
{
    public const ACTIONS = [
        'contract_issued' => 'Contrato gerado',
        'receipt_issued' => 'Recibo gerado',
        'payment_recorded' => 'Recebimento registrado',
        'created' => 'Evento criado',
        'edited' => 'Ficha editada',
        'status' => 'Status alterado',
        'deleted' => 'Evento excluído',
        'coverage_retry' => 'Nova tentativa de cobertura solicitada',
        'imported' => 'Evento importado',
        'calendar_edited' => 'Alteração sincronizada',
        'calendar_cancelled' => 'Evento cancelado ou removido no Google',
    ];

    public $timestamps = false;
    protected $fillable = ['action', 'source', 'actor_email', 'actor_name', 'previous_status', 'new_status', 'occurred_at'];
    protected $casts = ['occurred_at' => 'immutable_datetime'];
}
