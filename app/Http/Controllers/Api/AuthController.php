<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\ApiTokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Requests\Api\ApiRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(ApiLoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('api.errors.invalid_credentials')],
            ]);
        }

        return $this->tokenResponse($request, $user);
    }

    public function register(ApiRegisterRequest $request, CreateNewUser $action): JsonResponse
    {
        $user = $action->create($request->safe()->only(['name', 'email', 'password', 'password_confirmation']));

        return $this->tokenResponse($request, $user, 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    private function tokenResponse(Request $request, User $user, int $status = 200): JsonResponse
    {
        $abilities = ApiTokenAbility::values();
        $token = $user->createToken(
            $request->string('device_name')->toString(),
            $abilities,
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'abilities' => $abilities,
            'user' => new UserResource($user),
        ], $status);
    }
}
