<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email salah.'],
                'password' => ['Password salah.']
            ]);
        }

        $user = $request->user();

        $expirationMinutes = (int) config('sanctum.expiration', 480);
        $expiresAt = now()->addMinutes($expirationMinutes);

        $token = $user->createToken('admin-token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
