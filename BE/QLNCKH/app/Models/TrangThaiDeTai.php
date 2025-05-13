<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrangThaiDeTai extends Model
{
    use HasFactory;

    protected $table = 'trang_thai_de_tai';
    public $timestamps = false;
    // protected $primaryKey = 'id';

    protected $fillable = [
        'ma_trang_thai',
        'ten_hien_thi',
        'mo_ta',
    ];

    // Relationships
    public function deTai()
    {
        return $this->hasMany(DeTai::class, 'trang_thai_id');
    }
}
