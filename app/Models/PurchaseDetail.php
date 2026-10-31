<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;
    // use Auditable;

    // protected $guarded = [];
    protected $fillable = [
        'purchase_id',
        'date',
        'category_id',
        'product_id',
        'buying_qty',
        'pack_qty',
        'piece_qty',
        'pieces_per_pack',
        'unit_price',
        'sub_unit_cost_price',
        'buying_price',
        'unit_id',
    ];

    protected $casts = [
        'date' => 'date',
        'buying_qty' => 'decimal:2',
        'pack_qty' => 'decimal:2',
        'piece_qty' => 'decimal:2',
        'pieces_per_pack' => 'integer',
        'unit_price' => 'decimal:2',
        'sub_unit_cost_price' => 'decimal:2',
        'buying_price' => 'decimal:2',
    ];

    public function getFormattedQtyAttribute(): string
    {
        $packQty = (float) ($this->pack_qty ?? 0);
        $pieceQty = (float) ($this->piece_qty ?? 0);
        $unitName = $this->product?->unit?->name ?? 'Pack';
        $subUnitName = $this->product?->subUnit?->name ?? 'Piece';

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
            elseif (str_ends_with($lower, 's') && strlen($lower) > 3) $singular = rtrim($cleaned, 'sS');

            // Generate correct plural
            $singLower = strtolower($singular);
            if ($singLower === 'box') $plural = 'boxes';
            elseif ($singLower === 'piece') $plural = 'pieces';
            elseif ($singLower === 'bag') $plural = 'bags';
            elseif ($singLower === 'pack') $plural = 'packs';
            elseif (str_ends_with($singLower, 'ch') || str_ends_with($singLower, 'sh') || str_ends_with($singLower, 'x')) $plural = $singLower . 'es';
            else $plural = $singLower . 's';

            return $count == 1 ? "{$fmtCount} {$singLower}" : "{$fmtCount} {$plural}";
        };

        if ($packQty > 0 && $pieceQty > 0) {
            return $formatName($packQty, $unitName) . ', ' . $formatName($pieceQty, $subUnitName);
        } elseif ($packQty > 0) {
            return $formatName($packQty, $unitName);
        } elseif ($pieceQty > 0) {
            return $formatName($pieceQty, $subUnitName);
        }

        if ($this->product && (float) $this->buying_qty > 0) {
            return $this->product->formatQuantity($this->buying_qty);
        }

        return rtrim(rtrim(number_format((float) $this->buying_qty, 2, '.', ''), '0'), '.');
    }

      public function product(){
        return $this->belongsTo(Product::class,'product_id','id');
    }
  

     public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    public function unit(){
        return $this->belongsTo(Unit::class,'unit_id','id');
    }



}
