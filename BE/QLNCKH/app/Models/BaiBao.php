<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

    class BaiBao extends Model
    {
        use HasFactory;

        protected $table = 'bai_bao'; // Correct table name
        protected $primaryKey = 'id'; // Correct primary key name
        public $incrementing = true;
        protected $keyType = 'integer';

        // Define non-standard timestamp column names
        const CREATED_AT = 'created_at'; // Correct constant name
        const UPDATED_AT = null; // Tell Eloquent there's no updated_at column

        protected $fillable = [
            'de_tai_id',  // Correct column name
            'ten_bai_bao',// Correct column name
            'ngay_xuat_ban',
            'mo_ta',
            'trang_thai',
            'ma_de_tai', // Added based on schema
            'nhan_xet',
            'msvc_nguoi_nop', // Make sure this is fillable if set directly, though it's set in LecturerController
            'admin_msvc'
        ];

        protected $casts = [
            'ngay_xuat_ban' => 'date', // Corrected typo
            'trang_thai' => 'string', // Cast ENUM to string
            'created_at' => 'datetime', // Correct attribute name
        ];

        // Relationships
        public function deTai()
        {
            // Correct foreign key and owner key
            return $this->belongsTo(DeTai::class, 'de_tai_id', 'ma_de_tai');
        }

        public function taiLieu()
        {
            // A BaiBao can have many TaiLieu records
            return $this->hasMany(TaiLieu::class, 'bai_bao_id', 'id');
        }

        /**
         * Get the user who submitted the article.
         */
            
        public function nguoiNop()
        {
            // Assumes 'msvc_nguoi_nop' in 'bai_bao' table links to 'msvc' in 'users' table
            return $this->belongsTo(User::class, 'msvc_nguoi_nop', 'msvc');
        }
        public function admin_xet_duyet()
        {
            // Assumes 'msvc_nguoi_nop' in 'bai_bao' table links to 'msvc' in 'users' table
            return $this->belongsTo(User::class, 'admin_msvc', 'msvc');
        }
    }
