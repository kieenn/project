<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThamGia extends Pivot
{
    use HasFactory;

    protected $table = 'tham_gia';
    protected $primaryKey = ['msvc', 'de_tai_id']; // Composite primary key
    public $incrementing = false;
    public $timestamps = false; // Use join_at manually or define constants

    // Define custom timestamp if needed
    // const CREATED_AT = 'join_at';
    // const UPDATED_AT = null;

    protected $fillable = [
        'msvc',
        'de_tai_id',
        'vai_tro_id',
        'can_edit',
        'join_at',
    ];

    protected $casts = [
        'join_at' => 'datetime',
        'can_edit' => 'boolean', // Add cast for boolean
    ];

    // Relationships from the pivot table
    public function giangVien()
    {
        // Link using the 'msvc' column in this pivot table
        return $this->belongsTo(User::class, 'msvc', 'msvc');
    }

    public function deTai()
    {
        // Link using the 'de_tai_id' column in this pivot table
        // to the 'id' column in the 'de_tai' table
        return $this->belongsTo(DeTai::class, 'de_tai_id', 'id');
    }

    public function vaiTro() // Add relationship to VaiTro model
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id');
    }
}
