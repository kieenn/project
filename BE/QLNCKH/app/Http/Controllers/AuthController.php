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
use Carbon\Carbon; // Import Carbon for time comparison

class AuthController extends Controller
{
    /**
     * Handle an incoming authentication request using MSVC for Admin access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function adminLogin(Request $request)
    {
        // 1. Validate the incoming request data
        $request->validate([
            'msvc' => 'required|string',
            'password' => 'required|string',
            // 'remember' => 'boolean' // Optional remember me functionality
        ]);

        // 2. Prepare credentials for authentication attempt
        $credentials = $request->only('msvc', 'password');

        // 3. Attempt to authenticate the user using the 'web' guard
        if (!Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            // If authentication fails (wrong msvc or password), throw a validation exception
            throw ValidationException::withMessages([
                'msvc' => [trans('auth.failed')], // Use standard translation
            ]);
        }

        // --- Authorization Check ---
        // 4. Get the authenticated user instance
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        // 5. Check if the user is a superadmin OR has an entry in user_permissions
        $isSuperAdmin = $user->is_superadmin ?? false; // Check is_superadmin (default to false if null)

        // Check for permission entry using the UserPermission Model:
        $hasAdminPermission = UserPermission::where('msvc', $user->msvc)->exists();

        // OR Check using the DB Facade (uncomment the line below and comment the one above if preferred):
        // $hasAdminPermission = DB::table('user_permissions')->where('msvc', $user->msvc)->exists();

        if (!$isSuperAdmin && !$hasAdminPermission) {
            // If user is NOT superadmin AND does NOT have permission entry, deny access
            Auth::guard('web')->logout(); // Log the user out immediately
            $request->session()->invalidate(); // Invalidate the session
            $request->session()->regenerateToken(); // Regenerate CSRF token

            // Throw a validation exception indicating lack of permission
            throw ValidationException::withMessages([
                'msvc' => ['You do not have permission to access the admin area.'],
            ]);
        }

        // --- Login Success ---
        // 6. User is authenticated AND authorized, regenerate the session ID
        $request->session()->regenerate();

        // 7. Return a simple success message as JSON
        // Optionally, you could return the user object or specific user data here
        // return response()->json(['user' => $user->only(['id', 'name', 'msvc', 'is_superadmin'])]);
        return response()->json(['message' => 'Successfully logged in.']);
    }

    /**
     * Destroy an authenticated session (logout).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json(['message' => 'Successfully logged out.']);
    }


    public function userLogin(Request $request){
        // 1. Validate the incoming request data
        $request->validate([
            'msvc' => 'required|string',
            'password' => 'required|string',
            // 'remember' => 'boolean' // Optional remember me functionality
        ]);

        // 2. Prepare credentials for authentication attempt
        $credentials = $request->only('msvc', 'password');

        // 3. Attempt to authenticate the user using the 'web' guard
        if (!Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            // If authentication fails (wrong msvc or password), throw a validation exception
            throw ValidationException::withMessages([
                // Use the standard translation key for failed authentication attempts
                'msvc' => [trans('auth.failed')],
            ]);
        }

        // 4. Authentication successful, get the authenticated user instance
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        // --- Optional: Add checks if necessary (e.g., account status) ---
        // Example: Check if the user's account is active
        // if (!$user->is_active) { // Assuming you have an 'is_active' column
        //     Auth::guard('web')->logout(); // Log out the inactive user
        //     $request->session()->invalidate();
        //     $request->session()->regenerateToken();
        //     throw ValidationException::withMessages([
        //         'msvc' => ['Your account is inactive. Please contact support.'],
        //     ]);
        // }

        // --- Login Success ---
        // 5. Regenerate the session ID to prevent session fixation attacks
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Successfully logged in.',
            'user' => $user->only(['id', 'name', 'msvc', 'email']) // Adjust fields as needed
        ]);
    }

    /**
     * Send password reset OTP via email.
     * POST /api/forgot-password
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $otp = random_int(100000, 999999);

        try {
            $cacheKey = 'password_reset_otp_' . $request->email;
            $expiresInMinutes = config('auth.passwords.users.expire', 15);
            Cache::put($cacheKey, $otp, now()->addMinutes($expiresInMinutes));
            Mail::to($request->email)->send(new PasswordResetOtpMail((string)$otp));
            return response()->json(['message' => 'Mã OTP đã được gửi đến email của bạn.']);
        } catch (\Exception $e) {
            Log::error('Error sending OTP: ' . $e->getMessage());
            return response()->json(['message' => 'Không thể gửi mã OTP. Vui lòng thử lại.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify the provided OTP.
     * POST /api/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $cacheKey = 'password_reset_otp_' . $request->email;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp) {
            return response()->json(['message' => 'Yêu cầu không hợp lệ hoặc mã OTP đã hết hạn.',
                                     'status' => 'error'
                                    ], 400);
        }
        if ($request->otp != $cachedOtp) {
            return response()->json(['message' => 'Mã OTP không chính xác.', 'status' => 'error'], 400);
        }

        // OTP is valid. Mark as verified in cache for a short period (e.g., 10 minutes)
        $verifiedCacheKey = 'password_reset_verified_' . $request->email;
        Cache::put($verifiedCacheKey, true, now()->addMinutes(10));

        // Cache::forget($cacheKey); // Optionally remove OTP now

        return response()->json(['message' => 'Xác thực OTP thành công.', 'status' => 'success']);
    }

    /**
     * Update the password after OTP verification.
     * POST /api/update-password
     */
    public function updatePasswordAfterOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $verifiedCacheKey = 'password_reset_verified_' . $request->email;
        if (!Cache::has($verifiedCacheKey)) {
            return response()->json(['message' => 'Bạn chưa xác thực OTP hoặc phiên xác thực đã hết hạn.'], 400);
        }

        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $user->password = Hash::make($request->password);
            $user->save();
            Cache::forget('password_reset_otp_' . $request->email);
            Cache::forget($verifiedCacheKey);
            DB::commit();
            return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting password: ' . $e->getMessage());
            return response()->json(['message' => 'Không thể đặt lại mật khẩu. Vui lòng thử lại.', 'error' => $e->getMessage()], 500);
        }
    }
}
