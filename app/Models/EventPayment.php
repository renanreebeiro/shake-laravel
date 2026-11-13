<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventPayment extends Model
{
    public const METHODS = ['PIX', 'Transferência', 'Dinheiro', 'Cartão'];
    public $timestamps = false;
    protected $fillable = ['request_key', 'amount', 'received_on', 'method'];
    protected $casts = ['amount' => 'integer', 'received_on' => 'immutable_date'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(ShakeEvent::class, 'shake_event_id')->withTrashed();
    }

    public function document(): HasOne
    {
        return $this->hasOne(EventDocument::class);
    }
}
