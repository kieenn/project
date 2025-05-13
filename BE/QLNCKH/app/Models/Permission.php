<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';
    public $timestamps = false; // No created_at/updated_at columns

    protected $fillable = [
        'ma_quyen',
        'mo_ta',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'msvc')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->using(UserPermission::class);
                    // ->withTimestamps('assigned_at', null);
    }
}
