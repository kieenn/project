<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeTaiTienDo extends Pivot
{
    use HasFactory;

    protected $table = 'de_tai_tien_do'; // Correct table name
    public $incrementing = true; // Has its own ID
    public $timestamps = false; // Use thoiGianNop manually or define constants

    // Define custom timestamp if needed
    // const CREATED_AT = 'thoiGianNop'; // Or maybe not managed automatically
    // const UPDATED_AT = null;

    protected $fillable = [
        'de_tai_id',
        'tien_do_id',
        'mo_ta',
        // 'file_bao_cao',
        // 'trang_thai',
        'is_present',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'is_present' => 'boolean'
    ];

    // Relationships from the pivot
    public function deTai()
    {
        // Sửa 'ma_de_Tai' thành 'ma_de_tai' để khớp với primary key của DeTai
        return $this->belongsTo(DeTai::class, 'de_tai_id', 'ma_de_tai');
    }

    public function tienDo()
    {
        // Link using tien_do_id based on schema
        return $this->belongsTo(TienDo::class, 'tien_do_id');
    }
}
