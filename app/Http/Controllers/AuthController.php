<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // butuh field password_confirmation
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function signin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Revoke token lama agar sesi baru memiliki idle-exp yang fresh.
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $abilities = $user->getAllPermissions()->map(function ($permission) {
            [$subject, $object] = explode('.', $permission->name);

            return [
                'subject' => $subject,
                'action' => $object,
            ];
        });

        // Ambil role pertama user (jika multi-role, ambil satu saja)
        $role = $user->roles()->first();
        $roleId = $role?->id;
        $roleUid = $role?->uid;
        // Ambil department_name dari userDetails pertama (jika ada)
        $departmentName = $user->userDetails()->first()?->department;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'department_name' => $departmentName,
            'role_id' => $roleId,
            'role_uid' => $roleUid,
            'permissions' => $abilities,
        ]);
    }

    /**
     * Login and get JWT token
     */
    public function jwtLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = Auth::user();

        return response()->json([
            'token' => $token,
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function signout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Successfully signed out',
        ]);
    }
}
