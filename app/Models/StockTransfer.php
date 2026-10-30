<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_no',
        'from_outlet_id',
        'to_outlet_id',
        'status',
        'transfer_date',
        'dispatched_at',
        'received_at',
        'dispatched_by',
        'received_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'from_outlet_id' => 'integer',
        'to_outlet_id' => 'integer',
        'transfer_date' => 'date',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'dispatched_by' => 'integer',
        'received_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function fromOutlet()
    {
        return $this->belongsTo(Outlet::class, 'from_outlet_id');
    }

    public function toOutlet()
    {
        return $this->belongsTo(Outlet::class, 'to_outlet_id');
    }

    public function dispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public static function generateTransferNo(): string
    {
        $prefix = 'TRF-' . date('Ymd') . '-';
        $latest = static::where('transfer_no', 'like', $prefix . '%')->latest('id')->first();
        $next = $latest ? (((int) substr($latest->transfer_no, -4)) + 1) : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}

