<?php
// app/Http/Controllers/AuthController.php

// Thêm DB facade
namespace App\Http\Controllers;

use App\Events\BaiBaoApproved;
use App\Events\BaiBaoRejected;
use App\Events\DeTaiApproved;
use Illuminate\Support\Facades\DB; 
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Import the User model
use App\Models\Permission; // Import the UserPermission model (adjust namespace if needed)
// OR if you prefer the DB facade:
// use Illuminate\Support\Facades\DB;
use App\Models\HocHam;
use App\Models\HocVi;
use App\Models\DonVi;
use Illuminate\Validation\Rules\Password; // Thêm Password rule
use Illuminate\Support\Facades\Log;
use App\Models\CapNhiemVu; // Import the CapNhiemVu model (adjust namespace if needed)
use App\Models\LinhVucNghienCuu; // Import the LinhVucNghienCuu model (adjust namespace if needed)
use App\Models\DeTai; // Import the DeTai model (adjust namespace if needed)
use App\Models\DeTaiTienDo;
use App\Models\ThamGia;
use App\Models\TrangThaiDeTai;
use App\Models\VaiTro;
use App\Models\TienDo;
use App\Models\Notification;
use App\Models\BaiBao;
use App\Models\TaiLieu;
use App\Events\DeTaiRejected;
class AdminController extends Controller
{
    /**
     * Handle an incoming authentication request using MSVC for Admin access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */


     // quản lý tài khoản
    
    public function getUsers(Request $request)
    {   
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Tài Khoản')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Define the number of users per page
        $perPage = 16;

        // Start building the query
        $query = User::query();

        // Filter by don_vi_id if provided
        if ($request->filled('don_vi_id')) {
            $query->where('don_vi_id', $request->input('don_vi_id'));
        }

        // Search functionality if 'search' term is provided
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ho_ten', 'ILIKE', "%{$searchTerm}%") // Use ILIKE for case-insensitive search in PostgreSQL
                  ->orWhere('msvc', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Fetch users with pagination, eager load permissions to avoid N+1 queries
        $users = $query->with(['permissions', 'donVi']) // Eager load relationships
                     ->select('id', 'msvc', 'ho_ten', 'email', 'sdt', 'is_superadmin', 'hoc_ham_id', 'hoc_vi_id', 'don_vi_id', 'dob')
                     ->paginate($perPage);

        // Laravel's paginate method automatically structures the response for pagination
        return response()->json($users, 200);
    }
    
    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'hoTen' => 'required|string|max:255', // Sử dụng hoTen
    //         'msvc' => 'required|string|max:100|unique:users,msvc',
    //         'email' => 'required|string|email|max:255|unique:users,email',
    //         'password' => ['required', 'confirmed', Password::defaults()],
    //         // Thêm validation cho is_superadmin và permissions
    //         'is_superadmin' => 'required|boolean',
    //         'permissions' => 'present|array', // Phải có key 'permissions', giá trị là mảng (có thể rỗng)
    //         'permissions.*' => 'string|exists:permissions,ma_quyen' // Mỗi item trong mảng phải là string và tồn tại trong bảng permissions
    //     ]);

    //     // Bắt đầu transaction để đảm bảo tính toàn vẹn
    //     DB::beginTransaction();
    //     try {
    //         // Tạo user mới
    //         $user = User::create([
    //             'hoTen' => $validatedData['hoTen'],
    //             'msvc' => $validatedData['msvc'],
    //             'email' => $validatedData['email'],
    //             'password' => Hash::make($validatedData['password']),
    //             'is_superadmin' => $validatedData['is_superadmin'],
    //             // Các trường khác có thể set default hoặc null trong DB
    //         ]);

    //         // Gán quyền chi tiết nếu không phải là super admin và có quyền được chọn
    //         if (!$user->is_superadmin && !empty($validatedData['permissions'])) {
    //             // Lấy IDs của các permissions dựa trên ma_quyen được gửi lên
    //             $permissionIds = Permission::whereIn('ma_quyen', $validatedData['permissions'])->pluck('id');
    //             // Gán permissions cho user mới tạo bằng sync (an toàn hơn attach)
    //             $user->permissions()->sync($permissionIds);
    //         }

    //         // Commit transaction nếu mọi thứ thành công
    //         DB::commit();

    //         // Load lại thông tin user vừa tạo để trả về (nếu cần)
    //         // $user->load('permissions'); // Không cần load permissions về list view

    //         // Chỉ trả về thông tin cơ bản cần thiết cho list view
    //         $userResponse = $user->only(['id', 'hoTen', 'msvc', 'email', 'is_superadmin']);

    //         return response()->json($userResponse, 201);

    //     } catch (\Exception $e) {
    //         // Rollback transaction nếu có lỗi
    //         DB::rollBack();
    //         // Log lỗi và trả về response lỗi
    //         \Log::error('Error creating user: ' . $e->getMessage());
    //         return response()->json(['message' => 'Đã xảy ra lỗi khi tạo người dùng.'], 500);
    //     }
    // }

    //  /**
    //  * Update the user's permissions and superadmin status.
    //  * PUT /api/admin/users/{user}/sync-permissions
    //  */
    

    // // Đảm bảo API lấy danh sách permissions tồn tại
    // /**
    //  * Get all available permissions.
    //  * GET /api/admin/permissions
    //  */
    public function getPermissions()
    {
        $permissions = Permission::select('id', 'ma_quyen', 'mo_ta')->get();
        return response()->json($permissions);
    }

    public function getUserPermissions(User $user)
    {
        // Lấy danh sách các mã quyền (ma_quyen)
        $permissionCodes = $user->permissions()->pluck('permissions.ma_quyen')->toArray();
        
        // Trả về is_superadmin và danh sách mã quyền
        return response()->json([
            'msvc' => $user->msvc, // Thêm msvc để dễ nhận diện user
            'is_superadmin' => (bool) $user->is_superadmin,
            'permission_codes' => $permissionCodes,
        ]);
    }

    //  /**
    //  * Get permissions for a specific user.
    //  * GET /api/admin/users/{user}/permissions
    //  */
    // public function getUserPermissions(User $user)
    // {
    //     // Chỉ lấy id và ma_quyen để giảm payload
    //     $permissions = $user->permissions()->select('permissions.id', 'permissions.ma_quyen')->get();
    //     return response()->json($permissions);
    // }

    public function getHocHam()
    {
        $hocHam = HocHam::select('id', 'ten')->get();
        return response()->json($hocHam);
    }

    public function getHocVi()
    {
        $hocVi = HocVi::select('id', 'ten')->get(); 
        return response()->json($hocVi);
    }

    public function getDonVi()
    {
        $donVi = DonVi::select('id', 'ten', 'parent_id')->get();
        return response()->json($donVi);
    }

    public function updateUser(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Tài Khoản')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ho_ten' => 'required|string|max:255',
            // Validate email uniqueness, ignoring the current user's email
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'sdt' => 'nullable|string|max:20', // Điều chỉnh max length nếu cần
            'dob' => 'nullable|date_format:Y-m-d', // Validate date format
            'don_vi_id' => 'nullable|integer|exists:don_vi,id', // Validate existence in don_vi table
            'hoc_ham_id' => 'nullable|integer|exists:hoc_ham,id', // Validate existence in hoc_ham table
            'hoc_vi_id' => 'nullable|integer|exists:hoc_vi,id', // Validate existence in hoc_vi table
            // Password validation: only required if provided, must be confirmed
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Chỉ cập nhật password nếu nó được cung cấp trong request
        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            // Loại bỏ key password khỏi mảng nếu không được cung cấp để không ghi đè password hiện tại bằng null
            unset($validatedData['password']);
        }

        // Cập nhật thông tin user
        $user->update($validatedData);

        // Trả về thông tin user đã cập nhật (loại bỏ password)
        return response()->json($user->refresh()->makeHidden('password'), 200); // refresh() để lấy dữ liệu mới nhất
    }    

