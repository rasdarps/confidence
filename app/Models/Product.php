<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'unit_id',
        'category_id',
        'barcode',
        'cost_price',
        'selling_price',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    protected $appends = [
        'quantity',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function purchasedetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'product_id', 'id');
    }

    public function invoicedetails()
    {
        return $this->hasMany(SaleDetail::class, 'product_id', 'id');
    }

    public function outletStocks()
    {
        return $this->hasMany(OutletStock::class);
    }

    public function transferItems()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function stockForOutlet(?int $outletId = null): ?OutletStock
    {
        if (! $outletId) {
            return null;
        }
        return $this->outletStocks()->where('outlet_id', $outletId)->first();
    }

    public function quantityForOutlet(?int $outletId = null): float
    {
        if (! $outletId) {
            return $this->quantity;
        }
        return (float) ($this->outletStocks()->where('outlet_id', $outletId)->value('quantity') ?? 0);
    }

    public function getQuantityAttribute(): float
    {
        return (float) ($this->relationLoaded('outletStocks')
            ? $this->outletStocks->sum('quantity')
            : $this->outletStocks()->sum('quantity'));
    }

    public function getTotalQuantityAttribute(): float
    {
        return $this->getQuantityAttribute();
    }

    public function getStockLevelAttribute(): float
    {
        return (float) ($this->relationLoaded('outletStocks')
            ? $this->outletStocks->sum('stock_level')
            : $this->outletStocks()->sum('stock_level'));
    }

    public function scopeInStock($query, ?int $outletId = null)
    {
        return $query->whereHas('outletStocks', function ($q) use ($outletId) {
            if ($outletId) {
                $q->where('outlet_id', $outletId);
            }
            $q->where('quantity', '>', 0);
        });
    }

    public function scopeLowStock($query, ?int $outletId = null)
    {
        return $query->whereHas('outletStocks', function ($q) use ($outletId) {
            if ($outletId) {
                $q->where('outlet_id', $outletId);
            }
            $q->whereColumn('quantity', '<=', 'stock_level')->where('quantity', '>', 0);
        });
    }
    
    public function scopeOutOfStock($query, ?int $outletId = null)
    {
        return $query->whereDoesntHave('outletStocks', function ($q) use ($outletId) {
            if ($outletId) {
                $q->where('outlet_id', $outletId);
            }
            $q->where('quantity', '>', 0);
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function productPrice()
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id');
    }

    public static function generateBarcode(string $name, int $id, $createdAt = null): string
    {
        $base12 = '200' . str_pad((string) $id, 9, '0', STR_PAD_LEFT);
        $checkDigit = static::calculateEan13CheckDigit($base12);

        return $base12 . $checkDigit;
    }

    private static function calculateEan13CheckDigit(string $base12): int
    {
        if (!preg_match('/^\d{12}$/', $base12)) {
            throw new \InvalidArgumentException('EAN-13 base must be exactly 12 numeric digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $base12[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    public function isInStock(?int $outletId = null): bool
    {
        return $this->quantityForOutlet($outletId) > 0;
    }

    public function isLowStock(?int $outletId = null): bool
    {
        $qty = $this->quantityForOutlet($outletId);
        $stock = $outletId ? ($this->stockForOutlet($outletId)?->stock_level ?? 0) : $this->stock_level;
        return $qty <= $stock && $qty > 0;
    }

    public function isOutOfStock(?int $outletId = null): bool
    {
        return $this->quantityForOutlet($outletId) <= 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        } elseif ($this->isLowStock()) {
            return 'Low Stock';
        }
        return 'In Stock';
    }

    public function getStockBadgeClassAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        } elseif ($this->isLowStock()) {
            return 'warning';
        }
        return 'success';
    }
}
