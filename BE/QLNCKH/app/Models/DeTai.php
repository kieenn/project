<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeTai extends Model
{
    use HasFactory;

    protected $table = 'de_tai';
    protected $primaryKey = 'id'; // Non-standard primary key
    public $incrementing = true;     // Primary key is not auto-incrementing
    protected $keyType = 'integer';    // Primary key is a string (VARCHAR)

    // Define non-standard timestamp column names
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'ma_de_tai',
        'ten_de_tai',
        'trang_thai_id',
        'ghi_chu',
        'admin_id',
        'created_at',
        'cnv_id',
        'lvnc_id',
        'chu_tri_id',
        'chu_quan_id',
        'thoi_gian_nop',
        'loai_hinh_nghien_cuu',
        'thoi_gian_thuc_hien', // Đơn vị tính (ví dụ: tháng) sẽ do logic ứng dụng quy định
        'tong_kinh_phi',
        'tong_quan_van_de',
        'tinh_cap_thiet',
        'doi_tuong', //  đối tượng nghiên cứu
        'pham_vi',   //  phạm vi nghiên cứu
        'muc_tieu_nghien_cuu',
        'noi_dung_phuong_phap',
        'thoi_gian_xet_duyet',
        'ngay_bat_dau_dukien',
        'ngay_ket_thuc_dukien',
        'nhan_xet'
    ];

    protected $casts = [
        // Giữ nguyên 'date' nếu bạn chỉ cần ngày, hoặc 'datetime' nếu cần cả giờ phút giây
        'thoi_gian_nop' => 'datetime',
        'created_at' => 'datetime',
        'thoi_gian_xet_duyet' => 'datetime',
        'tong_kinh_phi' => 'decimal:2', // Ép kiểu sang decimal với 2 chữ số sau dấu phẩy
        'thoi_gian_thuc_hien' => 'integer',
        'ngay_bat_dau_dukien' => 'date',
        'ngay_ket_thuc_dukien' => 'date'
    ];

    // Relationships
    public function trangThai()
    {
        return $this->belongsTo(TrangThaiDeTai::class, 'trang_thai_id');
    }

    public function admin() // Admin duyệt/tạo
    {
        // Schema uses 'admin_id'
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function linhVucNghienCuu()
    {
        // Schema uses 'lvnc_id'
        return $this->belongsTo(LinhVucNghienCuu::class, 'lvnc_id');
    }

    public function capNhiemVu()
    {
        // Schema uses 'cnv_id'
        return $this->belongsTo(CapNhiemVu::class, 'cnv_id');
    }

    public function chuTri() // User chủ trì
    {
        // Schema uses 'chu_tri_id'
        return $this->belongsTo(DonVi::class, 'chu_tri_id');
    }

    public function chuQuan() // Đơn vị chủ quản
    {
        // Schema uses 'chu_quan_id'
        return $this->belongsTo(DonVi::class, 'chu_quan_id');
    }

    public function tienDo() // Relationship through the pivot table
    {
        // Schema uses 'de_tai_id', 'tien_do_id' in pivot table 'de_tai_tien_do'
        // Schema for de_tai_tien_do is missing 'file_bao_cao', adjust withPivot if needed
        return $this->belongsToMany(TienDo::class, 'de_tai_tien_do', 'de_tai_id', 'tien_do_id', 'ma_de_tai', 'id')
                    ->using(DeTaiTienDo::class) // Use the pivot model
                    // Remove 'file_bao_cao' as it doesn't exist according to the error
                    ->withPivot('id', 'mo_ta', 'is_present', 'created_at') // Load columns that actually exist
                    ->orderBy('tien_do.thu_tu'); // Use the actual table name 'tien_do'
    }

    public function deTaiTienDoEntries() // Direct access to pivot table entries
    {
        // Schema uses 'de_tai_id'
        return $this->hasMany(DeTaiTienDo::class, 'de_tai_id', 'id');
    }

   // In c:\Users\maing\OneDrive\Documents\KLTN\project\BE\QLNCKH\app\Models\DeTai.php
    public function giangVienDangKy() // Giảng viên đăng ký đề tài này
    {
        // Schema uses 'de_tai_id', 'msvc' in pivot table 'dang_ky_de_tai'
        // Links de_tai.id -> dang_ky_de_tai.de_tai_id AND users.msvc -> dang_ky_de_tai.msvc
        return $this->belongsToMany(User::class, 'dang_ky_de_tai', 'de_tai_id', 'msvc', 'id', 'msvc')
                    ->using(DangKyDeTai::class) // Specify the pivot model
                    ->withPivot('register_at'); // Load the timestamp column explicitly
    }

    public function giangVienThamGia() // Giảng viên tham gia đề tài này
    {
        // Schema uses 'de_tai_id', 'msvc', 'vai_tro_id', 'can_edit' in pivot table 'tham_gia'
        // Links de_tai.id -> tham_gia.de_tai_id AND users.msvc -> tham_gia.msvc
        return $this->belongsToMany(User::class, 'tham_gia', 'de_tai_id', 'msvc', 'id', 'msvc')
                    ->using(ThamGia::class) // Specify the pivot model
                    // Load pivot columns explicitly, including the timestamp
                    ->withPivot('vai_tro_id', 'can_edit', 'join_at');
    }

    public function baiBao() // Bài báo thuộc đề tài này
    {
        // Schema uses 'de_tai_id'
        return $this->hasMany(BaiBao::class, 'de_tai_id', 'id');
    }

    public function taiLieu() // Tài liệu thuộc đề tài này
    {
        // Schema uses 'de_tai_id'
        return $this->hasMany(TaiLieu::class, 'de_tai_id', 'id');
    }

    public function chuNhiemDeTai() // Giảng viên chủ nhiệm đề tài này
    {
        // Assumes vai_tro_id = 1 is 'Chủ nhiệm' in the 'vai_tro' table. Adjust if different.
        return $this->belongsToMany(User::class, 'tham_gia', 'de_tai_id', 'msvc', 'id', 'msvc')
                    ->wherePivot('vai_tro_id', 1) // Filter for the 'Chủ nhiệm' role
                    ->withPivot('vai_tro_id', 'can_edit', 'join_at') // Include pivot data
                    ->using(ThamGia::class); // Use the pivot model
    }

    public function msvcGvdkUser() // Giảng viên đã đăng ký đề tài (dựa trên cột msvc_gvdk)
    {
        // Links de_tai.msvc_gvdk to users.msvc
        return $this->belongsTo(User::class, 'msvc_gvdk', 'msvc');
    }
}
