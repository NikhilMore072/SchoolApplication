<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
//     public function login(Request $request)
// {
//     $credentials = $request->only('email', 'password');

//     if (!$token = JWTAuth::attempt($credentials)) {
//         return response()->json(['error' => 'Invalid credentials'], 401);
//     }

//     $user = auth()->user();
//     $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
//     $customClaims = [
//         'role_id' => $user->role_id,
//         'reg_id' =>$user->reg_id,
//         'academic_year' => $academic_yr,
//     ];

//     $token = JWTAuth::claims($customClaims)->fromUser($user);

//     return response()->json([
//         'token' => $token,
//         // 'user' => $user,
//     ]);
// }


public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    Log::info('Login attempt with credentials:', $credentials);

    try {
        if (!$token = JWTAuth::attempt($credentials)) {
            Log::warning('Invalid credentials for user:', $credentials);
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = JWTAuth::setToken($token)->toUser();
        $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;

        Log::info('Authenticated user:', ['user_id' => $user->id, 'academic_year' => $academic_yr]);

        $customClaims = [
            'role_id' => $user->role_id,
            'reg_id' => $user->reg_id,
            'academic_year' => $academic_yr,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($user);

        Log::info('Token created successfully:', ['token' => $token]);

        return response()->json(['token' => $token]);

    } catch (JWTException $e) {
        Log::error('JWTException occurred:', ['message' => $e->getMessage()]);
        return response()->json(['error' => 'Could not create token'], 500);
    }
}


    public function getUserDetails(Request $request)
    {
        $user = $this->authenticateUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized User'], 401);
        }

        $customClaims = JWTAuth::getPayload();

        return response()->json([
            'user' => $user,
            'custom_claims' => $customClaims,
        ]);
    }

    public function updateAcademicYear(Request $request)
    {
        $user = $this->authenticateUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized User'], 401);
        }

        $newAcademicYear = $request->input('academic_year');

        $customClaims = [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'academic_year' => $newAcademicYear,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($user);

        return response()->json([
            'token' => $token,
            'message' => 'Academic year updated successfully',
        ]);
    }

    public function listSections(Request $request)
    {
        // Extract the JWT token from the Authorization header
        $token = $request->bearerToken();
    
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
    
        try {
            // Get the payload from the token
            $payload = JWTAuth::setToken($token)->getPayload();
            // Extract the academic year from the custom claims
            $academicYr = $payload->get('academic_year');
    
            // Fetch the sections for the academic year
            $sections = Section::where('academic_yr', $academicYr)->get();
            return response()->json($sections);
    
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
    
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
    
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token error'], 401);
        }
    }
    

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
}
