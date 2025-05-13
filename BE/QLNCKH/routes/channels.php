<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    // Logic kiểm tra user có phải là admin không
    // Ví dụ: user là superadmin hoặc có quyền trong bảng user_permissions
    if ($user) {
        $isSuperAdmin = $user->is_superadmin ?? false;
        // Giả sử bạn có model UserPermission để check quyền admin
        // Hoặc bạn có thể kiểm tra một permission cụ thể nếu hệ thống phân quyền chi tiết hơn
        $hasAdminAccess = \App\Models\UserPermission::where('msvc', $user->msvc)->exists(); // Điều chỉnh nếu cần
        return $isSuperAdmin || $hasAdminAccess;
    }
    return false;
});

Broadcast::channel('lecturer-notifications.{lecturerMsvc}', function ($user, $lecturerMsvc) {
    // So sánh MSVC của người dùng đã xác thực với msvc từ tên kênh
    return $user->msvc === $lecturerMsvc;
});
