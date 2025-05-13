<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TienDo extends Model
{
    use HasFactory;

    protected $table = 'tien_do';
    public $timestamps = false;

    protected $fillable = [
        'ten_moc',
        'mo_ta',
        'thu_tu',
    ];

    // Relationships
    public function deTaiTienDo()
    {
        return $this->hasMany(DeTaiTienDo::class, 'tien_do_id');
    }

     public function deTais() // De Tai related through the pivot table
    {
        // Correct table name and keys based on schema
        return $this->belongsToMany(DeTai::class, 'de_tai_tien_do', 'tien_do_id', 'de_tai_id', 'id', 'ma_de_tai')
                    ->using(DeTaiTienDo::class) // Use the pivot model
                    // Correct pivot columns based on de_tai_tien_do schema
                    ->withPivot('id', 'mo_ta', 'trang_thai', 'thoi_gian_nop');
    }
}
