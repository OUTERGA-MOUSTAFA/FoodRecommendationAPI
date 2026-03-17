<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email|max:255',
            'password' => ['required', 'min:8', 'string', 'confirmed', Password::defaults()],
            'dietary_tags' => 'array',
            'dietary_tags.*' => 'in:vegan,no_sugar,no_cholesterol,gluten_free,no_lactose',
        ]);

        $isFirstUser = User::count() === 0;
        $user = User::create(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'dietary_tags' => $request->dietary_tags,
                'role' => $isFirstUser ? 'admin' : 'client',
            ]
        );

        // create token sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'values incorrect'], 401);
        }
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Token incorrects'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'success' => 'Loged in successfully',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        // $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    function user(Request $request)
    {
        return response()->json($request->user());
    }
}
