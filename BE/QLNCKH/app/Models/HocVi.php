<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HocVi extends Model
{
    use HasFactory;

    protected $table = 'hoc_vi';
    public $timestamps = false;

    protected $fillable = [
        'ten',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'hoc_vi_id');
    }
}
