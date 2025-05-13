<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DangKyDeTai extends Pivot
{
    use HasFactory;

    protected $table = 'dang_ky_de_tai';
    protected $primaryKey = ['msvc', 'de_tai_id']; // Composite primary key
    public $incrementing = false; // Not auto-incrementing
    public $timestamps = false; // Use register_at manually or define constants

    // Define custom timestamp if needed
    const CREATED_AT = 'register_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'msvc',
        'de_tai_id',
        'register_at',
    ];

     protected $casts = [
        'register_at' => 'datetime',
    ];

    // Relationships (optional, usually accessed via User or DeTai)
    public function giangVien()
    {
        // Link using the 'msvc' column in this pivot table to the 'msvc' column in users table
        return $this->belongsTo(User::class, 'msvc', 'msvc');
    }

    public function deTai()
    {
        return $this->belongsTo(DeTai::class, 'de_tai_id', 'ma_de_tai');
    }
}
