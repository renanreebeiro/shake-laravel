<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageDriveConnection extends Model
{
    public $timestamps = false;
    protected $fillable = ['refresh_token', 'parent_id', 'parent_name'];
    protected $casts = ['refresh_token' => 'encrypted'];
    protected $hidden = ['refresh_token'];
}
