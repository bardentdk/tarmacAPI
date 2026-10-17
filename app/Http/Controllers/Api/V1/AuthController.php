<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create an account and return a mobile token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
            'user' => (new UserResource($user))->resolve($request),
        ], 201);
    }

    /**
     * Exchange credentials for a mobile token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
            'user' => (new UserResource($user))->resolve($request),
        ]);
    }

    /**
     * Revoke the token used for this request.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
