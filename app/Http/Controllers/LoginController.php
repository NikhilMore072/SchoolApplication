<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function authenticate(Request $request)      
    {
        $data = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found', 'success' => false], 404);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
        }

        $token = $user->createToken($data['email'])->plainTextToken;

        return (new UserResource($user))->additional([
            'message' => "Login successfully",
            'token' => $token,
            'success' => true
        ])->response()->setStatusCode(200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }
}
