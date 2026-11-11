<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'active'];
    protected $casts = ['active' => 'boolean'];
}
