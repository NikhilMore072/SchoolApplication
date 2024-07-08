<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{

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
    
        if(!Hash::check($data['password'], $user->password)){
            return response()->json(['message' => 'Invalid Password', 'field' => 'password', 'success' => false], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
        $reg_id = $user->reg_id;
        $role_id = $user->role_id;  
        $institutename = Setting::where('active', 'Y')->first()->institute_name;
    
        $sessionData = [
            'token' => $token,
            'role_id' => $role_id,
            'reg_id' => $reg_id,
            'academic_yr' => $academic_yr,
            'institutename' => $institutename,
        ];
    
        Session::put('sessionData', $sessionData);
    
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

  

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'answer_one' => 'required|string',
            'current_password' => 'required|string',
            'new_password' => 'required|string|confirmed|min:8',
        ]);

        $user = Auth::user();

        if ($data['answer_one'] !== $user->answer_one) {
            return response()->json(['message' => 'Security answer is incorrect', 'field' => 'answer_one', 'success' => false], 400);
        }

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect', 'field' => 'current_password', 'success' => false], 400);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully', 'success' => true], 200);
    }

    public function updateAcademicYear(Request $request)
{
    $request->validate([
        'academic_yr' => 'required|string',
    ]);

    $academicYr = $request->input('academic_yr');
    $sessionData = Session::get('sessionData');
    if (!$sessionData) {
        return response()->json(['message' => 'Session data not found', 'success' => false], 404);
    }

    $sessionData['academic_yr'] = $academicYr;
    Session::put('sessionData', $sessionData);

    return response()->json(['message' => 'Academic year updated successfully', 'success' => true], 200);
}


    public function clearData(Request $request)    {
        Session::forget('sessionData');
        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }


    public function getSessionData(Request $request)
    {
        $sessionData = Session::get('sessionData');

        if (!$sessionData) {
            return response()->json(['message' => 'Session data not found', 'success' => false], 404);
        }

        return response()->json(['session_data' => $sessionData, 'success' => true], 200);
    }



    public function getAcademicyear(Request $request)
    {
        $sessionData = Session::get('sessionData');
        $academicYr = $sessionData['academic_yr'] ?? null;

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
        }

        return response()->json(['academic_yr' => $academicYr, 'success' => true], 200);
    }





}
