<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingAccountMapping extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'extra_config' => 'array',
        'is_active' => 'boolean',
    ];
}
