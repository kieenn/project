<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use HasFactory;

    protected $table = 'admin_logs';

    // Treat 'thoi_gian' as the creation timestamp, disable updated_at
    const CREATED_AT = 'thoi_gian';
    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'hanh_dong',
        'doi_tuong',
        'doi_tuong_id',
        'noi_dung_truoc',
        'noi_dung_sau',
        'ip_address',
        // 'thoi_gian' // Usually handled automatically by Laravel if CREATED_AT is set
    ];

    protected $casts = [
        'noi_dung_truoc' => 'array', // Cast JSON text to array
        'noi_dung_sau' => 'array',   // Cast JSON text to array
        'thoi_gian' => 'datetime',
    ];

    // Relationships
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Optional: Polymorphic relationship to the logged object
    // This requires the 'doi_tuong' column to store the Model class name
    // and 'doi_tuong_id' to store the ID.
    // public function loggable()
    // {
    //    return $this->morphTo(__FUNCTION__, 'doi_tuong', 'doi_tuong_id');
    // }
}
