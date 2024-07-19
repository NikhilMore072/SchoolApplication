<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;


class LoginController extends Controller
{
    public function login(Request $request)
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

    if (!Hash::check($data['password'], $user->password)) {
        return response()->json(['message' => 'Invalid Password', 'field' => 'password', 'success' => false], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    $activeSetting = Setting::where('active', 'Y')->first();
    $academic_yr = $activeSetting->academic_yr;
    $reg_id = $user->reg_id;
    $role_id = $user->role_id;  
    $institutename = $activeSetting->institute_name;
    $user->academic_yr = $academic_yr;

    $sessionData = [
        'token' => $token,
        'role_id' => $role_id,
        'reg_id' => $reg_id,
        'academic_yr' => $academic_yr,
        'institutename' => $institutename,
    ];

    Session::put('sessionData', $sessionData);
    $cookie = cookie('sessionData', json_encode($sessionData), 120); // 120 minutes expiration

    return response()->json([
        'message' => "Login successfully",
        'token' => $token,
        'success' => true,
        'reg_id' => $reg_id,
        'role_id' => $role_id,
        'academic_yr' => $academic_yr,
        'institutename' => $institutename,
    ])->cookie($cookie);
}

public function getSessionData(Request $request)
{
    $sessionData = $request->session()->get('sessionData', []);
    if (empty($sessionData)) {
        return response()->json([
            'message' => 'No session data found',
            'success' => false
        ]);
    }

    return response()->json([
        'data' => $sessionData,
        'success' => true
    ]);
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
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'max:20',
                'regex:/^(?=.*[0-9])(?=.*[!@#\$%\^&\*]).{8,20}$/'
            ],
        ]);

        $user = Auth::user();

        // if ($data['answer_one'] !== $user->answer_one) {
        //     return response()->json(['message' => 'Security answer is incorrect', 'field' => 'answer_one', 'success' => false], 400);
        // }

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect', 'field' => 'current_password', 'success' => false], 400);
        }
        $user->answer_one =$data['answer_one'];
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


 
 


    public function getAcademicyear(Request $request)
    {
        $sessionData = Session::get('sessionData');
        $academicYr = $sessionData['academic_yr'] ?? null;

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
        }

        return response()->json(['academic_yr' => $academicYr, 'success' => true], 200);
    }

    
    public function editUser(Request $request)
    {
        $user = Auth::user();
        $teacher = $user->getTeacher;
        if ($teacher) {
            return response()->json([
                'user' => $user,                
            ]);
        } else {
            return response()->json([
                'message' => 'Teacher information not found.',
            ], 404);
        }
    }
    


    // public function updateUser(Request $request)
    // {
    //     try {
    //         // Validate the incoming request data
    //         $validatedData = $request->validate([
    //             'employee_id' => 'required|string|max:255',
    //             'name' => 'required|string|max:255',
    //             'father_spouse_name' => 'nullable|string|max:255',
    //             'birthday' => 'required|date',
    //             'date_of_joining' => 'required|date',
    //             'sex' => 'required|string|max:10',
    //             'religion' => 'nullable|string|max:255',
    //             'blood_group' => 'nullable|string|max:10',
    //             'address' => 'required|string|max:255',
    //             'phone' => 'required|string|max:15',
    //             'email' => 'required|string|email|max:255|unique:teacher,email,' . Auth::user()->reg_id . ',teacher_id',
    //             'designation' => 'required|string|max:255',
    //             'academic_qual' => 'nullable|array',
    //             'academic_qual.*' => 'nullable|string|max:255',
    //             'professional_qual' => 'nullable|string|max:255',
    //             'special_sub' => 'nullable|string|max:255',
    //             'trained' => 'nullable|string|max:255',
    //             'experience' => 'nullable|string|max:255',
    //             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no,' . Auth::user()->reg_id . ',teacher_id',
    //             'teacher_image_name' => 'nullable|string|max:255',
    //             'class_id' => 'nullable|integer',
    //             'section_id' => 'nullable|integer',
    //             'isDelete' => 'nullable|string|in:Y,N',
    //         ]);

    //         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
    //             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
    //         }

    //         $user = Auth::user();
    //         $teacher = $user->getTeacher;

    //         if ($teacher) {
    //             $teacher->fill($validatedData);
    //             $teacher->save();

    //             $user->update($request->only('email', 'name'));

    //             return response()->json([
    //                 'message' => 'Profile updated successfully!',
    //                 'user' => $user,
    //                 'teacher' => $teacher,
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'message' => 'Teacher information not found.',
    //             ], 404);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Error occurred while updating profile: ' . $e->getMessage(), [
    //             'request_data' => $request->all(),
    //             'exception' => $e
    //         ]);

    //         return response()->json([
    //             'message' => 'An error occurred while updating the profile',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


}
