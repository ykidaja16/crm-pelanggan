<?php

namespace App\Models;


use App\Models\Role;
use App\Models\ApprovalRequest;
use App\Models\Cabang;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function cabangs()
    {
        return $this->belongsToMany(Cabang::class, 'user_cabangs');
    }

    /**
     * Approval requests assigned to this user (task approval pending).
     */
    public function assignedApprovalRequests()
    {
        return $this->hasMany(ApprovalRequest::class, 'assigned_to');
    }

    /**
     * Approval requests where this user is requester.
     */
    public function approvalRequestsAsRequester()
    {
        return $this->hasMany(ApprovalRequest::class, 'requested_by');
    }

    /**
     * Approval requests where this user is reviewer.
     */
    public function approvalRequestsAsReviewer()
    {
        return $this->hasMany(ApprovalRequest::class, 'reviewed_by');
    }

    /**
     * Pelanggan class histories changed by this user.
     */
    public function pelangganClassHistories()
    {
        return $this->hasMany(\App\Models\PelangganClassHistory::class, 'changed_by');
    }

    /**
     * Ambil array ID cabang yang bisa diakses user ini.
     * IT role: akses semua cabang (return empty array = no restriction).
     */
    public function getAccessibleCabangIds(): array
    {
        return $this->cabangs()->pluck('cabangs.id')->toArray();
    }
}
