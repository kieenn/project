<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HocHam extends Model
{
    use HasFactory;

    protected $table = 'hoc_ham';
    public $timestamps = false;

    protected $fillable = [
        'ten',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'hoc_ham_id');
    }
}
