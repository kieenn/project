<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // For transaction when updating user
use Illuminate\Support\Facades\Log; // For logging errors
use Illuminate\Validation\Rules\Password; // For password validation rules
use App\Models\User; // Import the User model
use App\Models\UserPermission; // Import the UserPermission model (adjust namespace if needed)
use App\Mail\PasswordResetOtpMail; // Import the Mailable
use App\Models\CapNhiemVu;
use App\Models\VaiTro;
use App\Models\LinhVucNghienCuu;
use Carbon\Carbon; // Import Carbon for time comparison
use App\Models\HocHam;
use App\Models\HocVi;
use App\Models\DonVi;
use Illuminate\Validation\Rule; // Thêm Rule để validate email unique
use App\Models\DeTai; // Thêm model DeTai (hoặc tên model đề tài của bạn)
use App\Events\ResearchTopicSubmitted; // Thêm Event vừa tạo
use App\Events\BaiBaoSubmitted; // Thêm Event cho nộp bài báo
use App\Models\BaiBao; // Thêm model BaiBao
use App\Models\TaiLieu; // Thêm model TaiLieu
use Illuminate\Support\Facades\Storage; // Thêm Storage facade
use App\Models\TrangThaiDeTai;

class LecturerController extends Controller
{
    public function getLinhVuc(){
        $linhVuc = LinhVucNghienCuu::all();
        return response()->json($linhVuc);
    }

    public function getCapNhiemVu(){
        $capNhiemVu = CapNhiemVu::all();
        return response()->json($capNhiemVu);
    }

    public function getVaiTro(){
        $vaiTro = VaiTro::all();
        return response()->json($vaiTro);
    }

    public function findUsersByMsvc(Request $request)
    {
        $msvcQuery = $request->query('msvc');

        if (empty($msvcQuery)) {
            return response()->json(['message' => 'Query parameter "msvc" is required.'], 400);
        }

        // Search for users whose MSVC starts with the provided query string
        $query = User::where('msvc', 'ILIKE', $msvcQuery . '%')
                       ->select('msvc', 'ho_ten');

        $users = $query->get();

        return response()->json($users);
    }

    public function getHocHam(){
        $hocHam = HocHam::all();
        return response()->json($hocHam);
    }

    public function getHocVi(){
        $hocVi = HocVi::all();
        return response()->json($hocVi);
    }

    public function getDonVi(){
        $donVi = DonVi::all();
        return response()->json($donVi);
    }

