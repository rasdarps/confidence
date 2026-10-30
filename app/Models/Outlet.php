<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_central',
        'phone',
        'email',
        'address',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_central' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function isCentral(): bool
    {
        return (bool) $this->is_central;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stocks()
    {
        return $this->hasMany(OutletStock::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transfersOut()
    {
        return $this->hasMany(StockTransfer::class, 'from_outlet_id');
    }

    public function transfersIn()
    {
        return $this->hasMany(StockTransfer::class, 'to_outlet_id');
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    public function cashReconciliations()
    {
        return $this->hasMany(CashReconciliation::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCentral($query)
    {
        return $query->where('is_central', true);
    }

    public function scopeRetail($query)
    {
        return $query->where('type', 'retail_outlet');
    }

    public static function centralWarehouse(): ?self
    {
        return static::query()
            ->where('is_central', true)
            ->orderBy('id')
            ->first()
            ?? static::query()->orderBy('id')->first();
    }

    public static function centralWarehouseId(): ?int
    {
        $id = static::query()
            ->where('is_central', true)
            ->orderBy('id')
            ->value('id')
            ?? static::query()->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }
}

