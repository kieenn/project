<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPermission extends Pivot // Extend Pivot for pivot tables
{
    use HasFactory;

    protected $table = 'user_permissions';
    public $incrementing = true; // Has its own auto-incrementing ID
    public $timestamps = false; // Use assigned_at manually or define constants

    // Define custom timestamp if needed
    // const CREATED_AT = 'assigned_at';
    // const UPDATED_AT = null;

    protected $fillable = [
        'msvc',
        'permission_id',
        'assigned_by',
        'assigned_at', // Include if you want to fill it directly
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // Relationships from the pivot table itself
    public function user()
{
    // This confirms the link is via 'msvc'
    return $this->belongsTo(User::class, 'msvc');
}
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function assigner() // The admin who assigned the permission
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