    /**
     * Update the profile of the authenticated lecturer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy thông tin người dùng đang đăng nhập

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'ho_ten' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id), // Email phải là duy nhất, bỏ qua email hiện tại của user
            ],
            'sdt' => 'nullable|string|max:20',
            'dob' => 'nullable|date_format:Y-m-d', // Định dạng YYYY-MM-DD
            'hoc_ham_id' => 'nullable|integer|exists:hoc_ham,id',
            'hoc_vi_id' => 'nullable|integer|exists:hoc_vi,id',
            // Password chỉ validate nếu được cung cấp
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ]);

        // Chỉ cập nhật password nếu nó được cung cấp và không rỗng
        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']); // Bỏ qua cập nhật password nếu không được cung cấp
        }

        $user->update($validatedData);

        return response()->json(['message' => 'Cập nhật thông tin cá nhân thành công.', 'user' => $user->fresh()]);
    }

     /**
     * Lecturer submits a new research topic.
     * POST /lecturer/research-topics/submit
     */
    public function submitResearchTopic(Request $request)
    {
        $validatedData = $request->validate([
            'ten_de_tai' => 'required|string|max:255',
            'ma_de_tai_custom' => 'nullable|string|max:50|unique:de_tai,ma_de_tai', // Mã đề tài nếu giảng viên tự nhập
            'linh_vuc_id' => 'required|integer|exists:linh_vuc_nc,id',
            'cap_nhiem_vu_id' => 'required|integer|exists:cap_nhiem_vu,id',
            'chu_tri_id' => 'required|integer|exists:don_vi,id', // Thêm validation cho đơn vị chủ trì
            'chu_quan_id' => 'required|integer|exists:don_vi,id', // Thêm validation cho đơn vị chủ quản
            'loai_hinh_nghien_cuu' => 'required|string|max:255',
            'thoi_gian_bat_dau_du_kien' => 'required|date_format:Y-m-d',
            'thoi_gian_ket_thuc_du_kien' => 'required|date_format:Y-m-d|after_or_equal:thoi_gian_bat_dau_du_kien',
            'thoi_gian_thuc_hien' => 'required|integer|min:1', // Số tháng/ngày thực hiện
            'tong_kinh_phi_de_xuat' => 'required|numeric|min:0',
            // 'tong_kinh_phi_du_tru' => 'required|numeric|min:0', // Nếu cần lưu
            'tong_quan_van_de' => 'required|string',
            'tinh_cap_thiet' => 'required|string',
            'muc_tieu_nghien_cuu' => 'required|string',
            'doi_tuong' => 'required|string',
            'pham_vi' => 'required|string',
            'noi_dung_phuong_phap' => 'required|string',
            // 'san_pham_du_kien' => 'required|string', // Bỏ trường này
            'ghi_chu_de_xuat' => 'nullable|string',
            'thanh_vien_tham_gia' => 'required|array|min:1',
            'thanh_vien_tham_gia.*.giang_vien_id' => 'required|string|exists:users,msvc', // msvc của giảng viên
            'thanh_vien_tham_gia.*.vai_tro_id' => 'required|integer|exists:vai_tro,id',
            'thanh_vien_tham_gia.*.can_edit' => 'required|boolean',
            // Thêm validation cho các trường khác nếu có từ payload
            // 'mo_ta_de_tai' => 'required|string', // Nếu có trường này từ FE
        ]);

        /** @var \App\Models\User $lecturer */
        $lecturer = Auth::user();

        if (!$lecturer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        DB::beginTransaction();
        try {
            // Tạo đề tài mới
            $deTai = new DeTai([
                'ten_de_tai' => $validatedData['ten_de_tai'],
                'ma_de_tai' => $validatedData['ma_de_tai_custom'] ?: null, // Nếu rỗng thì để null
                'lvnc_id' => $validatedData['linh_vuc_id'],
                'cnv_id' => $validatedData['cap_nhiem_vu_id'],
                'chu_tri_id' => $validatedData['chu_tri_id'], // Lấy từ request
                'chu_quan_id' => $validatedData['chu_quan_id'], // Lấy từ request
                'loai_hinh_nghien_cuu' => $validatedData['loai_hinh_nghien_cuu'],
                'thoi_gian_nop' => $validatedData['thoi_gian_ket_thuc_du_kien'], // Ngày kết thúc dự kiến là thời gian nộp
                'thoi_gian_thuc_hien' => $validatedData['thoi_gian_thuc_hien'],
                'tong_kinh_phi' => $validatedData['tong_kinh_phi_de_xuat'],
                'tong_quan_van_de' => $validatedData['tong_quan_van_de'],
                'tinh_cap_thiet' => $validatedData['tinh_cap_thiet'],
                'muc_tieu_nghien_cuu' => $validatedData['muc_tieu_nghien_cuu'],
                'doi_tuong' => $validatedData['doi_tuong'],
                'pham_vi' => $validatedData['pham_vi'],
                'noi_dung_phuong_phap' => $validatedData['noi_dung_phuong_phap'],
                // 'san_pham_du_kien' => $validatedData['san_pham_du_kien'], // Bỏ trường này
                'ghi_chu' => $validatedData['ghi_chu_de_xuat'] ?? null,
                'ngay_bat_dau_dukien'=> $validatedData['thoi_gian_bat_dau_du_kien'],
                'ngay_ket_thuc_dukien' => $validatedData['thoi_gian_ket_thuc_du_kien'],

                // 'mo_ta_de_tai' => $validatedData['mo_ta_de_tai'] ?? null, // Nếu có
                // admin_id và thoi_gian_xet_duyet sẽ là null khi mới tạo, admin sẽ cập nhật sau
            ]);

            $deTai->trang_thai_id = 1; // Ví dụ: 1 là "Chờ duyệt" (Cần có bảng trang_thai_de_tai và model tương ứng)
            $deTai->msvc_gvdk = $lecturer->msvc; // Giảng viên đăng ký
            $deTai->save();

            // Logging để kiểm tra ID sau khi lưu
            if (!$deTai->id) {
                Log::error('DeTai ID is NULL after save. Model attributes: ', $deTai->getAttributes());
                // Ném Exception để dừng quá trình và rollback transaction
                throw new \Exception('Không thể lưu đề tài hoặc ID không được tạo.');
            } else {
                Log::info('DeTai saved successfully. ID: ' . $deTai->id);
            }

            // Tự động thêm người đăng ký (giảng viên hiện tại) làm chủ nhiệm đề tài
            // Giả sử vai trò "Chủ nhiệm" có ID là 1 (Cần xác định ID chính xác từ bảng vai_tro)
            $vaiTroChuNhiemId = 1; 
            $deTai->giangVienThamGia()->attach($lecturer->msvc, [
                'vai_tro_id' => $vaiTroChuNhiemId,
                'can_edit' => true, // Chủ nhiệm thường có quyền sửa
                'join_at' => now()
            ]);
            Log::info('Lecturer ' . $lecturer->msvc . ' attached as Chu Nhiem (Role ID: ' . $vaiTroChuNhiemId . ') to DeTai ID: ' . $deTai->id);

            // Thêm các thành viên khác từ request (nếu có)
            if (!empty($validatedData['thanh_vien_tham_gia'])) {
                foreach ($validatedData['thanh_vien_tham_gia'] as $thanhVien) {
                    // Chỉ thêm nếu thành viên này không phải là người đăng ký (đã được thêm làm chủ nhiệm)
                    // Hoặc nếu vai trò của thành viên này không phải là chủ nhiệm (nếu FE có thể gửi nhiều chủ nhiệm)
                    if ($thanhVien['giang_vien_id'] !== $lecturer->msvc || $thanhVien['vai_tro_id'] != $vaiTroChuNhiemId) {
                        $deTai->giangVienThamGia()->attach($thanhVien['giang_vien_id'], [ // giang_vien_id là msvc
                            'vai_tro_id' => $thanhVien['vai_tro_id'],
                            'can_edit' => $thanhVien['can_edit'],
                            'join_at' => now()
                        ]);
                        Log::info('Member ' . $thanhVien['giang_vien_id'] . ' attached with Role ID: ' . $thanhVien['vai_tro_id'] . ' to DeTai ID: ' . $deTai->id);
                    }
                }
            }

            // Dispatch event để thông báo cho admin
            event(new ResearchTopicSubmitted($deTai, $lecturer));

            DB::commit();
            return response()->json(['message' => 'Đăng ký đề tài thành công. Chờ duyệt.', 'de_tai' => $deTai->load('giangVienThamGia.vaiTro')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi đăng ký đề tài: ' . $e->getMessage() . ' Stack Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Đã xảy ra lỗi khi đăng ký đề tài.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllTrangThai(){
        $trangThai = TrangThaiDeTai::all();
        return response()->json($trangThai);
    }

    public function getAllDeTai(Request $request)
    {
        $user = Auth::user();
        $deTaiQuery = DeTai::query()
            ->where(function ($query) use ($user) {
                // Condition 1: User is the registrant (msvc_gvdk on de_tai table)
                $query->where('de_tai.msvc_gvdk', $user->msvc)
                      // Condition 2: User is a participant (via tham_gia pivot table)
                      ->orWhereHas('giangVienThamGia', function ($q) use ($user) {
                          // giangVienThamGia is a relationship on DeTai model, linking to User model
                          // It links de_tai.id to tham_gia.de_tai_id and users.msvc to tham_gia.msvc
                          $q->where('tham_gia.msvc', $user->msvc);
                      });
            })
            ->with([
                'trangThai:id,ten_hien_thi',
                'linhVucNghienCuu:id,ten',
                'capNhiemVu:id,ten',
                'chuTri:id,ten', // Đơn vị chủ trì (DonVi model)
                'chuQuan:id,ten', // Đơn vị chủ quản (DonVi model)
                // Eager load ALL participating members for each topic
                'giangVienThamGia' => function ($query) {
                    $query->select('users.id', 'users.ho_ten', 'users.msvc', 'users.email', 'users.sdt') // Select desired fields from users table
                          ->withPivot('vai_tro_id', 'can_edit', 'join_at'); // Load pivot data
                },
                // Eager load the "Chủ nhiệm" (main person responsible) for each topic
                'chuNhiemDeTai' => function ($query) {
                    $query->select('users.id', 'users.ho_ten', 'users.msvc'); // Select from users table
                },
                // Eager load progress information
                'tienDo' => function ($query) {
                    // Select specific fields from tien_do and pivot table
                    // Alias pivot columns to avoid name clashes if TienDo also has 'mo_ta' or 'created_at'
                    $query->select('tien_do.id', 'tien_do.ten_moc', 'tien_do.mo_ta as tien_do_description', 'tien_do.thu_tu')
                          ->withPivot('id as pivot_id', 'mo_ta as pivot_mo_ta', 'is_present', 'created_at as pivot_created_at')
                          ->orderBy('tien_do.thu_tu'); // Ensure order
                }
            ]);

        $perPage = $request->input('per_page', 15);
        $allDeTaiPaginated = $deTaiQuery->orderBy('de_tai.created_at', 'desc') // Qualify column name
                                     ->paginate($perPage);

        // Get all unique vai_tro_ids from ALL participations in the paginated results
        // to map them to role names efficiently.
        $vaiTroIdsInvolved = $allDeTaiPaginated->getCollection()
            ->flatMap(function ($deTaiItem) {
                return $deTaiItem->giangVienThamGia->pluck('pivot.vai_tro_id');
            })
            ->unique()
            ->filter()
            ->toArray();

        $vaiTroMap = [];
        if (!empty($vaiTroIdsInvolved)) {
            $vaiTroMap = VaiTro::whereIn('id', $vaiTroIdsInvolved)->pluck('ten_vai_tro', 'id');
        }

        // Collect msvc_gvdk values for DeTai items where chuNhiemDeTai is empty, to fetch User details later
        $msvcGvdkToFetch = $allDeTaiPaginated->getCollection()
            ->filter(function ($deTaiItem) {
                return $deTaiItem->chuNhiemDeTai->isEmpty() && !empty($deTaiItem->msvc_gvdk);
            })
            ->pluck('msvc_gvdk')
            ->unique()
            ->toArray();

        $msvcGvdkUserMap = [];
        if (!empty($msvcGvdkToFetch)) {
            $msvcGvdkUserMap = User::whereIn('msvc', $msvcGvdkToFetch)->pluck('ho_ten', 'msvc');
        }

        $allDeTaiPaginated->getCollection()->transform(function ($deTaiItem) use ($vaiTroMap, $user, $msvcGvdkUserMap) {
            // Add role name to each participating member
            $deTaiItem->giangVienThamGia->each(function ($gv) use ($vaiTroMap) {
                if ($gv->pivot) {
                    $gv->pivot->ten_vai_tro = $vaiTroMap[$gv->pivot->vai_tro_id] ?? 'Không xác định';
                }
            });

            // Determine current user's role information for this specific DeTai
            $currentUserParticipation = $deTaiItem->giangVienThamGia->firstWhere('msvc', $user->msvc);
            if ($currentUserParticipation && $currentUserParticipation->pivot) {
                $deTaiItem->current_user_vai_tro_id = $currentUserParticipation->pivot->vai_tro_id;
                // Use the ten_vai_tro already added to the pivot object
                $deTaiItem->current_user_vai_tro = $currentUserParticipation->pivot->ten_vai_tro;
                $deTaiItem->current_user_can_edit = (bool) $currentUserParticipation->pivot->can_edit;
            } elseif ($deTaiItem->msvc_gvdk === $user->msvc) {
                // Fallback: User is the registrant but not found in their filtered giangVienThamGia.
                // This might happen if the registration logic doesn't add them to tham_gia, or data inconsistency.
                $deTaiItem->current_user_vai_tro = 'Người đăng ký (Chủ nhiệm)'; // Assuming msvc_gvdk is always PI
                $deTaiItem->current_user_can_edit = false; // Default, as no specific role found in tham_gia
            }

            // Add "Chủ nhiệm" information
            if ($deTaiItem->chuNhiemDeTai->isNotEmpty()) {
                $chuNhiem = $deTaiItem->chuNhiemDeTai->first();
                $deTaiItem->chu_nhiem_info = [
                    'ho_ten' => $chuNhiem->ho_ten,
                    'msvc' => $chuNhiem->msvc,
                ];
            } elseif (!empty($deTaiItem->msvc_gvdk) && isset($msvcGvdkUserMap[$deTaiItem->msvc_gvdk])) {
                // Fallback if no explicit "Chủ nhiệm" (role 1) found, use msvc_gvdk
                $deTaiItem->chu_nhiem_info = [
                    'ho_ten' => $msvcGvdkUserMap[$deTaiItem->msvc_gvdk] ?? 'Không rõ (' . $deTaiItem->msvc_gvdk . ')',
                    'msvc' => $deTaiItem->msvc_gvdk,
                ];
            } else {
                $deTaiItem->chu_nhiem_info = null;
            }
            return $deTaiItem;
        });

        return response()->json($allDeTaiPaginated);
    }

    public function getDeTaiDetail(Request $request, DeTai $deTai)
    {
        $user = Auth::user();

        $deTai->load([
            'trangThai:id,ten_hien_thi',
            'linhVucNghienCuu:id,ten',
            'capNhiemVu:id,ten,kinh_phi',
            'chuTri:id,ten', // DonVi model for hosting unit
            'chuQuan:id,ten', // DonVi model for managing unit
            'msvcGvdkUser:id,ho_ten,msvc,email', // User who registered via msvc_gvdk
            // 'tienDo' => function ($query) { // Bỏ không load tiến độ
            //     $query->select('tien_do.id', 'tien_do.ten_moc', 'tien_do.mo_ta as tien_do_description', 'tien_do.thu_tu')
            //           ->withPivot('id as pivot_id', 'mo_ta as pivot_mo_ta', 'is_present', 'created_at as pivot_created_at')
            //           ->orderBy('tien_do.thu_tu');
            // },
            'giangVienThamGia' => function ($query) {
                // Load all participating members with their roles and other relevant user info
                $query->select('users.id', 'users.ho_ten', 'users.msvc', 'users.email', 'users.sdt')
                      ->withPivot('vai_tro_id', 'can_edit', 'join_at');
            },
            'chuNhiemDeTai' => function ($query) { // Explicitly load to select fields for the PI
                $query->select('users.id', 'users.ho_ten', 'users.msvc');
            },
            // 'baiBao:ma_bai_bao,de_tai_id,ten_bai_bao,ngayXuatBan,moTa,trangThai', // Load specific fields
            // 'taiLieu' => function ($query) {
            //     // Load related documents with uploader info
            //     $query->with('uploader:id,msvc,ho_ten') // Select specific fields from uploader
            //           ->select('id', 'de_tai_id', 'msvc', 'file_path', 'moTa', 'created_at'); // Select fields from tai_lieu
            // }
        ]);

        // 1. Enhance giangVienThamGia with role names
        $vaiTroIds = $deTai->giangVienThamGia->pluck('pivot.vai_tro_id')->unique()->filter()->toArray();
        $vaiTroMap = [];
        if (!empty($vaiTroIds)) {
            $vaiTroMap = VaiTro::whereIn('id', $vaiTroIds)->pluck('ten_vai_tro', 'id');
        }

        foreach ($deTai->giangVienThamGia as $gv) {
            if ($gv->pivot) {
                $gv->pivot->ten_vai_tro = $vaiTroMap[$gv->pivot->vai_tro_id] ?? 'Không xác định';
            }
        }

        // 2. Determine current user's role and permissions for this DeTai
        $currentUserInfo = [
            'msvc'=> $user->msvc,
            'ho_ten' => $user->ho_ten,
            'vai_tro' => null,
            'vai_tro_id' => null,
            'can_edit' => false,
            'is_registrant' => ($deTai->msvc_gvdk === $user->msvc),
        ];

        $currentUserParticipation = $deTai->giangVienThamGia->firstWhere('msvc', $user->msvc);
        if ($currentUserParticipation && $currentUserParticipation->pivot) {
            $currentUserInfo['vai_tro_id'] = $currentUserParticipation->pivot->vai_tro_id;
            $currentUserInfo['vai_tro'] = $currentUserParticipation->pivot->ten_vai_tro; // Already mapped
            $currentUserInfo['can_edit'] = (bool) $currentUserParticipation->pivot->can_edit;
        } elseif ($currentUserInfo['is_registrant']) {
            $currentUserInfo['vai_tro'] = 'Người đăng ký';
        }
        $deTai->current_user_info = $currentUserInfo;

        // 3. Determine and format "Chủ nhiệm" (Principal Investigator) information
        $chuNhiemInfo = null;
        if ($deTai->chuNhiemDeTai->isNotEmpty()) {
            $chuNhiem = $deTai->chuNhiemDeTai->first();
            $chuNhiemInfo = $chuNhiem->only(['ho_ten', 'msvc']);
        } elseif ($deTai->msvcGvdkUser) {
            $chuNhiemInfo = $deTai->msvcGvdkUser->only(['ho_ten', 'msvc']);
        }
        $deTai->chu_nhiem_info = $chuNhiemInfo;

        return response()->json($deTai);
    }

    public function updateDetaiSubmited(Request $request, DeTai $deTai)
    {
        /** @var \App\Models\User $lecturer */
        $lecturer = Auth::user();

        // Authorization Check
        if (!$lecturer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Assuming trang_thai_id = 1 means "Chờ duyệt" and is editable by lecturer
        // Adjust this ID if your "Chờ duyệt" status has a different ID.
        if ($deTai->trang_thai_id != 1) {
            return response()->json(['message' => 'Đề tài không thể chỉnh sửa ở trạng thái hiện tại.'], 403);
        }

        $canEditThisTopic = false;
        if ($deTai->msvc_gvdk === $lecturer->msvc) {
            $canEditThisTopic = true;
        } else {
            $participation = $deTai->giangVienThamGia()->where('users.msvc', $lecturer->msvc)->first();
            if ($participation && $participation->pivot->can_edit) {
                $canEditThisTopic = true;
            }
        }

        if (!$canEditThisTopic) {
            return response()->json(['message' => 'Bạn không có quyền chỉnh sửa đề tài này.'], 403);
        }

        // Validation
        $validatedData = $request->validate([
            'ten_de_tai' => 'required|string|max:255',
            'ma_de_tai_custom' => ['nullable', 'string', 'max:50', Rule::unique('de_tai', 'ma_de_tai')->ignore($deTai->id)],
            'linh_vuc_id' => 'required|integer|exists:linh_vuc_nc,id',
            'cap_nhiem_vu_id' => 'required|integer|exists:cap_nhiem_vu,id',
            'chu_tri_id' => 'required|integer|exists:don_vi,id',
            'chu_quan_id' => 'required|integer|exists:don_vi,id',
            'loai_hinh_nghien_cuu' => 'required|string|max:255',
            'thoi_gian_bat_dau_du_kien' => 'required|date_format:Y-m-d',
            'thoi_gian_ket_thuc_du_kien' => 'required|date_format:Y-m-d|after_or_equal:thoi_gian_bat_dau_du_kien',
            'thoi_gian_thuc_hien' => 'required|integer|min:1',
            'tong_kinh_phi_de_xuat' => 'required|numeric|min:0',
            'tong_quan_van_de' => 'required|string',
            'tinh_cap_thiet' => 'required|string',
            'muc_tieu_nghien_cuu' => 'required|string',
            'doi_tuong' => 'required|string',
            'pham_vi' => 'required|string',
            'noi_dung_phuong_phap' => 'required|string',
            'ghi_chu_de_xuat' => 'nullable|string',
            'thanh_vien_tham_gia' => 'required|array', // Can be empty if only PI is involved, but PI must be derived
            'thanh_vien_tham_gia.*.giang_vien_id' => 'required|string|exists:users,msvc', // Changed msvc to giang_vien_id
            'thanh_vien_tham_gia.*.vai_tro_id' => 'required|integer|exists:vai_tro,id',
            'thanh_vien_tham_gia.*.can_edit' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update DeTai fields
            $deTai->fill([
                'ten_de_tai' => $validatedData['ten_de_tai'],
                'ma_de_tai' => $validatedData['ma_de_tai_custom'] ?: null,
                'lvnc_id' => $validatedData['linh_vuc_id'],
                'cnv_id' => $validatedData['cap_nhiem_vu_id'],
                'chu_tri_id' => $validatedData['chu_tri_id'],
                'chu_quan_id' => $validatedData['chu_quan_id'],
                'loai_hinh_nghien_cuu' => $validatedData['loai_hinh_nghien_cuu'],
                'thoi_gian_nop' => $validatedData['thoi_gian_ket_thuc_du_kien'],
                'thoi_gian_thuc_hien' => $validatedData['thoi_gian_thuc_hien'],
                'tong_kinh_phi' => $validatedData['tong_kinh_phi_de_xuat'],
                'tong_quan_van_de' => $validatedData['tong_quan_van_de'],
                'tinh_cap_thiet' => $validatedData['tinh_cap_thiet'],
                'muc_tieu_nghien_cuu' => $validatedData['muc_tieu_nghien_cuu'],
                'doi_tuong' => $validatedData['doi_tuong'],
                'pham_vi' => $validatedData['pham_vi'],
                'noi_dung_phuong_phap' => $validatedData['noi_dung_phuong_phap'],
                'ghi_chu' => $validatedData['ghi_chu_de_xuat'] ?? null,
                'ngay_bat_dau_dukien'=> $validatedData['thoi_gian_bat_dau_du_kien'],
                'ngay_ket_thuc_dukien' => $validatedData['thoi_gian_ket_thuc_du_kien'],
            ]);
            $deTai->save();

            // Update thanh_vien_tham_gia
            $vaiTroChuNhiemId = 1; // Assuming 1 is "Chủ nhiệm"
            $originalRegistrantMsvc = $deTai->msvc_gvdk; // The original submitter (cannot be changed)

            $existingMembers = $deTai->giangVienThamGia()->get()->keyBy('msvc');
            $finalSyncData = [];

            // Ensure the original registrant (msvc_gvdk) is always "Chủ nhiệm"
            $finalSyncData[$originalRegistrantMsvc] = [
                'vai_tro_id' => $vaiTroChuNhiemId,
                'can_edit' => true,
                'join_at' => $existingMembers->has($originalRegistrantMsvc) ? $existingMembers->get($originalRegistrantMsvc)->pivot->join_at : now()
            ];

            if (!empty($validatedData['thanh_vien_tham_gia'])) {
                foreach ($validatedData['thanh_vien_tham_gia'] as $thanhVien) {
                    $memberMsvc = $thanhVien['giang_vien_id'];
                    $memberVaiTroId = $thanhVien['vai_tro_id'];

                    if ($memberMsvc === $originalRegistrantMsvc) {
                        // This member is the original registrant. Their role is already set to Chu Nhiem.
                        // If frontend sent a different role for them, log it as it's being overridden.
                        if ($memberVaiTroId != $vaiTroChuNhiemId) {
                             Log::warning("Attempt to change role of original registrant {$originalRegistrantMsvc} for DeTai ID {$deTai->id} to {$memberVaiTroId}. Overridden to Chu Nhiem (Role ID: {$vaiTroChuNhiemId}).");
                        }
                        continue; // Already handled
                    }

                    // Add other members
                    $finalSyncData[$memberMsvc] = [
                        'vai_tro_id' => $memberVaiTroId,
                        'can_edit' => $thanhVien['can_edit'],
                        'join_at' => $existingMembers->has($memberMsvc) ? $existingMembers->get($memberMsvc)->pivot->join_at : now()
                    ];
                }
            }
            $deTai->giangVienThamGia()->sync($finalSyncData);

            // Optionally, dispatch an event if the update is significant (e.g., for admin notification)
            // event(new ResearchTopicUpdated($deTai, $lecturer)); // You would need to create this Event class

            DB::commit();
            return response()->json(['message' => 'Cập nhật đề tài thành công.', 'de_tai' => $deTai->fresh()->load('giangVienThamGia.vaiTro')], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật đề tài ID ' . $deTai->id . ': ' . $e->getMessage() . ' Stack Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Đã xảy ra lỗi khi cập nhật đề tài.', 'error' => $e->getMessage()], 500);
        }
    }

    public function submitBaiBao(Request $request, DeTai $deTai)
    {
        /** @var \App\Models\User $lecturer */
        $lecturer = Auth::user();

        if (!$lecturer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Authorization: Check if the lecturer is part of the DeTai team
        $isMember = $deTai->msvc_gvdk === $lecturer->msvc ||
                    $deTai->giangVienThamGia()->where('users.msvc', $lecturer->msvc)->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Bạn không có quyền nộp bài báo cho đề tài này.'], 403);
        }

        // Validation
        $validatedData = $request->validate([
            'ten_bai_bao' => 'required|string|max:255',
            'ngay_xuat_ban' => 'required|date_format:Y-m-d',
            'mo_ta_bai_bao' => 'nullable|string|max:1000',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,zip,rar,jpg,png,xls,xlsx|max:20480', // Max 20MB, adjust as needed
            'file_descriptions' => 'nullable|array',
            'file_descriptions.*' => 'nullable|string|max:255',
        ]);

        if ($request->has('files') && $request->filled('file_descriptions')) {
            if (count($validatedData['files']) !== count($validatedData['file_descriptions'])) {
                return response()->json(['message' => 'Số lượng tệp và mô tả tệp không khớp.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $baiBao = new BaiBao();
            $baiBao->de_tai_id = $deTai->ma_de_tai; // Assumes de_tai_id in bai_bao table links to de_tai.id
            $baiBao->ten_bai_bao = $validatedData['ten_bai_bao'];
            $baiBao->ngay_xuat_ban = $validatedData['ngay_xuat_ban'];
            $baiBao->mo_ta = $validatedData['mo_ta_bai_bao'] ?? null;
            $baiBao->msvc_nguoi_nop = $lecturer->msvc;
            // As per your comment, 'trang_thai' is handled by the DB or default model value.
            $baiBao->save();

            if ($request->hasFile('files')) {
                $fileDescriptions = $request->input('file_descriptions', []);
                foreach ($request->file('files') as $index => $file) {
                    $filePath = $file->store("bai_bao_attachments/{$baiBao->id}", 'public');

                    $taiLieu = new TaiLieu();
                    $taiLieu->bai_bao_id = $baiBao->id;
                    $taiLieu->file_path = $filePath;
                    $taiLieu->mo_ta = $fileDescriptions[$index] ?? $file->getClientOriginalName();
                    $taiLieu->msvc_nguoi_upload = $lecturer->msvc;
                    $taiLieu->save();
                }
            }

            event(new BaiBaoSubmitted($baiBao, $lecturer, $deTai));
            DB::commit();
            return response()->json(['message' => 'Nộp bài báo thành công.', 'bai_bao' => $baiBao->load('taiLieu')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi nộp bài báo cho đề tài ID ' . $deTai->id . ': ' . $e->getMessage() . ' Stack Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Đã xảy ra lỗi khi nộp bài báo.', 'error' => $e->getMessage()], 500);
        }
    }
}
