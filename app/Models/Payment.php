<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeVisibleToSalesViewer(Builder $query): Builder
    {
        $viewer = auth()->user();
        if ($viewer instanceof User && $viewer->hasRole('Cashier')) {
            $outletId = $viewer->assignedOutletId();
            $query->whereHas('sale', function (Builder $saleQuery) use ($viewer, $outletId) {
                if ($outletId) {
                    $saleQuery->where('outlet_id', $outletId);
                } else {
                    $saleQuery->where('created_by', $viewer->id);
                }
            });
        }

        return $query;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }
}
