<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'schema_signature',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
