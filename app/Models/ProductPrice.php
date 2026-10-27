<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'cost_price',
        'selling_price',
        'created_by',
    ];

    // Relationship to Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship to User (who created the record)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

   
}
