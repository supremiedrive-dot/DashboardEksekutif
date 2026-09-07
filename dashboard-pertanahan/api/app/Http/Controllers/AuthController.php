<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        // Laravel provider/timebox verification without creating a web session.
        $guard = Auth::guard('web');
        if (! $guard->once($request->validated() + ['is_active' => true])) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user = $guard->user();
        $token = $user->createToken('dashboard-api', ['*'], now()->addHours(24));

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $this->profile($user),
            'token' => $token->plainTextToken,
        ])->header('Cache-Control', 'no-store');
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()))->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } elseif ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logout berhasil'])->header('Cache-Control', 'no-store');
    }

    private function profile(User $user): array
    {
        $profile = $user->only(['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at']);
        $roles = $user->roles()->where('roles.is_active', true)->orderBy('code')->pluck('code')->all();
        return $profile + ['role' => $roles[0] ?? null, 'roles' => $roles];
    }
}
