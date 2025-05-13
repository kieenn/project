<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinhVucNghienCuu extends Model
{
    use HasFactory;

    protected $table = 'linh_vuc_nc';
    public $timestamps = false;

    protected $fillable = [
        'ten',
    ];

    // Relationships
    public function deTai()
    {
        // Correct foreign key based on de_tai schema
        return $this->hasMany(DeTai::class, 'lvnc_id');
    }
}
