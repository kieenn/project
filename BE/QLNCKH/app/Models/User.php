<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // If using Sanctum for APIs

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    // Define non-standard timestamp column names if needed, or disable timestamps
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // No updated_at column in the schema

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ho_ten',
        'email',
        'password',
        'sdt',
        'mssv',
        'msvc',
        'is_superadmin',
        'don_vi_id',
        'hoc_ham_id',
        'hoc_vi_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'remember_token', // Add if you use remember tokens
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime', // Add if you use email verification
        'password' => 'hashed',
        'is_superadmin' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships

    public function donVi()
    {
        return $this->belongsTo(DonVi::class, 'don_vi_id');
    }

    public function hocHam()
    {
        return $this->belongsTo(HocHam::class, 'hoc_ham_id');
    }

    public function hocVi()
    {
        return $this->belongsTo(HocVi::class, 'hoc_vi_id');
    }

    public function permissions()
{
    // belongsToMany(RelatedModel, pivot_table, foreign_pivot_key, related_pivot_key, parent_key, related_key)
    // foreign_pivot_key ('msvc'): Column in 'user_permissions' linking to User.
    // related_pivot_key ('permission_id'): Column in 'user_permissions' linking to Permission.
    // parent_key ('msvc'): The key on the *User* model to match against 'foreign_pivot_key'.
    return $this->belongsToMany(Permission::class, 'user_permissions', 'msvc', 'permission_id', 'msvc', 'id')
                ->withPivot('assigned_by', 'assigned_at')
                ->using(UserPermission::class);
}

    public function assignedPermissions() // Permissions assigned by this user (if admin)
    {
        return $this->hasMany(UserPermission::class, 'assigned_by');
    }

    public function deTaiChuTri() // Đề tài mà user này chủ trì
    {
        return $this->hasMany(DeTai::class, 'chuTriID');
    }

    public function deTaiAdmin() // Đề tài mà user này tạo/duyệt
    {
        return $this->hasMany(DeTai::class, 'adminID');
    }

    public function dangKyDeTai() // Đề tài đã đăng ký
    {
        // belongsToMany(RelatedModel, pivot_table, foreign_pivot_key, related_pivot_key, parent_key, related_key)
        // RelatedModel: DeTai
        // pivot_table: dang_ky_de_tai
        // foreign_pivot_key: msvc (column in pivot linking to User)
        // related_pivot_key: de_tai_id (column in pivot linking to DeTai)
        // parent_key: msvc (key on User model)
        // related_key: ma_de_tai (key on DeTai model)
        return $this->belongsToMany(DeTai::class, 'dang_ky_de_tai', 'msvc', 'de_tai_id', 'msvc', 'id') // Sửa 'ma_de_tai' thành 'id'
                    ->using(DangKyDeTai::class) // Specify the pivot model
                    ->withPivot('register_at'); // Load the timestamp column explicitly
    }

    public function thamGiaDeTai() // Đề tài đã tham gia
    {
        // Schema: tham_gia (msvc, de_tai_id, vai_tro_id, can_edit, join_at)
        // Links: users.msvc -> tham_gia.msvc | de_tai.ma_de_tai -> tham_gia.de_tai_id
        return $this->belongsToMany(DeTai::class, 'tham_gia', 'msvc', 'de_tai_id', 'msvc', 'id') // Sửa 'ma_de_tai' thành 'id'
                    ->using(ThamGia::class) // Specify the pivot model
                    // Load pivot columns explicitly, including the timestamp
                    ->withPivot('vai_tro_id', 'can_edit', 'join_at');
    }

    /**
     * Các vai trò mà người dùng này đảm nhận thông qua bảng tham_gia.
     * Một người dùng có thể có nhiều vai trò khác nhau trong nhiều đề tài.
     * Mối quan hệ này sẽ trả về một tập hợp các model VaiTro duy nhất.
     */
    public function vaiTro()
    {
        // User -> tham_gia (pivot) -> VaiTro
        // 'tham_gia': Tên bảng trung gian.
        // 'msvc': Khóa ngoại trong bảng 'tham_gia' tham chiếu đến 'users.msvc'.
        // 'vai_tro_id': Khóa ngoại trong bảng 'tham_gia' tham chiếu đến 'vai_tro.id'.
        // 'msvc': Khóa cục bộ trên model 'User' (users.msvc).
        // 'id': Khóa cục bộ trên model 'VaiTro' (vai_tro.id).
        return $this->belongsToMany(VaiTro::class, 'tham_gia', 'msvc', 'vai_tro_id', 'msvc', 'id')
                    ->using(ThamGia::class) // Chỉ định model pivot nếu bạn muốn tương tác với nó
                    ->withPivot('de_tai_id', 'can_edit', 'join_at'); // Load join_at như một cột pivot thông thường
    }

    public function baiBaoTacGia() // Bài báo user này là tác giả
    {
        return $this->hasMany(BaiBao::class, 'tacGiaID');
    }

    public function taiLieuUploaded() // Tài liệu user này upload
    {
        return $this->hasMany(TaiLieu::class, 'uploader_id');
    }

    public function adminLogs() // Log hoạt động của admin này
    {
        return $this->hasMany(AdminLog::class, 'admin_id');
    }
    
}
