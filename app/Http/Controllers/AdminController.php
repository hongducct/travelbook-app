<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $admins = Admin::all();
        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:admins',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone_number' => 'nullable|string|regex:/^[\+0-9\-()\s]*$/|max:20',
            'avatar' => 'nullable|string',
            'admin_status' => 'nullable|in:active,inactive',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $admin = Admin::create($validated);

        return response()->json(['message' => 'Admin created', 'admin' => $admin], 201);
    }

    public function show($id)
    {
        $admin = Admin::findOrFail($id);
        return response()->json($admin);
    }

    public function update(Request $request)
    {
        // lấy thông tin admin từ token
        $admin = $request->user();
        $id = $admin->id;
        // nếu có id trong request thì lấy id từ request, nếu không thì dùng id từ token
        if ($request->has('id')) {
            $id = $request->input('id');
            $admin = Admin::findOrFail($id);
        }

        $validated = $request->validate([
            'username' => ['sometimes', 'unique:admins,username,' . $id],
            'email' => ['sometimes', 'email', 'unique:admins,email,' . $id],
            'first_name' => 'sometimes',
            'last_name' => 'sometimes',
            'phone_number' => 'nullable|string|regex:/^[\+0-9\-()\s]*$/|max:20',
            'avatar' => 'nullable|string',
            'admin_status' => 'sometimes|in:active,inactive',
        ]);

        $admin->update($validated);

        return response()->json(['message' => 'Admin updated', 'admin' => $admin]);
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();

        return response()->json(['message' => 'Admin deleted']);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $admin = Admin::where('username', $validated['username'])->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'email' => $admin->email,
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'phone_number' => $admin->phone_number,
                'avatar' => $admin->avatar,
                'admin_status' => $admin->admin_status,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout successful']);
    }

    public function profile(Request $request)
    {
        $admin = $request->user();
        return response()->json([
            'id' => $admin->id,
            'username' => $admin->username,
            'email' => $admin->email,
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'phone_number' => $admin->phone_number,
            'avatar' => $admin->avatar,
            'admin_status' => $admin->admin_status,
        ]);
    }

    public function requestEmailChangeOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|string|email|max:255|unique:admins,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->user();
        $otp = rand(100000, 999999);
        $cacheKey = 'email_change_otp_' . $admin->id;

        // Store both OTP and new email in cache
        Cache::put($cacheKey, [
            'otp' => $otp,
            'new_email' => $request->new_email
        ], now()->addMinutes(10));

        Log::info('Email Change OTP generated for admin ' . $admin->id . ': ' . $otp);

        try {
            Mail::raw("Your email change verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this email change, please ignore this email.", function ($message) use ($request) {
                $message->to($request->new_email)
                    ->subject('Email Change Verification Code');
            });

            Log::info('Email Change OTP email sent successfully to: ' . $request->new_email);

            return response()->json([
                'message' => 'Email change verification code has been sent to your new email address.',
                'new_email' => $request->new_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email change verification email: ' . $e->getMessage());
            Cache::forget($cacheKey);

            return response()->json([
                'errors' => ['general' => ['Failed to send verification code. Please try again later.']]
            ], 500);
        }
    }

    public function changeEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|string|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->user();
        $cacheKey = 'email_change_otp_' . $admin->id;
        $storedData = Cache::get($cacheKey);

        Log::info('Email Change - Stored OTP for admin ' . $admin->id . ': ' . ($storedData['otp'] ?? 'null'));
        Log::info('Email Change - Request OTP: ' . $request->otp);

        if (
            !$storedData ||
            !isset($storedData['otp']) ||
            $storedData['otp'] !== (int)$request->otp ||
            $storedData['new_email'] !== $request->new_email
        ) {

            Log::info('Email Change - OTP Validation Failed');
            return response()->json([
                'errors' => ['otp' => ['Invalid or expired verification code. Please request a new one.']]
            ], 422);
        }

        try {
            $admin->update([
                'email' => $request->new_email
            ]);

            Cache::forget($cacheKey);

            Log::info('Email change successful for admin: ' . $admin->id . ' to: ' . $request->new_email);

            try {
                Mail::raw("Your email address has been successfully changed.\n\nIf you didn't make this change, please contact our support team immediately.", function ($message) use ($request) {
                    $message->to($request->new_email)
                        ->subject('Email Change Confirmation');
                });
            } catch (\Exception $e) {
                Log::warning('Failed to send email change confirmation email: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Email has been changed successfully.',
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'email' => $admin->email,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'phone_number' => $admin->phone_number,
                    'avatar' => $admin->avatar,
                    'admin_status' => $admin->admin_status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to change email: ' . $e->getMessage());

            return response()->json([
                'errors' => ['general' => ['Failed to change email. Please try again later.']]
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'errors' => ['email' => ['Email address not found in our system.']]
            ], 422);
        }

        $otp = rand(100000, 999999);
        $cacheKey = 'forgot_password_otp_' . $admin->id;
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        Log::info('Forgot Password OTP generated for admin ' . $admin->id . ': ' . $otp);

        try {
            Mail::raw("Your password reset verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this password reset, please ignore this email.", function ($message) use ($admin) {
                $message->to($admin->email)
                    ->subject('Password Reset Verification Code');
            });

            Log::info('Forgot Password OTP email sent successfully to: ' . $admin->email);

            return response()->json([
                'message' => 'Password reset code has been sent to your email address.',
                'email' => $admin->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send forgot password email: ' . $e->getMessage());
            Cache::forget($cacheKey);

            return response()->json([
                'errors' => ['general' => ['Failed to send reset code. Please try again later.']]
            ], 500);
        }
    }

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

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'errors' => ['email' => ['Email address not found in our system.']]
            ], 422);
        }

        $cacheKey = 'forgot_password_otp_' . $admin->id;
        $storedOtp = Cache::get($cacheKey);

        Log::info('Password Reset - Stored OTP for admin ' . $admin->id . ': ' . $storedOtp);
        Log::info('Password Reset - Request OTP: ' . $request->otp);

        if (!$storedOtp || $storedOtp !== (int)$request->otp) {
            Log::info('Password Reset - OTP Validation Failed: Stored OTP (' . $storedOtp . ') !== Request OTP (' . $request->otp . ')');
            return response()->json([
                'errors' => ['otp' => ['Invalid or expired verification code. Please request a new one.']]
            ], 422);
        }

        try {
            $admin->update([
                'password' => Hash::make($request->password)
            ]);

            Cache::forget($cacheKey);

            Log::info('Password reset successful for admin: ' . $admin->email);

            try {
                Mail::raw("Your password has been successfully reset.\n\nIf you didn't make this change, please contact our support team immediately.", function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject('Password Reset Confirmation');
                });
            } catch (\Exception $e) {
                Log::warning('Failed to send password reset confirmation email: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage());

            return response()->json([
                'errors' => ['general' => ['Failed to reset password. Please try again later.']]
            ], 500);
        }
    }
}
