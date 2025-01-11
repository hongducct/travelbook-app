<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
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
            'phone_number' => 'required|string|max:20',
            'date_of_birth' => 'nullable|date',
            'description' => 'nullable|string',
            'avatar' => 'nullable|string',
            'address' => 'nullable|string',
            'is_vendor' => 'nullable|boolean',
            'gender' => 'nullable|string|in:male,female,other', // Add gender validation
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
            'gender' => $request->gender, // Add gender to creation
        ]);

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $rules = [];

        if ($request->has('current_password')) {
            $rules['current_password'] = 'required|string';
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required|string|min:8';
        } else {
            if ($request->has('username')) {
                $rules['username'] = 'required|string|max:255|unique:users,username,' . $user->id;
            }

            if ($request->has('email')) {
                $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $user->id;
            }

            if ($request->has('first_name')) {
                $rules['first_name'] = 'required|string|max:255';
            }

            if ($request->has('last_name')) {
                $rules['last_name'] = 'required|string|max:255';
            }

            if ($request->has('phone_number')) {
                $rules['phone_number'] = 'required|string|max:20';
            }

            if ($request->has('date_of_birth')) {
                $rules['date_of_birth'] = 'nullable|date';
            }

            if ($request->has('description')) {
                $rules['description'] = 'nullable|string';
            }

            if ($request->has('avatar')) {
                $rules['avatar'] = 'nullable|string';
            }

            if ($request->has('address')) {
                $rules['address'] = 'nullable|string';
            }

            if ($request->has('is_vendor')) {
                $rules['is_vendor'] = 'nullable|boolean';
            }
            if ($request->has('gender')) {
                $rules['gender'] = 'nullable|string|in:male,female,other';
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if (array_key_exists('current_password', $data)) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return response()->json(['errors' => ['current_password' => ['Mật khẩu hiện tại không đúng.']]], 422);
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
            unset($data['password_confirmation']);
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}