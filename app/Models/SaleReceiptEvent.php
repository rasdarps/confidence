<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReceiptEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'operator_id',
        'event_type',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'sale_id' => 'integer',
        'operator_id' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}