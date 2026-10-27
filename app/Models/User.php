<?php
  
namespace App\Models;
  
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\Auditable;
  
class User extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasRoles, HasApiTokens;
    use Auditable;
  
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'must_change_password',
        'user_type',
        'outlet_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
  
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
  
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'must_change_password' => 'boolean',
        'outlet_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function isCentral(): bool
    {
        return is_null($this->outlet_id) || ($this->outlet && $this->outlet->isCentral());
    }

    public function assignedOutletId(): ?int
    {
        return $this->outlet_id;
    }

    /**
     * Find user for authentication (supports both email and username)
     */
    public function findForPassport($identifier)
    {
        return $this->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();
    }
}
