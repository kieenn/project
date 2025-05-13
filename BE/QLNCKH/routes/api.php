<?php

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LecturerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('web')->group(function () {
    // Use the correct method name from AuthController if it's adminLogin
    // Assuming '/admin/login' is the intended endpoint based on your controller method name
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);

    // Route for user/lecturer login
    Route::post('/lecturer/login', [AuthController::class, 'userLogin']);

    // --- Authenticated Routes ---
    // These routes require the user to be logged in (session established via /admin/login)
    // Using 'auth:web' is appropriate here since you're using the 'web' guard and sessions.
    Route::middleware('auth:web')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        // Route::get('/role', [AuthController::class, 'getRole']);

        // Correct way to get the authenticated user using a closure
        Route::get('/user', function (Request $request) {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Lấy danh sách các mã quyền (ma_quyen)
            $permissionCodes = $user->permissions()->pluck('permissions.ma_quyen')->toArray();

            // Trả về thông tin người dùng cùng với is_superadmin và permission_codes
            return response()->json(array_merge($user->toArray(), [
                'permission_codes' => $permissionCodes
            ]));
        });
        
        // Route::get('/user-permissions', function (Request $request) {
        //     // Assuming you want to return the user's permissions
        //     // Adjust the relationship name based on your User model
        //     return response()->json(['permissions'=> $request->user()->permissions,
        //                 'is_superadmin' => $request->user()->is_superadmin]);
        //     //') // or ->load('permissions') if needed
        // });
        Route::get('/admin/permissions', [AdminController::class, 'getPermissions']);
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
        Route::get('/admin/users/{user}/permissions', [AdminController::class, 'getUserPermissions'])->name('admin.users.permissions');
        Route::get('/admin/hoc-ham', [AdminController::class, 'getHocHam']);
        Route::get('/admin/hoc-vi', [AdminController::class, 'getHocVi']);
        Route::get('/admin/don-vi', [AdminController::class, 'getDonVi']);
        Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::put('/admin/users/{user}/sync-permissions', [AdminController::class, 'syncPermissions'])->name('admin.users.syncPermissions');
        Route::post('/admin/users/add', [AdminController::class, 'addUser'])->name('admin.users.add');
        Route::put('/admin/don-vi/update/{donVi}', [AdminController::class, 'updateUnit'])->name('admin.donVi.updateUnit');
        Route::post('/admin/don-vi/add', [AdminController::class, 'addUnit'])->name('admin.donVi.addUnit');
        Route::delete('/admin/don-vi/delete/{donVi}', [AdminController::class, 'deleteUnit'])->name('admin.donVi.deleteUnit');
        Route::get('/admin/getDonVi', [AdminController::class, 'getDonViPagination'])->name('admin.getDonVi');
        Route::get('/admin/cap-nhiem-vu', [AdminController::class, 'getCapNhiemVu']);
        Route::get('/admin/getCapNhiemVu', [AdminController::class, 'getCapNhiemVuPagination'])->name('admin.getCapNhiemVu');
        Route::put('/admin/cap-nhiem-vu/update/{capNhiemVu}', [AdminController::class, 'updateCapNhiemVu'])->name('admin.capNhiemVu.update');
        Route::post('/admin/cap-nhiem-vu/add', [AdminController::class, 'addCapNhiemVu'])->name('admin.capNhiemVu.add');
        Route::delete('/admin/cap-nhiem-vu/delete/{capNhiemVu}', [AdminController::class, 'deleteCapNhiemVu'])->name('admin.capNhiemVu.delete');
        // Route::get('/admin/linh-vuc-nghien-cuu', [AdminController::class, 'getLinhVucNghienCuu']);
        Route::get('/admin/getLinhVucNghienCuu', [AdminController::class, 'getLinhVucNghienCuuPagination'])->name('admin.getLinhVucNghienCuu');
        Route::put('/admin/linh-vuc-nghien-cuu/update/{linhVucNghienCuu}', [AdminController::class, 'updateLinhVucNghienCuu'])->name('admin.linhVucNghienCuu.update');
        Route::post('/admin/linh-vuc-nghien-cuu/add', [AdminController::class, 'addLinhVucNghienCuu'])->name('admin.linhVucNghienCuu.add');
        Route::delete('/admin/linh-vuc-nghien-cuu/delete/{linhVucNghienCuu}', [AdminController::class, 'deleteLinhVucNghienCuu'])->name('admin.linhVucNghienCuu.delete');
        Route::get('/admin/tien-do-de-tai', [AdminController::class, 'getListTienDoDetaiPagination']);
        Route::get('/admin/linh-vuc-nghien-cuu', [AdminController::class, 'getAllLinhVucNghienCuu']);
        Route::get('/admin/trang-thai-de-tai', [AdminController::class, 'getAllTrangThaiDeTai']);
        Route::get('/admin/tien-do', [AdminController::class, 'getAllTienDo']);
        Route::post('/admin/tien-do-de-tai/{deTai:ma_de_tai}/update-progress', [AdminController::class, 'updateTienDoDeTai'])->name('admin.deTai.updateTienDoDeTai');

        Route::get('/linh-vuc', [LecturerController::class, 'getLinhVuc']);
        Route::get('/cap-nhiem-vu', [LecturerController::class, 'getCapNhiemVu']);
        Route::get('/vai-tro-thanh-vien', [LecturerController::class, 'getVaiTro']);
        Route::get('/find-by-msvc', [LecturerController::class, 'findUsersByMsvc']);
       

        Route::get('/hoc-ham', [LecturerController::class, 'getHocHam']);
        Route::get('/hoc-vi', [LecturerController::class, 'getHocVi']);
        Route::get('/don-vi', [LecturerController::class, 'getDonVi']);
        Route::put('/profile', [LecturerController::class, 'updateProfile']);
        Route::post('/research-topics/submit', [LecturerController::class, 'submitResearchTopic']);
        
        Route::get('/researches', [LecturerController::class, 'getAllDeTai']);
        Route::get('/trang-thai-de-tai/lecturer-view', [LecturerController::class, 'getAllTrangThai']);
        Route::get('/researches/{deTai}/edit-details', [LecturerController::class, 'getDeTaiDetail']);
        Route::put('/researches/{deTai}', [LecturerController::class, 'updateDetaiSubmited'])->name('lecturer.researches.update');

        Route::get('/admin/research-proposals/pending-approval', [AdminController::class, 'getListDeTaiXetDuyet']);
        Route::get('/admin/vai-tro', [AdminController::class, 'getVaiTro']);
        Route::put('/admin/research-proposals/{deTai}/review', [AdminController::class, 'setDeTai']);
        Route::get('/admin/articles/pending', [AdminController::class, 'getAllBaiBao']);
        Route::get('/admin/test', [AdminController::class, 'test']);
        // Add {deTai} route parameter for route model binding
        Route::post('/articles/declare/{deTai}', [LecturerController::class, 'submitBaiBao']);
        Route::get('/admin/articles/{baiBao}', [AdminController::class, 'getArticleDetail']); // New route for article detail       
        Route::post('/admin/articles/{baiBao}/approve',[AdminController::class, 'approveBaiBao']);
        Route::post('/admin/articles/{baiBao}/reject',[AdminController::class, 'rejectBaiBao']);

    });
});
Route::post('/forgot-password', [AuthController::class, 'sendResetOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']); // New route for OTP verification
Route::post('/update-password', [AuthController::class, 'updatePasswordAfterOtp']); 
// --- Other Protected API Routes ---
/*
Route::middleware('auth:sanctum')->group(function () {
    // Add other API routes for your application here if using Sanctum tokens
    // Example: Route::get('/de-tai', [DeTaiController::class, 'index']);
});
*/
