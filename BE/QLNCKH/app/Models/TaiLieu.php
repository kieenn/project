<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaiLieu extends Model
{
    use HasFactory;

    protected $table = 'tai_lieu';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'integer';

    // Define non-standard timestamp column names
    const CREATED_AT = 'created_at'; // Correct constant name
      const UPDATED_AT = null;

    protected $fillable = [
        'bai_bao_id', // Foreign key to bai_bao table
        'file_path',
        'mo_ta',
        'msvc_nguoi_upload'
    ];

     protected $casts = [
        'created_at' => 'datetime', // Correct attribute name
    ];

    // Relationships
    public function baiBao()
    {
        // Relationship to BaiBao model
        // Assumes BaiBao model's primary key is 'id'
        return $this->belongsTo(BaiBao::class, 'bai_bao_id', 'id');
    }
}
