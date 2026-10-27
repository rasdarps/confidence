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
        'unit_price',
        'buying_price',
        'unit_id',
    ];

    protected $casts = [
        'date' => 'date',
        'buying_qty' => 'integer',
        'unit_price' => 'decimal:2',
        'buying_price' => 'decimal:2',
      
    ];

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
