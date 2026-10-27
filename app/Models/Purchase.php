<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;
    
    protected $fillable = [
        'purchase_no',
        'date',
        'description',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'cancelled_at' => 'datetime',
        'cancelled_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    // User relationships
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

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Existing relationships
    public function purchase_payment(){
        return $this->belongsTo(PurchasePayment::class,'id','purchase_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function purchase_details(){
        return $this->hasMany(PurchaseDetail::class,'purchase_id','id');
    }

    public function editTrails()
    {
        return $this->hasMany(PurchaseEditTrail::class, 'purchase_id', 'id');
    }
}
