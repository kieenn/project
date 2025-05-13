<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonVi extends Model
{
    use HasFactory;

    protected $table = 'don_vi';
    public $timestamps = false; // No created_at/updated_at columns

    protected $fillable = [
        'ten',
        'parent_id',
    ];

    // Relationships
    public function parent() // Đơn vị cha
    {
        return $this->belongsTo(DonVi::class, 'parent_id');
    }

    public function children() // Các đơn vị con
    {
        return $this->hasMany(DonVi::class, 'parent_id');
    }

    public function users() // Users thuộc đơn vị này
    {
        return $this->hasMany(User::class, 'don_vi_id');
    }

    public function deTaiChuTri() // Đề tài do đơn vị này chủ trì
    {
        // Correct foreign key based on de_tai schema
        return $this->hasMany(DeTai::class, 'chu_tri_id');
    }

    public function deTaiChuQuan() // Đề tài do đơn vị này chủ quản
    {
        // Correct foreign key based on de_tai schema
        return $this->hasMany(DeTai::class, 'chu_quan_id');
    }
}
