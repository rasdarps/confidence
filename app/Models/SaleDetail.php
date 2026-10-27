<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
    'created_by' => 'integer',
    'updated_by' => 'integer',
    'deleted_by' => 'integer',
  ];

      public function product(){
        return $this->belongsTo(Product::class,'product_id','id');
    }
  

     public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }
}
 
