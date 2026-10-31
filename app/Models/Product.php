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
        'has_sub_unit',
        'sub_unit_id',
        'pieces_per_pack',
        'category_id',
        'barcode',
        'cost_price',
        'selling_price',
        'sub_unit_cost_price',
        'sub_unit_selling_price',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'has_sub_unit' => 'boolean',
        'pieces_per_pack' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'sub_unit_cost_price' => 'decimal:2',
        'sub_unit_selling_price' => 'decimal:2',
    ];

    protected $appends = [
        'quantity',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function subUnit()
    {
        return $this->belongsTo(Unit::class, 'sub_unit_id');
    }

    /**
     * Format a base quantity into Pack and Piece breakdown.
     * e.g. 28 pieces with 18 pcs/box -> "1 box, 10 pieces"
     */
    public function formatQuantity($baseQty): string
    {
        $qty = (float) $baseQty;
        $isNegative = $qty < 0;
        $absQty = abs($qty);
        $packName = $this->unit?->name ?? 'Pack';
        $subUnitName = $this->subUnit?->name ?? 'Piece';

        $formatName = function($count, $name) {
            $fmtCount = (float) $count == (int) $count ? (int) $count : rtrim(rtrim(number_format((float) $count, 2, '.', ''), '0'), '.');
            $cleaned = trim($name ?? '');
            $lower = strtolower($cleaned);

            // Normalize singular word
            $singular = $cleaned;
            if (str_ends_with($lower, 'boxes')) $singular = 'box';
            elseif (str_ends_with($lower, 'pieces')) $singular = 'piece';
            elseif (str_ends_with($lower, 'bundles')) $singular = 'bundle';
            elseif (str_ends_with($lower, 'liters') || str_ends_with($lower, 'litres')) $singular = 'liter';
            elseif (str_ends_with($lower, 'kilograms')) $singular = 'kg';
            elseif (str_ends_with($lower, 'meters') || str_ends_with($lower, 'metres')) $singular = 'meter';
            elseif (str_ends_with($lower, 'bags')) $singular = 'bag';
            elseif (str_ends_with($lower, 'packs')) $singular = 'pack';
            elseif (str_ends_with($lower, 'cartons')) $singular = 'carton';
            elseif (str_ends_with($lower, 'rolls')) $singular = 'roll';
            elseif (str_ends_with($lower, 'bottles')) $singular = 'bottle';
            elseif (str_ends_with($lower, 's') && strlen($lower) > 3) $singular = rtrim($cleaned, 'sS');

            // Generate correct plural
            $singLower = strtolower($singular);
            if ($singLower === 'box') $plural = 'boxes';
            elseif ($singLower === 'piece') $plural = 'pieces';
            elseif ($singLower === 'bag') $plural = 'bags';
            elseif ($singLower === 'pack') $plural = 'packs';
            elseif ($singLower === 'carton') $plural = 'cartons';
            elseif ($singLower === 'roll') $plural = 'rolls';
            elseif ($singLower === 'bottle') $plural = 'bottles';
            elseif (str_ends_with($singLower, 'ch') || str_ends_with($singLower, 'sh') || str_ends_with($singLower, 'x')) $plural = $singLower . 'es';
            else $plural = $singLower . 's';

            return $count == 1 ? "{$fmtCount} {$singLower}" : "{$fmtCount} {$plural}";
        };

        $prefix = $isNegative ? '-' : '';

        if ($this->has_sub_unit && $this->pieces_per_pack > 1) {
            $packs = (int) floor($absQty / $this->pieces_per_pack);
            $loose = (int) round(fmod($absQty, $this->pieces_per_pack));

            if ($packs > 0 && $loose > 0) {
                return $prefix . $formatName($packs, $packName) . ', ' . $formatName($loose, $subUnitName);
            } elseif ($packs > 0) {
                return $prefix . $formatName($packs, $packName);
            } elseif ($loose > 0) {
                return $prefix . $formatName($loose, $subUnitName);
            } else {
                return $prefix . $formatName(0, $subUnitName);
            }
        }

        $formattedQty = (float) rtrim(rtrim(number_format($absQty, 2, '.', ''), '0'), '.');
        return $prefix . $formatName($formattedQty, $packName);
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

    public function stockAdjustmentItems()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
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
        if ($this->relationLoaded('outletStocks')) {
            $stock = $this->outletStocks->firstWhere('outlet_id', $outletId);
            return (float) ($stock ? $stock->quantity : 0);
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