    public function syncPermissions(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Tài Khoản')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'is_superadmin' => 'required|boolean',
            // 'permissions' phải tồn tại và là một mảng (có thể rỗng)
            'permissions' => 'present|array',
            // Mỗi phần tử trong mảng 'permissions' phải là string
            // và tồn tại trong cột 'mo_ta' của bảng 'permissions'
            'permissions.*' => 'string|exists:permissions,ma_quyen',
        ]);

        // Cập nhật trạng thái is_superadmin
        $user->is_superadmin = $validatedData['is_superadmin'];
        $user->save(); // Lưu thay đổi is_superadmin

        // Đồng bộ hóa permissions
        // Nếu user là superadmin, ta sẽ xóa hết các quyền cụ thể (sync với mảng rỗng)
        // Nếu không phải superadmin, ta sẽ sync với danh sách ID quyền được cung cấp
        if ($user->is_superadmin) {
            $user->permissions()->sync([]); // Xóa hết quyền cụ thể nếu là superadmin
        } else {
            // Lấy ID của các permissions dựa trên tên (mo_ta) được gửi lên
            // Sửa 'ma_quyen' thành 'mo_ta' để khớp với validation và payload
            $permissionIds = Permission::whereIn('ma_quyen', $validatedData['permissions'])->pluck('id')->toArray();
            // Gán các quyền cụ thể bằng danh sách ID đã lấy được
            $user->permissions()->sync($permissionIds);
        }

        // Trả về thông tin user đã cập nhật và danh sách quyền mới (nếu cần)
        $user->load('permissions'); // Load lại quan hệ permissions sau khi sync

        return response()->json([
            'message' => 'Cập nhật quyền thành công!',
            'user' => $user->only(['id', 'msvc', 'ho_ten', 'is_superadmin']),
            'permissions' => $user->permissions->pluck('id') // Trả về danh sách ID quyền mới
        ], 200);
    }

    /**
     * Store a newly created user in storage.
     * POST /api/admin/users/add
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUser(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Tài Khoản')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ho_ten' => 'required|string|max:255',
            'msvc' => 'required|string|max:100|unique:users,msvc', // Đảm bảo msvc là duy nhất
            'email' => 'required|string|email|max:255|unique:users,email', // Đảm bảo email là duy nhất
            'sdt' => 'nullable|string|max:20',
            'dob' => 'nullable|date_format:Y-m-d',
            'don_vi_id' => 'nullable|integer|exists:don_vi,id',
            'hoc_ham_id' => 'nullable|integer|exists:hoc_ham,id',
            'hoc_vi_id' => 'nullable|integer|exists:hoc_vi,id',
            'password' => ['required', 'confirmed', Password::defaults()], // Sử dụng quy tắc password mặc định và yêu cầu confirmation
            'is_superadmin' => 'required|boolean',
            'permissions' => 'present|array', // Phải có key 'permissions', giá trị là mảng (có thể rỗng)
            'permissions.*' => 'string|exists:permissions,ma_quyen' // Mỗi item trong mảng phải là string và tồn tại trong bảng permissions (cột ma_quyen)
        ]);

        // Bắt đầu transaction để đảm bảo tính toàn vẹn
        DB::beginTransaction();
        try {
            // Tạo user mới
            $user = User::create([
                'ho_ten' => $validatedData['ho_ten'], // Đổi key thành hoTen để khớp với $fillable
                'msvc' => $validatedData['msvc'],
                'email' => $validatedData['email'],
                'sdt' => $validatedData['sdt'],
                'dob' => $validatedData['dob'], // Thêm dob vào mảng create
                'don_vi_id' => $validatedData['don_vi_id'],
                'hoc_ham_id' => $validatedData['hoc_ham_id'],
                'hoc_vi_id' => $validatedData['hoc_vi_id'],
                'password' => Hash::make($validatedData['password']),
                'is_superadmin' => $validatedData['is_superadmin'],
            ]);

            // Gán quyền chi tiết nếu không phải là super admin và có quyền được chọn
            if (!$user->is_superadmin && !empty($validatedData['permissions'])) {
                // Lấy IDs của các permissions dựa trên ma_quyen được gửi lên
                $permissionIds = Permission::whereIn('ma_quyen', $validatedData['permissions'])->pluck('id');
                // Gán permissions cho user mới tạo bằng sync
                $user->permissions()->sync($permissionIds);
            }

            // Commit transaction nếu mọi thứ thành công
            DB::commit();

            // Trả về thông tin user vừa tạo (loại bỏ password)
            return response()->json($user->makeHidden('password'), 201); // 201 Created
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();
            // Log lỗi và trả về response lỗi
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi tạo người dùng.', 'error' => $e->getMessage()], 500);
        }
    }


    // quản lý khai báo ( đơn vị, cấp nhiệm vụ, lĩnh vực nghiên cứu)
    public function updateUnit(Request $request, DonVi $donVi)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ten' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:don_vi,id', // Kiểm tra parent_id có tồn tại trong bảng don_vi
        ]);

        // Cập nhật thông tin đơn vị
        $donVi->update($validatedData);

        // Trả về thông tin đơn vị đã cập nhật
        return response()->json($donVi, 200); // 200 OK
    }
    
    public function addUnit(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ten' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:don_vi,id', // Kiểm tra parent_id có tồn tại trong bảng don_vi
        ]);

        // Tạo đơn vị mới
        $donVi = DonVi::create($validatedData);

        // Trả về thông tin đơn vị đã tạo
        return response()->json($donVi, 201); // 201 Created
    }
    public function deleteUnit(DonVi $donVi)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Xóa đơn vị
        $donVi->delete();

        // Trả về thông báo thành công
        return response()->json(['message' => 'Đơn vị đã được xóa thành công.'], 200); // 200 OK
    }
    public function getDonViPagination(Request $request) // Add Request $request
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Define a default number of items per page, allow overriding via query parameter
        $perPage = $request->query('per_page', 15); // Default to 15 items per page
        $searchTerm = $request->query('search'); // Get the search term for 'ten'
        $parentId = $request->query('parent_id'); // Get the parent_id filter

        // Start building the query, alias the main table as 'd'
        $query = DonVi::query()
            ->from('don_vi as d') // Alias the main table
            ->select('d.id', 'd.ten', 'd.parent_id', 'p.ten as parent_ten') // Select columns from aliased tables
            ->leftJoin('don_vi as p', 'd.parent_id', '=', 'p.id'); // Join with parent table (aliased as 'p')

        // Add search condition for 'ten' if a search term is provided
        if ($searchTerm) {
            // Use 'ILIKE' for case-insensitive search in PostgreSQL
            // Use 'LIKE' for case-insensitive search in MySQL (by default) or SQLite
            // Adjust the operator if your database is different
            // Use the alias 'd' for the column
            $query->where('d.ten', 'ILIKE', "%{$searchTerm}%");
        }

        // Add filter condition for 'parent_id' if provided
        if ($request->filled('parent_id')) { // Check if parent_id is present and not empty
            // Use the alias 'd' for the column
            if (strtolower($parentId) === 'null' || $parentId === '0') {
                 // Allow filtering for root units (where parent_id is NULL)
                $query->whereNull('d.parent_id'); // Corrected to use alias 'd'
            } elseif (filter_var($parentId, FILTER_VALIDATE_INT)) {
                // Filter by a specific parent ID if it's a valid integer
                $query->where('d.parent_id', $parentId); // Corrected to use alias 'd'
            }
            // If parent_id is provided but not 'null', '0', or a valid integer, it will be ignored
            // You might want to add validation or error handling here if needed.
        }

        // Use paginate() instead of get()
        $donVi = $query->paginate($perPage); // Pass the number of items per page, applies to the main query

        // Laravel's paginate method automatically structures the response for pagination
        return response()->json($donVi);
    }

    public function getCapNhiemVu()
    {
        $capNhiemVu = CapNhiemVu::all(); // Get all CapNhiemVu records
        return response()->json($capNhiemVu);
    }

    public function getCapNhiemVuPagination(Request $request) // Add Request parameter
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Define a default number of items per page, allow overriding via query parameter
        $perPage = $request->query('per_page', 15); // Default to 10 items per page
        $searchTerm = $request->query('search'); // Get the search term for 'ten'
        $parentId = $request->query('parent_id'); // Get the parent_id filter

        // Start building the query, alias the main table as 'cnv'
        $query = CapNhiemVu::query()
            ->from('cap_nhiem_vu as cnv') // Alias the main table
            // Select columns including parent's name
            ->select('cnv.id', 'cnv.ten', 'cnv.kinh_phi', 'cnv.parent_id', 'p.ten as parent_ten')
            // Join with parent table (aliased as 'p')
            ->leftJoin('cap_nhiem_vu as p', 'cnv.parent_id', '=', 'p.id');

        // Add search condition for 'ten' if a search term is provided
        if ($searchTerm) {
            // Use 'ILIKE' for case-insensitive search in PostgreSQL
            // Use 'LIKE' for case-insensitive search in MySQL (by default) or SQLite
            $query->where('cnv.ten', 'ILIKE', "%{$searchTerm}%");
        }

        // Add filter condition for 'parent_id' if provided
        if ($request->filled('parent_id')) { // Check if parent_id is present and not empty
            if (strtolower($parentId) === 'null' || $parentId === '0') {
                 // Allow filtering for root items (where parent_id is NULL)
                $query->whereNull('cnv.parent_id');
            } elseif (filter_var($parentId, FILTER_VALIDATE_INT)) {
                // Filter by a specific parent ID if it's a valid integer
                $query->where('cnv.parent_id', $parentId);
            }
        }

        // Use paginate()
        $capNhiemVu = $query->paginate($perPage);
        return response()->json($capNhiemVu);
    }
    public function updateCapNhiemVu(Request $request, CapNhiemVu $capNhiemVu)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ten' => 'required|string|max:255',
            'kinh_phi' => 'nullable|numeric', // Validate kinh_phi as numeric
            'parent_id' => 'nullable|integer|exists:cap_nhiem_vu,id', // Validate parent_id exists in cap_nhiem_vu table
        ]);

        // Cập nhật thông tin cấp nhiệm vụ
        $capNhiemVu->update($validatedData);

        // Trả về thông tin cấp nhiệm vụ đã cập nhật
        return response()->json($capNhiemVu, 200); // 200 OK
    }
    public function addCapNhiemVu(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ten' => 'required|string|max:255',
            'kinh_phi' => 'nullable|numeric', // Validate kinh_phi as numeric
            'parent_id' => 'nullable|integer|exists:cap_nhiem_vu,id', // Validate parent_id exists in cap_nhiem_vu table
        ]);

        // Tạo cấp nhiệm vụ mới
        $capNhiemVu = CapNhiemVu::create($validatedData);

        // Trả về thông tin cấp nhiệm vụ đã tạo
        return response()->json($capNhiemVu, 201); // 201 Created
    }

    public function deleteCapNhiemVu(CapNhiemVu $capNhiemVu)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Xóa cấp nhiệm vụ
        $capNhiemVu->delete();

        // Trả về thông báo thành công
        return response()->json(['message' => 'Cấp nhiệm vụ đã được xóa thành công.'], 200); // 200 OK
    }

    // public function getLVCN()
    // {
    //     $linhVucCN = LinhVucNghienCuu::all(); // Get all LinhVucNghienCuu records
    //     return response()->json($linhVucCN);
    // }
        public function getLinhVucNghienCuuPagination(Request $request) // Renamed this method
        {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
                return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
            }

            // Define a default number of items per page, allow overriding via query parameter
            $perPage = $request->query('per_page', 15); // Default to 10 items per page
            $searchTerm = $request->query('search'); // Get the search term for 'ten'

            // Start building the query
            $query = LinhVucNghienCuu::query()
                ->select('id', 'ten');

            // Add search condition for 'ten' if a search term is provided
            if ($searchTerm) {
                // Use 'ILIKE' for case-insensitive search in PostgreSQL
                $query->where('ten', 'ILIKE', "%{$searchTerm}%");
            }

            // Use paginate()
            $linhVucCN = $query->paginate($perPage);
            return response()->json($linhVucCN);
        }

        // This method should UPDATE an existing record
        public function updateLinhVucNghienCuu(Request $request, LinhVucNghienCuu $linhVucNghienCuu) // Renamed variable
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        $validatedData = $request->validate([
            'ten' => 'required|string|max:100'
        ]);
        
        Log::info('Updating LinhVucNghienCuu ID: ' . $linhVucNghienCuu->id . ' with data: ', $validatedData);
        Log::info('Model state BEFORE update: ', $linhVucNghienCuu->toArray());

        // --- Option 1: Using update() with return value check ---
        $updateResult = $linhVucNghienCuu->update($validatedData); // Use renamed variable
        Log::info('Result of update() method: ' . ($updateResult ? 'true' : 'false'));

        // --- Option 2: Using attribute assignment and save() ---
        // Comment out Option 1 and uncomment below to try this alternative
        // $LVNC->ten = $validatedData['ten'];
        // $saveResult = $LVNC->save();
        // \Log::info('Result of save() method: ' . ($saveResult ? 'true' : 'false'));

        // Refresh the model instance to get the latest data from the database
        $linhVucNghienCuu->refresh(); // Use renamed variable
        Log::info('Model state AFTER refresh: ', $linhVucNghienCuu->toArray());
        
        // Trả về thông tin cấp nhiệm vụ đã cập nhật
        return response()->json($linhVucNghienCuu, 200); // Use renamed variable
    }
        // This method should ADD a new record
        // In c:\Users\maing\OneDrive\Documents\KLTN\project\BE\QLNCKH\app\Http\Controllers\AdminController.php
        public function addLinhVucNghienCuu(Request $request)
        {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
                return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
            }

            $validatedData = $request->validate([
                'ten' => 'required|string|max:255',
            ]);
            // create() lets the DB handle the ID
            $linhVucCN = LinhVucNghienCuu::create($validatedData);
            return response()->json($linhVucCN, 201);
        }

        public function deleteLinhVucNghienCuu(LinhVucNghienCuu $linhVucNghienCuu) // Renamed variable
        {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Khai Báo')->exists()) {
                return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
            }

            // Xóa lĩnh vực nghiên cứu
            $linhVucNghienCuu->delete(); // Use renamed variable

            // Trả về thông báo thành công
            return response()->json(['message' => 'Lĩnh vực nghiên cứu đã được xóa thành công.'], 200); // 200 OK
        }

        // quản lý đề tài 
        public function getAllLinhVucNghienCuu()
        {
            $linhVuc = LinhVucNghienCuu::select('id', 'ten')->orderBy('ten')->get();
            return response()->json($linhVuc);
        }

        // Lấy danh sách tất cả Trạng thái Đề tài cho dropdown
        public function getAllTrangThaiDeTai()
        {
            $trangThai = TrangThaiDeTai::select('id', 'ten_hien_thi')->get();
            return response()->json($trangThai);
        }

        // Lấy danh sách tất cả các mốc Tiến độ cho dropdown
        public function getAllTienDo()
        {
            $tienDo = TienDo::select('id', 'ten_moc')->orderBy('thu_tu')->get();
            return response()->json($tienDo);
        }

        public function getListTienDoDetaiPagination(Request $request){
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Tiến Độ Đề Tài')->exists()) {
                return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
            }

            $perPage = 16; // Number of items per page

            // Bắt đầu query và tải các mối quan hệ (eager loading)
            // Sử dụng tên các phương thức quan hệ đã sửa trong model DeTai
            $query = DeTai::query()->with([
                // Tải các model liên quan và chỉ chọn các cột cần thiết để tối ưu
                'trangThai:id,ten_hien_thi', // Lấy tên trạng thái (TrangThaiDeTai model)
                'admin:id,ho_ten',          // Lấy tên admin duyệt (User model)
                'linhVucNghienCuu:id,ten', // Lấy tên lĩnh vực (LinhVucNghienCuu model)
                'capNhiemVu:id,ten',       // Lấy tên cấp nhiệm vụ (CapNhiemVu model)
                'chuTri:id,ten',           // Lấy thông tin đơn vị chủ trì (DonVi model)
                'chuQuan:id,ten',          // Lấy tên đơn vị chủ quản (DonVi model) - Giữ nguyên vì đã đúng
                // Tải các mốc tiến độ ('TienDo') và dữ liệu từ bảng trung gian ('de_tai_tien_do').
                // Quan hệ 'tienDo' đã bao gồm sắp xếp và pivot data.
                'tienDo', // Loads TienDo model + pivot data ('id', 'mo_ta', 'thoi_gian_nop')
                // Bỏ 'giangVienDangKy', 'baiBao', 'taiLieu' nếu không cần thiết
                // Chỉ tải giảng viên tham gia có vai trò là chủ nhiệm (vai_tro_id = 1)
                'giangVienThamGia' => function ($query) { // Sẽ lấy tất cả giảng viên tham gia
                    $query->select('users.id', 'users.ho_ten', 'users.msvc') // Select columns from users table
                          // Bỏ ->wherePivot('vai_tro_id', '=', 1) để lấy tất cả thành viên
                          ->withPivot('vai_tro_id', 'can_edit', 'join_at'); // Vẫn tải các cột pivot cần thiết
                },
            ])
            // Loại trừ các đề tài có trạng thái "Chờ duyệt" (ID = 1)
            ->where('de_tai.trang_thai_id', '!=', 1);

            // --- Thêm Tìm kiếm và Lọc ---

            // 1. Tìm kiếm theo Từ khóa chung (Tên, Mã đề tài)
            if ($request->filled('search_keyword')) {
                $keyword = $request->input('search_keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('ten_de_tai', 'ILIKE', "%{$keyword}%")
                      ->orWhere('ma_de_tai', 'ILIKE', "%{$keyword}%");
                });
            }

            // 2. Lọc theo Trạng thái (trang_thai_id)
            if ($request->filled('trang_thai_id')) {
                // Nếu người dùng lọc theo một trang_thai_id cụ thể,
                // điều kiện này sẽ được thêm vào. Nếu họ lọc theo trang_thai_id = 1,
                // kết quả sẽ rỗng do điều kiện loại trừ ở trên.
                $query->where('de_tai.trang_thai_id', $request->input('trang_thai_id'));
            }

            // 3. Lọc theo Lĩnh vực nghiên cứu (lvnc_id)
            if ($request->filled('lvnc_id')) {
                $query->where('lvnc_id', $request->input('lvnc_id'));
            }

            // 4. Lọc theo Cấp nhiệm vụ (cnv_id)
            if ($request->filled('cnv_id')) {
                $query->where('cnv_id', $request->input('cnv_id'));
            }

            // 5. Lọc theo Tiến độ (kiểm tra sự tồn tại của một tien_do_id cụ thể)
            if ($request->filled('tien_do_id')) {
                $tienDoId = $request->input('tien_do_id');
                $query->whereHas('tienDo', function ($q) use ($tienDoId) {
                    $q->where('tien_do.id', $tienDoId); // Lọc theo ID của mốc tiến độ
                });
            }

            // 6a. Lọc theo Đơn vị chủ trì (chu_tri_id)
            if ($request->filled('chu_tri_id')) {
                $query->where('chu_tri_id', $request->input('chu_tri_id'));
            }
            // 6b. Lọc theo Đơn vị chủ quản (chu_quan_id)
            if ($request->filled('chu_quan_id')) {
                $query->where('chu_quan_id', $request->input('chu_quan_id'));
            }

            // 7. Lọc/Tìm kiếm theo Chủ nhiệm đề tài (Giảng viên - ho_ten hoặc msvc)
            if ($request->filled('chu_nhiem_keyword')) {
                $cnKeyword = $request->input('chu_nhiem_keyword');
                $query->whereHas('giangVienThamGia', function ($q) use ($cnKeyword) {
                    $q->where('tham_gia.vai_tro_id', 1) // Chỉ tìm trong vai trò chủ nhiệm
                      ->where(function ($userQuery) use ($cnKeyword) {
                          $userQuery->where('users.ho_ten', 'ILIKE', "%{$cnKeyword}%")
                                    ->orWhere('users.msvc', 'ILIKE', "%{$cnKeyword}%");
                      });
                });
            }
            // --- Kết thúc Tìm kiếm và Lọc ---

            // Paginate the results
            $detai = $query->paginate($perPage);

            // --- Bổ sung: Lấy tên vai trò cho giảng viên tham gia ---
            // 1. Thu thập tất cả vai_tro_id từ kết quả đã phân trang
            $vaiTroIds = $detai->pluck('giangVienThamGia.*.pivot.vai_tro_id') // Lấy vai_tro_id từ pivot
                              ->flatten() // Làm phẳng mảng đa cấp
                              ->unique() // Lấy các ID duy nhất
                              ->filter(); // Loại bỏ các giá trị null hoặc rỗng

            // 2. Truy vấn tên vai trò tương ứng
            $vaiTroMap = VaiTro::whereIn('id', $vaiTroIds)->pluck('ten_vai_tro', 'id');

            // 3. Gắn tên vai trò vào từng pivot của giangVienThamGia
            $detai->getCollection()->transform(function ($item) use ($vaiTroMap) {
                $item->giangVienThamGia->each(function ($gv) use ($vaiTroMap) {
                    // Thêm thuộc tính 'ten_vai_tro' vào đối tượng pivot
                    $gv->pivot->ten_vai_tro = $vaiTroMap[$gv->pivot->vai_tro_id] ?? 'Không xác định';
                    // Thêm thuộc tính 'is_chu_nhiem' vào đối tượng giảng viên (User)
                    $gv->is_chu_nhiem = ($gv->pivot->vai_tro_id == 1);
                });
                return $item;
            });
            // --- Kết thúc bổ sung ---

            // Return the paginated data as JSON
            return response()->json($detai);
        }


    public function updateTienDoDeTai(Request $request, DeTai $deTai)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản Lý Tiến Độ Đề Tài')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // 1. Validate the incoming request
        $validatedData = $request->validate([
            'tien_do_id' => 'required|integer|exists:tien_do,id', // Ensure the milestone exists
            'mo_ta' => 'nullable|string|max:1000', // Optional description for this update
        ]);

        // Use a database transaction for atomicity
        DB::beginTransaction();
        try {
            // 2. Mark all existing progress entries for this DeTai as not current
            DeTaiTienDo::where('de_tai_id', $deTai->ma_de_tai)
                       ->update(['is_present' => false]);

            // 3. Attach the new progress milestone, marking it as current
            // The 'attach' method works on the belongsToMany relationship
            // It adds a new record to the pivot table 'de_tai_tien_do'
            $deTai->tienDo()->attach($validatedData['tien_do_id'], [
                'mo_ta' => $validatedData['mo_ta'] ?? null, // Use provided description or null
                'is_present' => true, // Mark this new entry as the current one
                // 'created_at' will be set automatically by the database default
            ]);

                // If the new progress milestone ID is 7, update DeTai's trang_thai_id to 3
                if ($validatedData['tien_do_id'] == 7) {
                    $deTai->trang_thai_id = 3; // Assuming 3 means "Đã hoàn thành" or similar
                    $deTai->save();
                }

            // Commit the transaction if everything was successful
            DB::commit();

            // Return a success response
            // Optionally, you could return the updated DeTai with its latest progress
            // $deTai->load('tienDo'); // Reload the relationship if needed
            return response()->json(['message' => 'Cập nhật tiến độ đề tài thành công.'], 200);

        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();
            Log::error('Error updating DeTai progress: ' . $e->getMessage()); // Log the error
            return response()->json(['message' => 'Đã xảy ra lỗi khi cập nhật tiến độ.', 'error' => $e->getMessage()], 500);
        }
    }
        
    //quên mật khẩu

    //xác thực gửi otp qua mail (4-6 random)

    // Trong AdminController.php hoặc một controller quản lý notifications
public function getNotifications(Request $request)
{
    $user = $request->user(); // Admin đang đăng nhập
    $notifications = Notification::where('notifiable_id', $user->id)
                                ->where('notifiable_type', User::class)
                                ->orderBy('created_at', 'desc')
                                ->take(10) // Lấy 10 thông báo mới nhất chẳng hạn
                                ->get();
    // Có thể thêm logic đếm số thông báo chưa đọc
    $unreadCount = Notification::where('notifiable_id', $user->id)
                               ->where('notifiable_type', User::class)
                               ->whereNull('read_at')
                               ->count();

    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);
}


public function markAsRead(Request $request, $notificationId)
{
    $user = $request->user();
    $notification = Notification::where('id', $notificationId)
                                ->where('notifiable_id', $user->id)
                                ->where('notifiable_type', User::class)
                                ->first();
    if ($notification && is_null($notification->read_at)) {
        $notification->read_at = now();
        $notification->save();
        return response()->json(['message' => 'Notification marked as read.']);
    }
    return response()->json(['message' => 'Notification not found or already read.'], 404);
}
 
public function getListDeTaiXetDuyet(Request $request){
    /** @var \App\Models\User $currentUser */
    $currentUser = Auth::user();
    if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Duyệt Đề Tài')->exists()) {
        return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
    }

    $perPage = $request->input('per_page', 15); // Mặc định 15 item mỗi trang, có thể tùy chỉnh qua query param

    $query = DeTai::query()
        ->where('trang_thai_id', 1) // Chỉ lấy các đề tài có trạng thái "Chờ duyệt" (ID = 1)
        ->with([ // Tải trước các mối quan hệ cần thiết để hiển thị
            'linhVucNghienCuu:id,ten',
            'capNhiemVu:id,ten',
            'chuTri:id,ten',
            'chuQuan:id,ten',
            'msvcGvdkUser:id,ho_ten,msvc', // Giảng viên đăng ký đề tài
            'giangVienThamGia' => function ($q) { // Lấy tất cả thành viên tham gia
                $q->select('users.id', 'users.ho_ten', 'users.msvc')
                  ->withPivot('vai_tro_id'); // Tải vai_tro_id từ bảng pivot
            }
        ])
        ->when($request->filled('search_keyword'), function ($query) use ($request) {
            $keyword = $request->input('search_keyword');
            // Tìm kiếm trong tên đề tài hoặc mã đề tài (nếu có)
            // hoặc tên giảng viên đăng ký
            $query->where(function ($q) use ($keyword) {
                $q->where('ten_de_tai', 'ILIKE', "%{$keyword}%")
                //   ->orWhere('ma_de_tai', 'ILIKE', "%{$keyword}%") // Giả sử bạn có cột ma_de_tai
                  ->orWhereHas('msvcGvdkUser', function ($userQuery) use ($keyword) {
                      $userQuery->where('ho_ten', 'ILIKE', "%{$keyword}%")
                                ->orWhere('msvc', 'ILIKE', "%{$keyword}%"); // Thêm tìm kiếm theo msvc
                  });
            });
        })
        ->orderBy('created_at', 'desc'); // Sắp xếp theo ngày tạo mới nhất

    $deTaiPaginated = $query->paginate($perPage);

    // Bạn có thể thêm logic để lấy tên vai trò cho giangVienThamGia nếu cần, tương tự như hàm getListTienDoDetaiPagination

    return response()->json($deTaiPaginated);
}

