<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // Import Rule class
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');

        $query = User::query();

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->inactive();
        } elseif ($status === 'banned') {
            $query->banned();
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
            'phone_number' => 'required|string|regex:/^[0-9]+$/|max:20', // Updated: Chỉ cho phép số
            'date_of_birth' => 'nullable|date',
            'description' => 'nullable|string',
            'avatar' => 'nullable|string',
            'address' => 'nullable|string',
            'is_vendor' => 'nullable|boolean',
            'gender' => 'nullable|string|in:male,female,other',
            'user_status' => 'nullable|string|in:active,inactive,banned', // updated here
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
            'user_status' => $request->user_status ?? 'active', // default active
        ]);
        $token = $user->createToken('user-token')->plainTextToken;

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request)
    {
        // Lấy user từ token (người dùng đang đăng nhập)
        $user = $request->user();
        // in ra tất cả thông tin gửi lên
        Log::info('Update User Request Data:', $request->all());
        $rules = [];

        // Password update validation
        if ($request->has('current_password')) {
            $rules['current_password'] = 'required|string';
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required|string|min:8';
        }

        // Profile fields validation
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
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // username hoặc email
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->login)
            ->orWhere('email', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Nếu dùng Laravel Sanctum hoặc Passport, có thể tạo token ở đây
        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // cần password_confirmation
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:20'], // Thêm regex để kiểm tra số
        ]);
        $avatarUrl = 'https://i.pravatar.cc/300?u=' . uniqid();

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'avatar' => $avatarUrl, // Lưu URL avatar vào database
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

            // Find or create user
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'username' => 'google_' . Str::random(10),
                    'email' => $googleUser->email,
                    'first_name' => $googleUser->user['given_name'] ?? 'Google',
                    'last_name' => $googleUser->user['family_name'] ?? 'User',
                    'phone_number' => null,
                    'avatar' => $googleUser->avatar ?? 'https://i.pravatar.cc/300?u=' . uniqid(),
                    // 'password' => Hash::make(Str::random(16)), // Random password for security
                    'password' => null, // No password for Google login
                    'google_id' => $googleUser->id,
                ]);
            } else {
                // Update Google ID if not set
                $user->update(['google_id' => $googleUser->id]);
            }

            $token = $user->createToken('user-token')->plainTextToken;

            // Redirect to frontend with token
            // return redirect()->away(
            //     env('FRONTEND_URL', 'https://travel-booking.hongducct.id.vn') . '/auth/callback?token=' . $token
            // );
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
