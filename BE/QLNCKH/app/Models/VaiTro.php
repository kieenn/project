<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    protected $table = 'vai_tro'; // Table name
    public $timestamps = false; // No created_at/updated_at columns

    protected $fillable = [
        'ten_vai_tro',
        'mo_ta',
    ];

    // Relationships

    /**
     * Get the pivot entries (tham_gia) associated with this role.
     */
    public function thamGiaEntries()
    {
        // Links back to the ThamGia pivot model
        return $this->hasMany(ThamGia::class, 'vai_tro_id');
    }
}