public function getVaiTro(){
    $vaiTro = VaiTro::select('id', 'ten_vai_tro')->get();
    return response()->json($vaiTro);
}

public function setDeTai(Request $request, DeTai $deTai){
    /** @var \App\Models\User $currentUser */
    $currentUser = Auth::user();
    if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Duyệt Đề Tài')->exists()) {
        return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
    }

    // Chỉ cho phép xét duyệt đề tài đang ở trạng thái "Chờ duyệt" (ID = 1)
    if ($deTai->trang_thai_id != 1) {
        return response()->json(['message' => 'Đề tài này không ở trạng thái chờ duyệt.'], 400);
    }

    $validatedData = $request->validate([
        'trang_thai_id' => 'required|integer|exists:trang_thai_de_tai,id',
        'ma_de_tai' => 'nullable|string|max:50|unique:de_tai,ma_de_tai,' . $deTai->id, // Bỏ qua chính đề tài này khi check unique
        'ghi_chu_xet_duyet' => 'nullable|string|max:1000', // Ghi chú của admin khi duyệt
        'ly_do_tu_choi' => 'required_if:trang_thai_id,4|nullable|string|max:1000', // Bắt buộc nếu từ chối (trang_thai_id = 4)
    ]);

    DB::beginTransaction();
    try {
        $deTai->trang_thai_id = $validatedData['trang_thai_id'];
        $deTai->admin_id = $currentUser->id; // Gán admin đã duyệt
        $deTai->thoi_gian_xet_duyet = now(); // Thời gian xét duyệt
        $deTai->nhan_xet = $validatedData['ly_do_tu_choi'] ?? null; // Ghi chú chung khi xét duyệt

        if ($validatedData['trang_thai_id'] == 2) { // Nếu là "Đã xác nhận" / "Đã duyệt"
            // Nếu admin không nhập mã đề tài, hoặc mã đề tài rỗng, thì không cập nhật
            if (!empty($validatedData['ma_de_tai'])) {
                $deTai->ma_de_tai = $validatedData['ma_de_tai'];
            }
            // Tự động tạo mốc tiến độ đầu tiên (ID = 1) cho đề tài này
            // Kiểm tra xem đã tồn tại mốc tiến độ này chưa để tránh duplicate nếu có logic khác
            if (!$deTai->tienDo()->where('tien_do.id', 1)->exists()) {
                $deTai->tienDo()->attach(1, [ // Giả sử ID 1 là mốc tiến độ ban đầu
                    'is_present' => true, // Đánh dấu đây là tiến độ hiện tại
                ]);
            }
            event(new DeTaiApproved($deTai, $currentUser));
            // Có thể thêm logic tạo mã tự động nếu $validatedData['ma_de_tai'] rỗng và bạn muốn
        } elseif ($validatedData['trang_thai_id'] == 4) { // Nếu là "Từ chối"
            // Ghi chú từ chối sẽ được lưu vào cột 'nhan_xet' hoặc một cột riêng nếu có
            // Hiện tại, đang dùng chung cột 'nhan_xet' và bổ sung lý do từ chối vào đó
            $deTai->nhan_xet = "Lý do từ chối: " . $validatedData['ly_do_tu_choi'] . 
                               ($validatedData['ly_do_tu_choi'] ? " (Ghi chú thêm: " . $validatedData['ly_do_tu_choi'] . ")" : "");

            event(new DeTaiRejected($deTai, $currentUser, $validatedData['ly_do_tu_choi']));
        }

        $deTai->save();
        DB::commit();
        return response()->json(['message' => 'Xét duyệt đề tài thành công.', 'de_tai' => $deTai->fresh()], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Lỗi khi xét duyệt đề tài ID ' . $deTai->id . ': ' . $e->getMessage());
        return response()->json(['message' => 'Đã xảy ra lỗi khi xét duyệt đề tài.', 'error' => $e->getMessage()], 500);
    }
}
        public function getAllBaiBao(Request $request){
        // Xác định số lượng mục trên mỗi trang, có thể được ghi đè bằng tham số 'per_page' từ request
        $perPage = $request->input('per_page', 15); // Mặc định 15 mục mỗi trang

        // Bắt đầu xây dựng query cho BaiBao
        $query = BaiBao::query();

        // Eager load mối quan hệ 'deTai'
        // Chọn các cột cụ thể từ bảng 'de_tai' liên quan để tối ưu hóa query
        // Giả sử model DeTai có 'ma_de_tai' (là owner key) và 'ten_de_tai'
        // Mở rộng để lấy thêm thông tin chi tiết từ DeTai và các model liên quan của nó
        $query->with([
            'deTai' => function ($q) {
            // Chọn các cột cần thiết từ bảng de_tai
            // Quan trọng: 'ma_de_tai' (owner key của relationship) phải được chọn
            $q->select(
                'de_tai.ma_de_tai',      // Khóa chính của de_tai, dùng cho join
                'de_tai.ten_de_tai',
                'de_tai.trang_thai_id',  // FK để lấy thông tin trạng thái
                'de_tai.msvc_gvdk',      // FK để lấy thông tin giảng viên chủ nhiệm
                'de_tai.lvnc_id',        // FK để lấy thông tin lĩnh vực nghiên cứu
                'de_tai.cnv_id',         // FK để lấy thông tin cấp nhiệm vụ
                'de_tai.ngay_bat_dau_dukien',
                'de_tai.ngay_ket_thuc_dukien'
                // Thêm các cột khác từ bảng de_tai nếu bạn cần
            )
            // Eager load các relationship của chính model DeTai
            ->with([
                'trangThai:id,ten_hien_thi',          // Lấy tên trạng thái từ TrangThaiDeTai
                'msvcGvdkUser:id,ho_ten,msvc',        // Lấy thông tin giảng viên đăng ký/chủ nhiệm từ User
                'linhVucNghienCuu:id,ten',            // Lấy tên lĩnh vực từ LinhVucNghienCuu
                'capNhiemVu:id,ten'                   // Lấy tên cấp nhiệm vụ từ CapNhiemVu
            ]);
        },
        'taiLieu' => function ($q) {
            // Chọn các cột cần thiết từ bảng tai_lieu
            // 'bai_bao_id' là foreign key, cần thiết để Laravel khớp nối
            $q->select('id', 'bai_bao_id', 'file_path', 'mo_ta', 'created_at');
        }
        ]);
        // Thêm sắp xếp, ví dụ: theo ngày tạo hoặc ngày xuất bản
        // $query->orderBy('ngay_xuat_ban', 'desc'); // Sắp xếp theo ngày xuất bản mới nhất
        $query->orderBy('created_at', 'desc'); // Hoặc sắp xếp theo ngày tạo mới nhất

        // Phân trang kết quả
        $baiBaos = $query->paginate($perPage);

        // Trả về dữ liệu đã phân trang dưới dạng JSON
        return response()->json($baiBaos);
    }

    public function test(){
        return response()->json('test');
    }

    public function getArticleDetail(BaiBao $baiBao)
    {
        try {
            // Eager load relationships you might need.
            // Adjust these based on your BaiBao model's relationships and what you want to return.
            $baiBao->load(['deTai', 'taiLieu', 'nguoiNop', 'admin_xet_duyet']); // 'nguoiNop' assumes you have a relationship to the User model who submitted

            if (!$baiBao) { // Should be handled by route model binding, but an extra check doesn't hurt
                return response()->json(['message' => 'Không tìm thấy bài báo.'], 404);
            }

            return response()->json($baiBao, 200);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy chi tiết bài báo: ' . $e->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi lấy chi tiết bài báo.', 'error' => $e->getMessage()], 500);
        }
    }
     public function approveBaiBao(BaiBao $baiBao){
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Permission Check: Ensure the admin has the right to approve articles.
        // Adjust 'Duyệt Bài Báo' if your permission code is different.
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản lý sản phẩm')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Check if the article is in a state that can be approved.
        // Assuming 'Chờ duyệt' is the status for pending articles.
        // Adjust this if your status value is different (e.g., an ID or a different string).
        if ($baiBao->trang_thai !== 'chờ duyệt') {
            return response()->json(['message' => 'Bài báo này không ở trạng thái chờ duyệt hoặc đã được xử lý.'], 400);
        }

        DB::beginTransaction();
        try {
            $baiBao->trang_thai = 'đã duyệt'; // Set the new status. Adjust if needed.
            $baiBao->admin_msvc = $currentUser->msvc; // Record which admin approved it.
            // If you have a 'thoi_gian_xet_duyet' column or similar for BaiBao:
            // $baiBao->thoi_gian_xet_duyet = now();
            $baiBao->save();

            DB::commit();
            event(new BaiBaoApproved($baiBao, $currentUser));
            // Optionally, dispatch an event or send a notification to the lecturer.
            // event(new BaiBaoApproved($baiBao, $currentUser));

            return response()->json(['message' => 'Bài báo đã được duyệt thành công.', 'bai_bao' => $baiBao->fresh()], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi duyệt bài báo ID ' . $baiBao->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi duyệt bài báo.', 'error' => $e->getMessage()], 500);
        }
     }

     public function rejectBaiBao(Request $request, BaiBao $baiBao){
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Permission Check: Ensure the admin has the right to reject articles.
        // Using the same permission as approve for now, adjust if needed.
        if (!$currentUser->is_superadmin && !$currentUser->permissions()->where('permissions.ma_quyen', 'Quản lý sản phẩm')->exists()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        // Check if the article is in a state that can be rejected.
        if ($baiBao->trang_thai !== 'chờ duyệt') {
            return response()->json(['message' => 'Bài báo này không ở trạng thái chờ duyệt hoặc đã được xử lý.'], 400);
        }

        $validatedData = $request->validate([
            'ly_do_tu_choi' => 'required|string|max:1000', // Reason for rejection
        ]);

        DB::beginTransaction();
        try {
            $baiBao->trang_thai = 'từ chối'; // Set the new status. Adjust if needed.
            $baiBao->admin_msvc = $currentUser->msvc; // Record which admin rejected it.
            $baiBao->nhan_xet = $validatedData['ly_do_tu_choi']; // Store the rejection reason.
            $baiBao->save();

            DB::commit();
            event(new BaiBaoRejected($baiBao->fresh(), $currentUser, $validatedData['ly_do_tu_choi']));
            // Optionally, dispatch an event or send a notification to the lecturer.
            // event(new BaiBaoRejected($baiBao, $currentUser, $validatedData['nhan_xet']));

            return response()->json(['message' => 'Bài báo đã được từ chối thành công.', 'bai_bao' => $baiBao->fresh()], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi từ chối bài báo ID ' . $baiBao->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi từ chối bài báo.', 'error' => $e->getMessage()], 500);
        }
     }
}
