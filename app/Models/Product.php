<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;
    // protected $guarded = [];
    protected $fillable = [
    'name',
    'unit_id',
    'category_id',
    'quantity',
    'stock_level',
    'barcode',
    'created_by',
    'updated_by',
    'deleted_by',
    'cost_price',
    'selling_price',
   
];

protected $casts = [
    'quantity' => 'double',
    'stock_level' => 'double', 
    'created_by' => 'integer',
    'updated_by' => 'integer',
    'deleted_by' => 'integer',
    'cost_price' => 'decimal:2',
    'selling_price' => 'decimal:2',
];
    /*public function supplier(){
        return $this->belongsTo(Supplier::class,'supplier_id','id');
    }*/

     public function unit(){
        return $this->belongsTo(Unit::class);
    }

     public function category(){
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

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'stock_level')
            ->where('quantity', '>', 0);
    }
    
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    // User relationships - NOW PROPERLY LINKED
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

    // Generate deterministic numeric EAN-13 barcode: 200 + 9-digit product id + check digit
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
            // EAN-13 applies weight 1,3 alternating from the left for the first 12 digits.
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock()
    {
        return $this->quantity > 0;
    }


      /**
     * Check if product is low on stock
     */
    public function isLowStock()
    {
        return $this->quantity <= $this->stock_level && $this->quantity > 0;
    }
    /**
     * Check if product is out of stock
     */
    public function isOutOfStock()
    {
        return $this->quantity <= 0;
    }

/**
     * Get stock status label
     */
    public function getStockStatusAttribute()
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        } elseif ($this->isLowStock()) {
            return 'Low Stock';
        }
        return 'In Stock';
    }
    

    /**
     * Get stock status badge class
     */
    public function getStockBadgeClassAttribute()
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        } elseif ($this->isLowStock()) {
            return 'warning';
        }
        return 'success';
    }
}

