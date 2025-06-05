<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Send forgot password OTP to user's email
     * Step 1 of password reset process
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if email exists in the system
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'email' => ['Email address not found in our system. Please check your email or register a new account.']
                ]
            ], 422);
        }

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in cache with 10 minutes expiration
        $cacheKey = 'forgot_password_otp_' . $user->id;
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        try {
            // Send OTP via email
            Mail::raw("Your password reset verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this password reset, please ignore this email.", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Password Reset Verification Code');
            });

            return response()->json([
                'message' => 'Password reset code has been sent to your email address.',
                'email' => $user->email // Optional: return email for confirmation
            ]);
        } catch (\Exception $e) {
            // Clean up the OTP from cache if email fails
            Cache::forget($cacheKey);

            return response()->json([
                'errors' => [
                    'general' => ['Failed to send reset code. Please try again later.']
                ]
            ], 500);
        }
    }

    /**
     * Reset password using OTP
     * Step 2 of password reset process
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'email' => ['Email address not found in our system.']
                ]
            ], 422);
        }

        // Check OTP
        $cacheKey = 'forgot_password_otp_' . $user->id;
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== (int)$request->otp) {
            return response()->json([
                'errors' => [
                    'otp' => ['Invalid or expired verification code. Please request a new one.']
                ]
            ], 422);
        }

        try {
            // Update user password
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Clear OTP from cache
            Cache::forget($cacheKey);

            // Optional: Send confirmation email
            try {
                Mail::raw("Your password has been successfully reset.\n\nIf you didn't make this change, please contact our support team immediately.", function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Password Reset Confirmation');
                });
            } catch (\Exception $e) {
                // Don't fail the request if confirmation email fails
            }

            return response()->json([
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'errors' => [
                    'general' => ['Failed to reset password. Please try again later.']
                ]
            ], 500);
        }
    }

    /**
     * Verify OTP without resetting password (optional utility method)
     */
    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'email' => ['Email address not found in our system.']
                ]
            ], 422);
        }

        $cacheKey = 'forgot_password_otp_' . $user->id;
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== (int)$request->otp) {
            return response()->json([
                'errors' => [
                    'otp' => ['Invalid or expired verification code.']
                ]
            ], 422);
        }

        return response()->json([
            'message' => 'OTP verified successfully.',
            'valid' => true
        ]);
    }

    // UPDATED: Enhanced index method with search and sorting
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');
        $search = $request->query('search');
        $sortBy = $request->query('sort', 'id');
        $sortOrder = $request->query('order', 'desc');

        $query = User::query();

        // Status filtering
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->inactive();
        } elseif ($status === 'banned') {
            $query->banned();
        }

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $allowedSortFields = ['id', 'username', 'email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('id');
        }

        $users = $query->paginate($perPage);
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|regex:/^[0-9]+$/|max:20',
            'date_of_birth' => 'nullable|date',
            'description' => 'nullable|string',
            'avatar' => 'nullable|string',
            'address' => 'nullable|string',
            'is_vendor' => 'nullable|boolean',
            'gender' => 'nullable|string|in:male,female,other',
            'user_status' => 'nullable|string|in:active,inactive,banned',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'date_of_birth' => $request->date_of_birth,
            'description' => $request->description,
            'avatar' => $request->avatar,
            'address' => $request->address,
            'is_vendor' => $request->is_vendor ?? false,
            'gender' => $request->gender,
            'user_status' => $request->user_status ?? 'active',
        ]);

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    // change user status
    public function changeStatus(Request $request, User $user)
    {
        $request->validate([
            'user_status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
        ]);

        try {
            $user->update([
                'user_status' => $request->user_status,
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['general' => ['Failed to update status.']]], 500);
        }

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => new UserResource($user)
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $rules = [];

        if ($request->has('current_password')) {
            $rules['current_password'] = 'required|string';
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required|string|min:8';
        } elseif ($request->has('otp')) {
            $rules['otp'] = 'required|string|size:6';
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required|string|min:8';
        }

        $rules = array_merge($rules, [
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone_number' => ['sometimes', 'string', 'regex:/^[\+0-9\-()\s]*$/', 'max:20', 'nullable'],
            'date_of_birth' => 'sometimes|date|nullable',
            'description' => 'sometimes|string|nullable',
            'avatar' => 'sometimes|string|nullable',
            'address' => 'sometimes|string|nullable',
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'is_vendor' => 'sometimes|boolean',
            'user_status' => ['sometimes', Rule::in(['active', 'inactive', 'banned'])],
        ]);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'username',
            'email',
            'first_name',
            'last_name',
            'phone_number',
            'date_of_birth',
            'description',
            'avatar',
            'address',
            'gender',
            'is_vendor',
            'user_status',
        ]);

        if ($request->has('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['errors' => ['current_password' => ['Current password is incorrect.']]], 422);
            }
            $data['password'] = Hash::make($request->password);
        } elseif ($request->has('otp')) {
            if ($user->password === null) {
                $storedOtp = Cache::get('otp_' . $user->id);

                if (!$storedOtp || $storedOtp !== (int)$request->otp) {
                    return response()->json(['errors' => ['otp' => ['Invalid or expired OTP.']]], 422);
                }

                $data['password'] = Hash::make($request->password);
                Cache::forget('otp_' . $user->id);
            } else {
                return response()->json(['errors' => ['general' => ['OTP is not required for users with a password.']]], 422);
            }
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function sendOtp(Request $request)
    {
        $user = $request->user();
        if ($user->password !== null) {
            return response()->json(['errors' => ['general' => ['OTP is only for Google-authenticated users without a password.']]], 422);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $user->id, $otp, now()->addMinutes(10));

        Mail::raw("Your OTP is $otp. Valid for 10 minutes.", function ($message) use ($user) {
            $message->to($user->email)->subject('Password Setup OTP');
        });

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->login)
            ->orWhere('email', $request->login)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Tài khoản không tồn tại (username hoặc email không đúng)'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu không đúng'], 401);
        }

        // Kiểm tra trạng thái tài khoản
        if ($user->user_status === 'inactive') {
            return response()->json([
                'message' => 'Tài khoản của bạn đang bị vô hiệu hóa. Vui lòng liên hệ travelbooking@hongducct.id.vn để được hỗ trợ kích hoạt lại tài khoản.',
                'status' => 'inactive'
            ], 403);
        }

        if ($user->user_status === 'banned') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa do vi phạm điều khoản sử dụng. Vui lòng liên hệ travelbooking@hongducct.id.vn để biết thêm chi tiết và thắc mắc về việc mở khóa tài khoản.',
                'status' => 'banned'
            ], 403);
        }

        // Chỉ cho phép đăng nhập nếu tài khoản active
        if ($user->user_status !== 'active') {
            return response()->json([
                'message' => 'Tài khoản của bạn chưa được kích hoạt. Vui lòng liên hệ travelbooking@hongducct.id.vn để được hỗ trợ.',
                'status' => $user->user_status
            ], 403);
        }

        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }


    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:20'],
        ]);

        $avatarUrl = 'https://i.pravatar.cc/300?u=' . uniqid();

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'avatar' => $avatarUrl,
        ]);

        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'message' => 'Register successful',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar,
            'is_vendor' => $user->is_vendor,
            'user_status' => $user->user_status,
            'has_password' => $user->password !== null,
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'username' => 'google_' . Str::random(10),
                    'email' => $googleUser->email,
                    'first_name' => $googleUser->user['given_name'] ?? 'Google',
                    'last_name' => $googleUser->user['family_name'] ?? 'User',
                    'phone_number' => null,
                    'avatar' => $googleUser->avatar ?? 'https://i.pravatar.cc/300?u=' . uniqid(),
                    'password' => null,
                    'google_id' => $googleUser->id,
                ]);
            } else {
                $user->update(['google_id' => $googleUser->id]);
            }

            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'message' => 'Register successful',
                'token' => $token,
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Google login failed'], 500);
        }
    }
}
