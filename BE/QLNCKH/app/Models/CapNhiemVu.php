<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapNhiemVu extends Model
{
    use HasFactory;

    protected $table = 'cap_nhiem_vu'; // Tên bảng trong cơ sở dữ liệu
    public $timestamps = false;

    protected $fillable = [
        'ten',
        'kinh_phi',
        'parent_id',
    ];

    // Relationships
    public function parent() // Cấp nhiệm vụ cha
    {
        return $this->belongsTo(CapNhiemVu::class, 'parent_id');
    }

    public function children() // Các cấp nhiệm vụ con
    {
        return $this->hasMany(CapNhiemVu::class, 'parent_id');
    }

    public function deTai()
    {
        // Correct foreign key based on de_tai schema
        return $this->hasMany(DeTai::class, 'cnv_id');
    }
}
