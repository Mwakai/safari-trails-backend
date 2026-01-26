<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponses;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => $request->role_id,
            'company_id' => $request->company_id,
            'phone' => $request->phone,
            'status' => $request->status ?? UserStatus::Active,
            'created_by' => $request->user()?->id,
        ]);

        $user->load(['role', 'company']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid credentials', 401);
        }

        $user = User::with(['role', 'company'])->firstWhere('email', $request->email);

        if (! $user->isActive()) {
            Auth::logout();

            return $this->error('Your account is inactive', 403);
        }

        $user->update(['last_login_at' => now()]);

        return $this->ok(
            'Authenticated',
            [
                'token' => $user->createToken(
                    'Api token for '.$user->email,
                    ['*'],
                    now()->addMonth()
                )->plainTextToken,
                'user' => new UserResource($user),
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (method_exists($token, 'delete')) {
            $token->delete();
        }

        return $this->ok('Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'company']);

        return $this->ok('User retrieved', [
            'user' => new UserResource($user),
        ]);
    }
}
