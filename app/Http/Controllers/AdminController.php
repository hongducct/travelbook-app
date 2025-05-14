<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes|unique:admins,username,' . $id,
            'email' => 'sometimes|email|unique:admins,email,' . $id,
            'password' => 'sometimes|min:6',
            'first_name' => 'sometimes',
            'last_name' => 'sometimes',
            'admin_status' => 'sometimes|in:active,inactive',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

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

        if (!$admin) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!Hash::check($validated['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Tạo token (dùng Laravel Sanctum hoặc Passport; tạm demo không cần)
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
                'admin_status' => $admin->admin_status,
            ],
        ]);
    }
    public function logout(Request $request)
    {
        $admin = $request->user();
        $admin->tokens()->delete();

        return response()->json(['message' => 'Logout successful']);
    }
}
