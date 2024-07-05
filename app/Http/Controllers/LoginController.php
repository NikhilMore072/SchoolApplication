<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function getdata(){
        return User::all();
    } 

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        $user = User::where('email', $data['email'])
                    ->where('IsDelete', 'N')
                    ->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found', 'field' => 'email', 'success' => false], 404);
        }
    
        if ($data['password'] != $user->password) {
            return response()->json(['message' => 'Invalid Password', 'field' => 'password', 'success' => false], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
        $reg_id = $user->reg_id;
        $role_id = $user->role_id;  
        $institutename = Setting::where('active', 'Y')->first()->institute_name;
    
        $sessionData = [
            'token' => $token,
            'user' => $user,
            'academic_yr' => $academic_yr,
            'institutename' => $institutename,
        ];
    
        Session::put('sessionData', $sessionData);
        Log::info('Session Data Set:', ['sessionData' => $sessionData]);
    
        return (new UserResource($user))->additional([
            'message' => "Login successfully",
            'token' => $token,
            'success' => true,
            'reg_id' => $reg_id,
            'role_id' =>$role_id,
            'academic_yr' => $academic_yr,
            'institutename' => $institutename,
        ])->response()->setStatusCode(200);
    }
    

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Session::forget('sessionData');
        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }

    public function clearData(Request $request)    {
        Session::forget('sessionData');
        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }

public function getSessionData(Request $request)
{
    $academicYear = null;
    $sessionData = json_decode($request->session()->get('sessionData'), true);
    if ($sessionData && isset($sessionData['academic_yr'])) {
        $academicYear = $sessionData['academic_yr'];
    }

    return response()->json([
        'academic_year' => $academicYear,
    ]);
}




    public function getAcademicyear(Request $request)
    {
        $sessionData = Session::get('sessionData');
        Log::info('Session Data Retrieved:', ['sessionData' => $sessionData]);

        $academicYr = $sessionData['academic_yr'] ?? null;

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
        }

        return response()->json(['academic_yr' => $academicYr, 'success' => true], 200);
    }



    public function updateAcademicYear(Request $request)
{
     $sessionData = Session::get('sessionData');
    if (!$sessionData) {
        return response()->json(['message' => 'Session data not found', 'success' => false], 404);
    }
    $sessionData['academic_yr'] = '2000-2001';

    Session::put('sessionData', $sessionData);

    Log::info('Session Data Updated:', ['sessionData' => $sessionData]);

    return response()->json(['message' => 'Academic year updated successfully', 'success' => true], 200);
}

}










// public function authenticate(Request $request)
// {
//     // Regenerate session ID to start a new session
//     $request->session()->regenerate();

//     $data = $request->validate([
//         'email' => 'required|string|email',
//         'password' => 'required|string',
//     ]);

//     $user = User::where('email', $data['email'])
//                 ->where('IsDelete', 'N')
//                 ->first();

//     if (!$user) {
//         return response()->json(['message' => 'User not found', 'field' => 'email', 'success' => false], 404);
//     }

//     if ($data['password'] != $user->password) {
//         return response()->json(['message' => 'Invalid Password', 'field' => 'password', 'success' => false], 401);
//     }

//     $token = $user->createToken('auth_token')->plainTextToken;

//     $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
//     $institutename = Setting::where('active', 'Y')->first()->institute_name;

//     $sessionData = [
//         'token' => $token,
//         'user' => $user,
//         'academic_yr' => $academic_yr,
//         'institutename' => $institutename,
//     ];

//     // Put data into the session
//     $request->session()->put('sessionData', $sessionData);
//     Log::info('Session Data Set:', ['sessionData' => $sessionData]);

//     return (new UserResource($user))->additional([
//         'message' => "Login successfully",
//         'token' => $token,
//         'success' => true,
//         'user' => $user,
//         'academic_yr' => $academic_yr,
//         'institutename' => $institutename,
//     ])->response()->setStatusCode(200);
// }

// public function logout(Request $request)
// {
//     // End the session
//     $request->session()->invalidate();
//     $request->session()->regenerateToken();

//     $request->user()->currentAccessToken()->delete();
//     Session::forget('sessionData');
    
//     return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
// }




































// namespace App\Http\Controllers;



// use App\Models\User;
// use App\Models\Setting;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;
// use App\Http\Resources\UserResource;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Session;


// class LoginController extends Controller
// {
//     public function getdata(){
//         $user = User::all();
//         return $user;
//     } 

// public function authenticate(Request $request)
// {
//     $data = $request->validate([
//         'email' => 'required|string|email',
//         'password' => 'required|string',
//     ]);

//     $user = User::where('user_id', $data['email'])->first();

//     if (!$user || ($data['password'] != $user->password)) {
//         return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
//     }

//     $token = $user->createToken('auth_token')->plainTextToken;

//     $settings = Setting::where('active', 'Y')->get();
//     $academic_yr = Setting::where('active', 'Y')->first()->academic_yr; 
 
//     $sessionData = [
//         'user' => $user,
//         'settings' => $settings,
//         'academic_yr'=>$academic_yr,
//     ];
//     Session::put('sessionData', $sessionData);
//         Log::info('Session Data Set:', ['sessionData' => $sessionData]);

//     return (new UserResource($user))->additional([
//         'message' => "Login successfully",
//         'token' => $token,
//         'success' => true,
//         // 'settings' => $settings,
//         'academic_yr'=>$academic_yr,
//     ])->response()->setStatusCode(200);
// }


//     public function logout(Request $request)
//     {
//         $request->user()->currentAccessToken()->delete();
//         Session::forget('sessionData');
//         return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
//     }

//     public function getSessionData(Request $request)
//     {
//         $sessionData = Session::get('sessionData');

//         if (!$sessionData) {
//             return response()->json(['message' => 'Session data not found', 'success' => false], 404);
//         }

//         return response()->json(['session_data' => $sessionData, 'success' => true], 200);
//     }


// }
















































































// public function authenticate(Request $request)
// {
//     $data = $request->validate([
//         'email' => 'required|string|email',
//         'password' => 'required|string',
//     ]);

//     $user = User::where('user_id', $data['email'])->first();

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

//     $user = User::where('user_id', $data['email'])->first();

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

//     $user = User::where('user_id', $data['email'])->first();

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


  