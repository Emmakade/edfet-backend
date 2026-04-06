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
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        if ($request->user() && $request->user()->hasRole('super-admin') && $request->filled('role')) {
            $user->assignRole($request->input('role'));
        }

        return response()->json([
            'user' => $this->transformUser($user->fresh()),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $creds = $request->validated();

        $user = User::query()
            ->where('email', $creds['email'])
            ->first();

        if (! $user || ! Hash::check($creds['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $this->transformUser(
                $user->load('student.currentEnrollment.schoolClass', 'student.enrollments.schoolClass')
            ),
            'token' => $token,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load(
            'student.currentEnrollment.schoolClass',
            'student.enrollments.schoolClass'
        );

        return response()->json([
            'user' => $this->transformUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    private function transformUser(User $user): array
    {
        $roleName = $user->getRoleNames()->first();

        $frontendRole = match ($roleName) {
            'super-admin' => 'admin',
            'class-teacher' => 'teacher',
            default => $roleName,
        };

        return array_merge($user->toArray(), [
            'role' => $frontendRole,
            'role_name' => $roleName,
        ]);
    }
}