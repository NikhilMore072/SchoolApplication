<?php

namespace App\Http\Controllers;



use App\Models\UserMaster;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;


class LoginController extends Controller
{
    public function getdata(){
        $user = UserMaster::all();
        return $user;
    } 

public function authenticate(Request $request)
{
    $data = $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $user = UserMaster::where('user_id', $data['email'])->first();

    if (!$user || ($data['password'] != $user->password)) {
        return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    $settings = Setting::where('active', 'Y')->get();

    // Combine user and settings into one array
    $sessionData = [
        'user' => $user,
        'settings' => $settings,
    ];

    // Store the combined data in one session key
    Session::put('sessionData', $sessionData);

    return (new UserResource($user))->additional([
        'message' => "Login successfully",
        'token' => $token,
        'success' => true,
        'settings' => $settings,
    ])->response()->setStatusCode(200);
}




    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        // Session::forget('user');
        // Session::forget('settings');
        Session::forget('sessionData');

        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }


}


// public function authenticate(Request $request)
// {
//     $data = $request->validate([
//         'email' => 'required|string|email',
//         'password' => 'required|string',
//     ]);

//     $user = UserMaster::where('user_id', $data['email'])->first();

//     if (!$user || ($data['password'] != $user->password)) {
//         return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
//     }

//     $token = $user->createToken('auth_token')->plainTextToken;

//     $settings = Setting::where('active', 'Y')->get();
//     Session::put('user', $user);
//     Session::put('settings', $settings);

//     return (new UserResource($user))->additional([
//         'message' => "Login successfully",
//         'token' => $token,
//         'settings' => $settings,
//         'success' => true
//     ])->response()->setStatusCode(200);
// }
// public function getSessionData(Request $request)
// {
//     return response()->json([
//         'user' => Session::get('user'),
//         'settings' => Session::get('settings')
//     ]);
// }

//     public function authenticate(Request $request)
// {
//     $data = $request->validate([
//         'email' => 'required|string|email',
//         'password' => 'required|string',
//     ]);

//     $user = UserMaster::where('user_id', $data['email'])->first();

//     if (!$user) {
//         return response()->json(['message' => 'User not found', 'success' => false], 404);
//     }

//     if ($data['password'] != $user->password) {
//         return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
//     }

//     $token = $user->createToken('auth_token')->plainTextToken;

//     Session::put('user', $user);
//     $settings = Setting::all();
//     Session::put('settings', $settings);

//     return response()->json([
//         'message' => 'Login successfully',
//         'token' => $token,
//         'success' => true,
//         'user' => $user,
//         'settings' => $settings
//     ], 200);
// }










//     public function authenticate(Request $request)
// {
//     $data = $request->validate([
//         'email' => 'required|string|email',
//         'password' => 'required|string',
//     ]);

//     $user = UserMaster::where('user_id', $data['email'])->first();

//     if (!$user) {
//         return response()->json(['message' => 'User not found', 'success' => false], 404);
//     }

//     if ($data['password'] != $user->password) {
//         return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
//     }

//     // Fetch user settings data
//     $settings = Setting::first();

//     // Store user and settings data in the session
//     $request->session()->put('user', $user);
//     $request->session()->put('settings', $settings);

//     $token = $user->createToken('auth_token')->plainTextToken;

//     return (new UserResource($user))->additional([
//         'message' => "Login successfully",
//         'token' => $token,
//         'success' => true 
//     ])->response()->setStatusCode(200);
// }


//     public function logout(Request $request)
// {
//     // Delete the current access token
//     $request->user()->currentAccessToken()->delete();

//     // Remove user and settings data from the session
//     $request->session()->forget('user');
//     $request->session()->forget('settings');

//     return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
// }


 // public function authenticate(Request $request)      
    // {
    //     $data = $request->validate([
    //         'email' => 'required|string',
    //         'password' => 'required|string',
    //     ]);

    //     $user = User::where('email', $data['email'])->first();

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found', 'success' => false], 404);
    //     }

    //     if (!Hash::check($data['password'], $user->password)) {
    //         return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
    //     }

    //     $token = $user->createToken($data['email'])->plainTextToken;

    //     return (new UserResource($user))->additional([
    //         'message' => "Login successfully",
    //         'token' => $token,
    //         'success' => true
    //     ])->response()->setStatusCode(200);
    // }


  