<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDocument extends Model
{
    public $timestamps = false;
    protected $fillable = ['event_payment_id', 'request_key', 'kind', 'title', 'body', 'receipt_number', 'received_amount', 'received_on', 'payment_method', 'generated_at'];
    protected $casts = ['received_amount' => 'integer', 'received_on' => 'immutable_date', 'generated_at' => 'immutable_datetime', 'body' => 'encrypted'];
}
