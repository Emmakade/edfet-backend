<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Register user (for super-admin creation or normal users)
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'name'=> $data['name'],
            'email'=> $data['email'],
            'password'=> Hash::make($data['password']),
            'phone'=> $data['phone'] ?? null
        ]);

        // optionally attach role if provided (only super-admin allowed to call this endpoint when role provided)
        if ($request->user() && $request->user()->hasRole('super-admin') && $request->filled('role')) {
            $user->assignRole($request->input('role'));
        }

        return response()->json(['user'=>$user], 201);
    }

    // Login and issue sanctum token
    public function login(LoginRequest $request)
    {
        $creds = $request->validated();
        $user = User::where('email', $creds['email'])->first();

        if (! $user || ! Hash::check($creds['password'], $user->password)) {
            return response()->json(['message'=>'Invalid credentials'], 401);
        }

        // create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    // Logout - revoke current token
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message'=>'Logged out']);
    }
}
