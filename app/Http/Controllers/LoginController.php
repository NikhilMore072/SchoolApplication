<?php


namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use League\Csv\Writer;
use App\Models\Parents;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Division;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;





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

    public function getStudentListbysectionforregister(Request $request , $section_id){         
        $studentList = Student::with('getClass', 'getDivision')
                                ->where('section_id',$section_id)
                                ->where('parent_id','0')
                                ->distinct()
                                ->get();

        return response()->json($studentList);                        
    }

    public function getAllStudentListForRegister(Request $request){                 
        $studentList = Student::with('getClass', 'getDivision')
                                ->where('parent_id','0')
                                ->distinct()
                                ->get();

        return response()->json($studentList);                        
    }

    // public function downloadCsvTemplateWithData(Request $request, $section_id)
    // {
    //     // Extract the academic year from the token payload
    //     //  $payload = getTokenPayload($request);    
    //     $academicYear = "2023-2024";
    //     // Fetch only the necessary fields from the Student model where academic year and section_id match
    //     $students = Student::select(
    //         'student_id as *Code',
    //         'first_name as *First Name',
    //         'mid_name as Mid name',
    //         'last_name as last name',
    //         'gender as *Gender',
    //         'dob as *DOB(in dd/mm/yyyy format)',
    //         'stu_aadhaar_no as Student Aadhaar No.',
    //         'mother_tongue as Mother Tongue',
    //         'religion as Religion',
    //         'blood_group as *Blood Group',
    //         'caste as caste',
    //         'subcaste as Sub Caste',
    //         'class_id as Class',
    //         'section_id as Division',
    //         'permant_add as *Address',
    //         'city as *City',
    //         'state as *State',
    //         'admission_date as *DOA(in dd/mm/yyyy format)',
    //         'reg_no as *GRN No'
    //     )
    //     ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')  
    //     ->where('student.parent_id','=','0')
    //     ->where('academic_yr', $academicYear)  
    //     ->where('section_id', $section_id)
    //     ->get()
    //     ->toArray();
        
    
    //     $headers = [
    //         'Content-Type' => 'text/csv',
    //         'Content-Disposition' => 'attachment; filename="students_template.csv"',
    //     ];
    
    //     $columns = [
    //         '*Code', 
    //         '*First Name', 
    //         'Mid name', 
    //         'last name', 
    //         '*Gender', 
    //         '*DOB(in dd/mm/yyyy format)', 
    //         'Student Aadhaar No.', 
    //         'Mother Tongue', 
    //         'Religion', 
    //         '*Blood Group', 
    //         'caste', 
    //         'Sub Caste', 
    //         'Class', 
    //         'Division', 
    //         '*Mother Name', 
    //         'Mother Occupation', 
    //         '*Mother Mobile No.(Only Indian Numbers)', 
    //         '*Mother Email-Id', 
    //         '*Father Name', 
    //         'Father Occupation', 
    //         '*Father Mobile No.(Only Indian Numbers)', 
    //         '*Father Email-Id', 
    //         'Mother Aadhaar No.', 
    //         'Father Aadhaar No.', 
    //         '*Address', 
    //         '*City', 
    //         '*State', 
    //         '*DOA(in dd/mm/yyyy format)', 
    //         '*GRN No',
    //     ];
    
    //     $callback = function() use ($columns, $students) {
    //         $file = fopen('php://output', 'w');
    
    //         // Write the header row
    //         fputcsv($file, $columns);
    
    //         // Write each student's data below the headers
    //         foreach ($students as $student) {
    //             fputcsv($file, $student);
    //         }
    
    //         fclose($file);
    //     };
    
    //     // Return the CSV file as a response
    //     return response()->stream($callback, 200, $headers);
    // }

    public function downloadCsvTemplateWithData(Request $request, $section_id)
    {
        // Extract the academic year from the token payload
        $academicYear = "2023-2024";
    
        // Fetch only the necessary fields from the Student model where academic year and section_id match
        $students = Student::select(
            'student_id as student_id', // Specify the table name
            'first_name as *First Name',
            'mid_name as Mid name',
            'last_name as last name',
            'gender as *Gender',
            'dob as *DOB(in dd/mm/yyyy format)',
            'stu_aadhaar_no as Student Aadhaar No.',
            'mother_tongue as Mother Tongue',
            'religion as Religion',
            'blood_group as *Blood Group',
            'caste as caste',
            'subcaste as Sub Caste',
            'class.name as Class', // Specify the table name
            'section.name as Division',
            'mother_name as *Mother Name', // Assuming you have this field
            'mother_occupation as Mother Occupation', // Assuming you have this field
            'm_mobile as *Mother Mobile No.(Only Indian Numbers)', // Assuming you have this field
            'm_emailid as *Mother Email-Id', // Assuming you have this field
            'father_name as *Father Name', // Assuming you have this field
            'father_occupation as Father Occupation', // Assuming you have this field
            'f_mobile as *Father Mobile No.(Only Indian Numbers)', // Assuming you have this field
            'f_email as *Father Email-Id', // Assuming you have this field
            'm_adhar_no as Mother Aadhaar No.', // Assuming you have this field
            'parent_adhar_no as Father Aadhaar No.', // Assuming you have this field
            'permant_add as *Address',
            'city as *City',
            'state as *State',
            'admission_date as *DOA(in dd/mm/yyyy format)',
            'reg_no as *GRN No'
        )
        ->distinct() 
        ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')  
        ->leftJoin('section', 'student.section_id', '=', 'section.section_id') // Use correct table name 'sections'
        ->leftJoin('class', 'student.class_id', '=', 'class.class_id') // Use correct table name 'sections'
        ->where('student.parent_id', '=', '0')
        ->where('student.academic_yr', $academicYear)  // Specify the table name here
        ->where('student.section_id', $section_id) // Specify the table name here
        ->get()
        ->toArray();
    
        // Debugging: Log the retrieved students data
        \Log::info('Students Data: ', $students); // Check Laravel logs to see if data is fetched correctly
    
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_template.csv"',
        ];
    
        $columns = [
            'student_id', 
            '*First Name', 
            'Mid name', 
            'last name', 
            '*Gender', 
            '*DOB(in dd/mm/yyyy format)', 
            'Student Aadhaar No.', 
            'Mother Tongue', 
            'Religion', 
            '*Blood Group', 
            'caste', 
            'Sub Caste', 
            'Class', 
            'Division',
            '*Mother Name', 
            'Mother Occupation', 
            '*Mother Mobile No.(Only Indian Numbers)', 
            '*Mother Email-Id', 
            '*Father Name', 
            'Father Occupation', 
            '*Father Mobile No.(Only Indian Numbers)', 
            '*Father Email-Id', 
            'Mother Aadhaar No.', 
            'Father Aadhaar No.', 
            '*Address', 
            '*City', 
            '*State', 
            '*DOA(in dd/mm/yyyy format)', 
            '*GRN No',
        ];
    
        $callback = function() use ($columns, $students) {
            $file = fopen('php://output', 'w');
    
            // Write the header row
            fputcsv($file, $columns);
    
            // Write each student's data below the headers
            foreach ($students as $student) {
                fputcsv($file, $student);
            }
    
            fclose($file);
        };
    
        // Return the CSV file as a response
        return response()->stream($callback, 200, $headers);
    }
    

// public function updateCsvData(Request $request, $section_id)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Fetch the class_id using the provided section_id
//     $division = Division::find($section_id);
//     $class_id = $division->class_id;

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Missing student ID']);
//             continue;
//         }

//         // Validate the gender field
//         if (!in_array($studentData['gender'], ['M', 'F', 'O'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Invalid gender value. Please enter M, F, or O.']);
//             continue;
//         }

//         // Validate and convert the date of birth (DOB) format (dd-mm-yyyy)
//         if (!preg_match('/\d{2}-\d{2}-\d{4}/', $studentData['dob'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Invalid DOB format. Expected format is dd-mm-yyyy.']);
//             continue;
//         } else {
//             // Convert dob to yyyy-mm-dd format
//             $dobParts = explode('-', $studentData['dob']);
//             $studentData['dob'] = "{$dobParts[2]}-{$dobParts[1]}-{$dobParts[0]}"; // Convert to yyyy-mm-dd
//         }

//         // Validate and convert admission_date format (dd-mm-yyyy)
//         if (!preg_match('/\d{2}-\d{2}-\d{4}/', $studentData['admission_date'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Invalid admission date format. Expected format is dd-mm-yyyy.']);
//             continue;
//         } else {
//             // Convert admission_date to yyyy-mm-dd format
//             $admissionParts = explode('-', $studentData['admission_date']);
//             $studentData['admission_date'] = "{$admissionParts[2]}-{$admissionParts[1]}-{$admissionParts[0]}"; // Convert to yyyy-mm-dd
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         if ($student) {
//             // Handle parent creation or update
//             $parentData = [
//                 'father_name' => $studentData['father_name'] ?? null,
//                 'father_occupation' => $studentData['father_occupation'] ?? null,
//                 'f_mobile' => $studentData['father_mobile'] ?? null,
//                 'f_email' => $studentData['father_email'] ?? null,
//                 'mother_name' => $studentData['mother_name'] ?? null,
//                 'mother_occupation' => $studentData['mother_occupation'] ?? null,
//                 'm_mobile' => $studentData['mother_mobile'] ?? null,
//                 'm_emailid' => $studentData['mother_email'] ?? null,
//                 'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
//                 'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
//                 'f_dob' => $studentData['dob'] ?? null,
//                 'm_dob' => $studentData['dob'] ?? null,
//             ];

//             // Check if the parent already exists based on mobile number or Aadhaar
//             $parent = Parents::where('f_mobile', $parentData['f_mobile'])->first();

//             if (!$parent) {
//                 // Create a new parent if not found
//                 $parent = Parents::create($parentData);
//             }

//             // Update the student's parent_id and class_id
//             $student->parent_id = $parent->parent_id;
//             $student->class_id = $class_id;

//             try {
//                 // Save the student record and update other fields
//                 $student->save();
//                 unset($studentData['father_name'], $studentData['father_occupation'], $studentData['father_mobile'], $studentData['father_email']);
//                 unset($studentData['mother_name'], $studentData['mother_occupation'], $studentData['mother_mobile'], $studentData['mother_email']);
//                 $student->update($studentData);
//             } catch (\Exception $e) {
//                 // Log the error and add the row to invalidRows with error message
//                 $invalidRows[] = array_merge($row, ['error' => 'Error updating student: ' . $e->getMessage()]);
//                 continue;
//             }
//         } else {
//             // Log student not found error
//             $invalidRows[] = array_merge($row, ['error' => 'Student not found']);
//         }
//     }

//     // If there are invalid rows, create a CSV file with rejected rows and error messages
//     if (!empty($invalidRows)) {
//         // Create a CSV writer instance
//         $csv = Writer::createFromString('');

//         // Add the header to the CSV file
//         $csv->insertOne(array_merge($header, ['error']));

//         // Add the invalid rows to the CSV file
//         foreach ($invalidRows as $invalidRow) {
//             $csv->insertOne($invalidRow);
//         }

//         // Save the CSV to the storage folder under 'app/public/csv_rejected'
//         $filePath = 'public/csv_rejected/rejected_rows_' . now()->format('Y_m_d_H_i_s') . '.csv';
//         Storage::put($filePath, $csv->toString());

//         // Return the URL for the rejected file
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => Storage::url($filePath), // URL to the generated CSV file
//         ], 422);
//     }

//     // Return a success response if no errors
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }



public function updateCsvData(Request $request, $section_id)
{
    // Validate the uploaded CSV file
    $request->validate([
        'file' => 'required|file|mimes:csv,txt|max:2048',
    ]);

    // Read the uploaded CSV file
    $file = $request->file('file');
    if (!$file->isValid()) {
        return response()->json(['message' => 'Invalid file upload'], 400);
    }

    // Get the contents of the CSV file
    $csvData = file_get_contents($file->getRealPath());
    $rows = array_map('str_getcsv', explode("\n", $csvData));
    $header = array_shift($rows); // Extract the header row

    // Define the CSV to database column mapping
    $columnMap = [
        'student_id' => 'student_id',
        '*First Name' => 'first_name',
        'Mid name' => 'mid_name',
        'last name' => 'last_name',
        '*Gender' => 'gender',
        '*DOB(in dd/mm/yyyy format)' => 'dob',
        'Student Aadhaar No.' => 'stu_aadhaar_no',
        'Mother Tongue' => 'mother_tongue',
        'Religion' => 'religion',
        '*Blood Group' => 'blood_group',
        'caste' => 'caste',
        'Sub Caste' => 'subcaste',
        '*Mother Name' => 'mother_name',
        'Mother Occupation' => 'mother_occupation',
        '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
        '*Mother Email-Id' => 'mother_email',
        '*Father Name' => 'father_name',
        'Father Occupation' => 'father_occupation',
        '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
        '*Father Email-Id' => 'father_email',
        'Mother Aadhaar No.' => 'mother_aadhaar_no',
        'Father Aadhaar No.' => 'father_aadhaar_no',
        '*Address' => 'permant_add',
        '*City' => 'city',
        '*State' => 'state',
        '*DOA(in dd/mm/yyyy format)' => 'admission_date',
        '*GRN No' => 'reg_no',
    ];

    // Prepare an array to store invalid rows for reporting
    $invalidRows = [];

    // Fetch the class_id using the provided section_id
    $division = Division::find($section_id);
    if (!$division) {
        return response()->json(['message' => 'Invalid section ID'], 400);
    }
    $class_id = $division->class_id;

    // Start processing the CSV rows
    foreach ($rows as $rowIndex => $row) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Map CSV columns to database fields
        $studentData = [];
        foreach ($header as $index => $columnName) {
            if (isset($columnMap[$columnName])) {
                $dbField = $columnMap[$columnName];
                $studentData[$dbField] = $row[$index] ?? null;
            }
        }

        // Validate required fields
        if (empty($studentData['student_id'])) {
            $invalidRows[] = array_merge($row, ['error' => 'Missing student ID']);
            continue;
        }

        if (!in_array($studentData['gender'], ['M', 'F', 'O'])) {
            $invalidRows[] = array_merge($row, ['error' => 'Invalid gender value. Expected M, F, or O.']);
            continue;
        }

        // Validate and convert DOB and admission_date formats
        if (!$this->validateDate($studentData['dob'], 'd-m-Y')) {
            $invalidRows[] = array_merge($row, ['error' => 'Invalid DOB format. Expected dd/mm/yyyy.']);
            continue;
        } else {
            $studentData['dob'] = \Carbon\Carbon::createFromFormat('d-m-Y', $studentData['dob'])->format('Y-m-d');
        }

        if (!$this->validateDate($studentData['admission_date'], 'd-m-Y')) {
            $invalidRows[] = array_merge($row, ['error' => 'Invalid admission date format. Expected dd-mm-yyyy.']);
            continue;
        } else {
            $studentData['admission_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $studentData['admission_date'])->format('Y-m-d');
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // Find the student by `student_id`
            $student = Student::where('student_id', $studentData['student_id'])->first();
            if (!$student) {
                $invalidRows[] = array_merge($row, ['error' => 'Student not found']);
                DB::rollBack();
                continue;
            }

            // Handle parent creation or update
            $parentData = [
                'father_name' => $studentData['father_name'] ?? null,
                'father_occupation' => $studentData['father_occupation'] ?? null,
                'f_mobile' => $studentData['father_mobile'] ?? null,
                'f_email' => $studentData['father_email'] ?? null,
                'mother_name' => $studentData['mother_name'] ?? null,
                'mother_occupation' => $studentData['mother_occupation'] ?? null,
                'm_mobile' => $studentData['mother_mobile'] ?? null,
                'm_emailid' => $studentData['mother_email'] ?? null,
                'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
                'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
            ];

            // Check if parent exists, if not, create one
            $parent = Parents::where('f_mobile', $parentData['f_mobile'])->first();
            if (!$parent) {
                $parent = Parents::create($parentData);
            }


           
            // Update the student's parent_id and class_id
            $student->parent_id = $parent->parent_id;
            $student->class_id = $class_id;
            $student->gender = $studentData['gender'];
            $student->first_name = $studentData['first_name'];
            $student->mid_name = $studentData['mid_name'];
            $student->last_name = $studentData['last_name'];
            $student->dob = $studentData['dob'];
            $student->admission_date = $studentData['admission_date'];
            $student->stu_aadhaar_no = $studentData['stu_aadhaar_no'];
            $student->mother_tongue = $studentData['mother_tongue'];
            $student->religion = $studentData['religion'];
            $student->caste = $studentData['caste'];
            $student->subcaste = $studentData['subcaste'];
            $student->IsDelete = 'N';
            $student->save();

            // Insert data into user_master table (skip if already exists)
            DB::table('user_master')->updateOrInsert(
                ['user_id' => $studentData['father_email']],
                [
                    'name' => $studentData['father_name'],
                    'password' => 'arnolds',
                    'reg_id' => $parent->parent_id,
                    'role_id' => 'P',
                    'IsDelete' => 'N',
                ]
            );

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            $invalidRows[] = array_merge($row, ['error' => 'Error updating student: ' . $e->getMessage()]);
            continue;
        }
    }

    // If there are invalid rows, generate a CSV for rejected rows
    if (!empty($invalidRows)) {
        $csv = Writer::createFromString('');
        $csv->insertOne(array_merge($header, ['error']));
        foreach ($invalidRows as $invalidRow) {
            $csv->insertOne($invalidRow);
        }
        $filePath = 'public/csv_rejected/rejected_rows_' . now()->format('Y_m_d_H_i_s') . '.csv';
        Storage::put($filePath, $csv->toString());

        return response()->json([
            'message' => 'Some rows contained errors.',
            'invalid_rows' => Storage::url($filePath),
        ], 422);
    }

    // Return a success response
    return response()->json(['message' => 'CSV data updated successfully.']);
}

// Helper method to validate date format
private function validateDate($date, $format = 'Y-m-d')
{
    $d = \DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}










}






































































    
    
    







































































//   working functionality 
// public function updateCsvData(Request $request, $section_id)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Fetch the class_id using the provided section_id
//     $division = Division::find($section_id);
//     $class_id = $division->class_id;
//     \Log::info('Division Data:', [
//         'section_id' => $section_id,
//         'division' => $division,
//         'class_id' => $class_id,
//     ]);

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Log the mapped student data
//         \Log::info('Mapped Student Data for Row ' . ($rowIndex + 2) . ': ', $studentData);

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
//                 'error' => 'Missing student ID',
//                 'data' => $studentData, // Include the invalid data for better debugging
//             ];
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         // If student exists, update the data
//         if ($student) {
//             // Handle parent creation or update
//             $parentData = [
//                 'father_name' => $studentData['father_name'] ?? null,
//                 'father_occupation' => $studentData['father_occupation'] ?? null,
//                 'f_mobile' => $studentData['father_mobile'] ?? null,
//                 'f_email' => $studentData['father_email'] ?? null,
//                 'mother_name' => $studentData['mother_name'] ?? null,
//                 'mother_occupation' => $studentData['mother_occupation'] ?? null,
//                 'm_mobile' => $studentData['mother_mobile'] ?? null,
//                 'm_emailid' => $studentData['mother_email'] ?? null,
//                 'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
//                 'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
//                 'f_dob' => $studentData['dob'] ?? null, // Use dob directly
//                 'm_dob' => $studentData['dob'] ?? null, // You might want to separate these based on your logic
//             ];

//             // Check if the parent already exists based on mobile number or Aadhaar
//             $parent = Parents::where('f_mobile', $parentData['f_mobile'])->orWhere('m_mobile', $parentData['m_mobile'])->first();

//             if (!$parent) {
//                 // Create a new parent if not found
//                 $parent = Parents::create($parentData);
//             }

//             // Update the student's parent_id
//             $student->parent_id = $parent->parent_id;

//             // Set the class_id for the student
//             $student->class_id = $class_id;  // Assign the class_id from the section_id

//             // Save the student record
//             $student->save();

//             // Update the student's data (excluding parent fields)
//             unset($studentData['father_name'], $studentData['father_occupation'], $studentData['father_mobile'], $studentData['father_email']);
//             unset($studentData['mother_name'], $studentData['mother_occupation'], $studentData['mother_mobile'], $studentData['mother_email']);
//             $student->update($studentData);
//         } else {
//             // Collect error if student not found
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2,
//                 'error' => 'Student not found',
//                 'data' => $studentData,
//             ];
//         }
//     }

//     // If there are invalid rows, return them in the response
//     if (!empty($invalidRows)) {
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => $invalidRows,
//         ], 422);
//     }

//     // Return a success response
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }


// Perfectly working 
// public function updateCsvData(Request $request, $section_id)
// {
//     try {
//         // Validate that a CSV file is uploaded
//         $request->validate([
//             'file' => 'required|file|mimes:csv,txt|max:2048',
//         ]);

//         // Read the uploaded CSV file
//         $file = $request->file('file');
//         if (!$file->isValid()) {
//             \Log::error('File upload failed: Invalid file', ['file' => $file]);
//             return response()->json(['message' => 'Invalid file upload'], 400);
//         }

//         // Get the contents of the uploaded file
//         $csvData = file_get_contents($file->getRealPath());
//         $rows = array_map('str_getcsv', explode("\n", $csvData));
//         $header = array_shift($rows); // Extract the header row

//         // Define a map for CSV columns to database fields
//         $columnMap = [
//             'student_id' => 'student_id',
//             '*First Name' => 'first_name',
//             'Mid name' => 'mid_name',
//             'last name' => 'last_name',
//             '*Gender' => 'gender',
//             '*DOB(in dd/mm/yyyy format)' => 'dob',
//             'Student Aadhaar No.' => 'stu_aadhaar_no',
//             'Mother Tongue' => 'mother_tongue',
//             'Religion' => 'religion',
//             '*Blood Group' => 'blood_group',
//             'caste' => 'caste',
//             'Sub Caste' => 'subcaste',
//             '*Mother Name' => 'mother_name',
//             'Mother Occupation' => 'mother_occupation',
//             '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//             '*Mother Email-Id' => 'mother_email',
//             '*Father Name' => 'father_name',
//             'Father Occupation' => 'father_occupation',
//             '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//             '*Father Email-Id' => 'father_email',
//             'Mother Aadhaar No.' => 'mother_aadhaar_no',
//             'Father Aadhaar No.' => 'father_aadhaar_no',
//             '*Address' => 'permant_add',
//             '*City' => 'city',
//             '*State' => 'state',
//             '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//             '*GRN No' => 'reg_no',
//         ];

//         // Prepare an array to collect invalid rows for reporting
//         $invalidRows = [];

//         // Fetch the class_id using the provided section_id
//         $division = Division::find($section_id);
//         if (!$division) {
//             \Log::error('Division not found for section_id', ['section_id' => $section_id]);
//             return response()->json(['message' => 'Division not found'], 404);
//         }
//         $class_id = $division->class_id;

//         \Log::info('Division Data:', [
//             'section_id' => $section_id,
//             'division' => $division,
//             'class_id' => $class_id,
//         ]);

//         // Loop through each row of data
//         foreach ($rows as $rowIndex => $row) {
//             // Skip empty rows
//             if (empty(array_filter($row))) {
//                 continue;
//             }

//             // Map CSV columns to database fields
//             $studentData = [];
//             foreach ($header as $index => $columnName) {
//                 if (isset($columnMap[$columnName])) {
//                     $dbField = $columnMap[$columnName];
//                     $studentData[$dbField] = $row[$index] ?? null;
//                 }
//             }

//             // Log the mapped student data
//             \Log::info('Mapped Student Data for Row ' . ($rowIndex + 2) . ': ', $studentData);

//             // Validate that `student_id` exists
//             if (empty($studentData['student_id'])) {
//                 $invalidRows[] = [
//                     'row' => $rowIndex + 2,
//                     'error' => 'Missing student ID',
//                     'data' => $studentData, // Include the invalid data for better debugging
//                 ];
//                 \Log::error('Missing student ID for row ' . ($rowIndex + 2));
//                 continue;
//             }

//             // Find the student by `student_id`
//             $student = Student::where('student_id', $studentData['student_id'])->first();

//             if ($student) {
//                 // Handle parent creation or update
//                 $parentData = [
//                     'father_name' => $studentData['father_name'] ?? null,
//                     'father_occupation' => $studentData['father_occupation'] ?? null,
//                     'f_mobile' => $studentData['father_mobile'] ?? null,
//                     'f_email' => $studentData['father_email'] ?? null,
//                     'mother_name' => $studentData['mother_name'] ?? null,
//                     'mother_occupation' => $studentData['mother_occupation'] ?? null,
//                     'm_mobile' => $studentData['mother_mobile'] ?? null,
//                     'm_emailid' => $studentData['mother_email'] ?? null,
//                     'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
//                     'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
//                     'f_dob' => $studentData['dob'] ?? null, // Use dob directly
//                     'm_dob' => $studentData['dob'] ?? null, // You might want to separate these based on your logic
//                 ];

//                 // Check if the parent already exists based on mobile number or Aadhaar
//                 $parent = Parents::where('f_mobile', $parentData['f_mobile'])->orWhere('m_mobile', $parentData['m_mobile'])->first();

//                 if (!$parent) {
//                     // Create a new parent if not found
//                     $parent = Parents::create($parentData);
//                 }

//                 // Update the student's parent_id
//                 $student->parent_id = $parent->parent_id;

//                 // Set the class_id for the student
//                 $student->class_id = $class_id;  // Assign the class_id from the section_id

//                 // Save the student record
//                 $student->save();

//                 // Update the student's data (excluding parent fields)
//                 unset($studentData['father_name'], $studentData['father_occupation'], $studentData['father_mobile'], $studentData['father_email']);
//                 unset($studentData['mother_name'], $studentData['mother_occupation'], $studentData['mother_mobile'], $studentData['mother_email']);
//                 $student->update($studentData);
//             } else {
//                 // Log error if student not found
//                 $invalidRows[] = [
//                     'row' => $rowIndex + 2,
//                     'error' => 'Student not found',
//                     'data' => $studentData,
//                 ];
//                 \Log::error('Student not found for row ' . ($rowIndex + 2));
//             }
//         }

//         // If there are invalid rows, return them in the response
//         if (!empty($invalidRows)) {
//             \Log::error('Invalid rows found', ['invalid_rows' => $invalidRows]);
//             return response()->json([
//                 'message' => 'Some rows contained errors.',
//                 'invalid_rows' => $invalidRows,
//             ], 422);
//         }

//         // Return a success response
//         return response()->json(['message' => 'Student data updated successfully.'], 200);
//     } catch (\Exception $e) {
//         // Log the exception
//         \Log::error('Error updating CSV data', [
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ]);

//         // Return a 500 response with the error message
//         return response()->json([
//             'message' => 'An error occurred while updating CSV data.',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }


// Working with the generate the csv file 
// public function updateCsvData(Request $request, $section_id)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Fetch the class_id using the provided section_id
//     $division = Division::find($section_id);
//     $class_id = $division->class_id;
//     \Log::info('Division Data:', [
//         'section_id' => $section_id,
//         'division' => $division,
//         'class_id' => $class_id,
//     ]);

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Missing student ID']);
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         if ($student) {
//             // Handle parent creation or update
//             $parentData = [
//                 'father_name' => $studentData['father_name'] ?? null,
//                 'father_occupation' => $studentData['father_occupation'] ?? null,
//                 'f_mobile' => $studentData['father_mobile'] ?? null,
//                 'f_email' => $studentData['father_email'] ?? null,
//                 'mother_name' => $studentData['mother_name'] ?? null,
//                 'mother_occupation' => $studentData['mother_occupation'] ?? null,
//                 'm_mobile' => $studentData['mother_mobile'] ?? null,
//                 'm_emailid' => $studentData['mother_email'] ?? null,
//                 'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
//                 'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
//                 'f_dob' => $studentData['dob'] ?? null,
//                 'm_dob' => $studentData['dob'] ?? null,
//             ];

//             // Check if the parent already exists based on mobile number or Aadhaar
//             $parent = Parents::where('f_mobile', $parentData['f_mobile'])->orWhere('m_mobile', $parentData['m_mobile'])->first();

//             if (!$parent) {
//                 // Create a new parent if not found
//                 $parent = Parents::create($parentData);
//             }

//             // Update the student's parent_id and class_id
//             $student->parent_id = $parent->parent_id;
//             $student->class_id = $class_id;

//             try {
//                 // Save the student record and update other fields
//                 $student->save();
//                 unset($studentData['father_name'], $studentData['father_occupation'], $studentData['father_mobile'], $studentData['father_email']);
//                 unset($studentData['mother_name'], $studentData['mother_occupation'], $studentData['mother_mobile'], $studentData['mother_email']);
//                 $student->update($studentData);
//             } catch (\Exception $e) {
//                 // Log the error and add the row to invalidRows with error message
//                 $invalidRows[] = array_merge($row, ['error' => 'Error updating student: ' . $e->getMessage()]);
//                 continue;
//             }
//         } else {
//             // Log student not found error
//             $invalidRows[] = array_merge($row, ['error' => 'Student not found']);
//         }
//     }

//     // If there are invalid rows, create a CSV file with rejected rows and error messages
//     if (!empty($invalidRows)) {
//         // Create a CSV writer instance
//         $csv = Writer::createFromString('');

//         // Add the header to the CSV file
//         $csv->insertOne(array_merge($header, ['error']));

//         // Add the invalid rows to the CSV file
//         foreach ($invalidRows as $invalidRow) {
//             $csv->insertOne($invalidRow);
//         }

//         // Save the CSV to a file and return the file in the response
//         $filePath = 'csv/rejected_rows_' . now()->format('Y_m_d_H_i_s') . '.csv';
//         Storage::put($filePath, $csv->toString());

//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => Storage::url($filePath), // URL to the generated CSV file
//         ], 422);
//     }

//     // Return a success response if no errors
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }





// public function updateCsvData(Request $request, $section_id)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Fetch the class_id using the provided section_id
//     $division = Division::find($section_id);
//     $class_id = $division->class_id;

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Missing student ID']);
//             continue;
//         }

//         // Validate the gender field
//         if (!in_array($studentData['gender'], ['M', 'F', 'O'])) {
//             $invalidRows[] = array_merge($row, ['error' => 'Invalid gender value. Please enter M, F, or O.']);
//             continue;
//         }

//         // Validate the date of birth (DOB) format (dd-mm-yyyy) and convert to yyyy-mm-dd
//         if (preg_match('/\d{2}-\d{2}-\d{4}/', $studentData['dob'])) {
//             try {
//                 // Convert the date from dd-mm-yyyy to yyyy-mm-dd
//                 $studentData['dob'] = Carbon::createFromFormat('d-m-Y', $studentData['dob'])->format('Y-m-d');
//             } catch (\Exception $e) {
//                 $invalidRows[] = array_merge($row, ['error' => 'Invalid DOB format. Expected format is dd-mm-yyyy.']);
//                 continue;
//             }
//         } else {
//             $invalidRows[] = array_merge($row, ['error' => 'Invalid DOB format. Expected format is dd-mm-yyyy.']);
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         if ($student) {
//             // Handle parent creation or update
//             $parentData = [
//                 'father_name' => $studentData['father_name'] ?? null,
//                 'father_occupation' => $studentData['father_occupation'] ?? null,
//                 'f_mobile' => $studentData['father_mobile'] ?? null,
//                 'f_email' => $studentData['father_email'] ?? null,
//                 'mother_name' => $studentData['mother_name'] ?? null,
//                 'mother_occupation' => $studentData['mother_occupation'] ?? null,
//                 'm_mobile' => $studentData['mother_mobile'] ?? null,
//                 'm_emailid' => $studentData['mother_email'] ?? null,
//                 'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
//                 'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
//                 'f_dob' => $studentData['dob'] ?? null,
//                 'm_dob' => $studentData['dob'] ?? null,
//             ];

//             // Check if the parent already exists based on mobile number or Aadhaar
//             $parent = Parents::where('f_mobile', $parentData['f_mobile'])->first();

//             if (!$parent) {
//                 // Create a new parent if not found
//                 $parent = Parents::create($parentData);
//             }

//             // Update the student's parent_id and class_id
//             $student->parent_id = $parent->parent_id;
//             $student->class_id = $class_id;

//             try {
//                 // Save the student record and update other fields
//                 $student->save();
//                 unset($studentData['father_name'], $studentData['father_occupation'], $studentData['father_mobile'], $studentData['father_email']);
//                 unset($studentData['mother_name'], $studentData['mother_occupation'], $studentData['mother_mobile'], $studentData['mother_email']);
//                 $student->update($studentData);
//             } catch (\Exception $e) {
//                 // Log the error and add the row to invalidRows with error message
//                 $invalidRows[] = array_merge($row, ['error' => 'Error updating student: ' . $e->getMessage()]);
//                 continue;
//             }
//         } else {
//             // Log student not found error
//             $invalidRows[] = array_merge($row, ['error' => 'Student not found']);
//         }
//     }

//     // If there are invalid rows, create a CSV file with rejected rows and error messages
//     if (!empty($invalidRows)) {
//         // Create a CSV writer instance
//         $csv = Writer::createFromString('');

//         // Add the header to the CSV file
//         $csv->insertOne(array_merge($header, ['error']));

//         // Add the invalid rows to the CSV file
//         foreach ($invalidRows as $invalidRow) {
//             $csv->insertOne($invalidRow);
//         }

//         // Save the CSV to the storage folder under 'app/public/csv_rejected'
//         $filePath = 'public/csv_rejected/rejected_rows_' . now()->format('Y_m_d_H_i_s') . '.csv';
//         Storage::put($filePath, $csv->toString());

//         // Return the URL for the rejected file
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => Storage::url($filePath), // URL to the generated CSV file
//         ], 422);
//     }

//     // Return a success response if no errors
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }









//   Working functionality code with student 
// public function updateCsvData(Request $request)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         'Class' => 'class_id',
//         'Division' => 'section_id',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Log the mapped student data
//         \Log::info('Mapped Student Data for Row ' . ($rowIndex + 2) . ': ', $studentData);

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
//                 'error' => 'Missing student ID',
//                 'data' => $studentData, // Include the invalid data for better debugging
//             ];
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         // If student exists, update the data
//         if ($student) {
//             // Directly assign the dob and admission_date without validation
//             $studentData['dob'] = $studentData['dob'] ?? null; // Assign as is
//             $studentData['admission_date'] = $studentData['admission_date'] ?? null; // Assign as is

//             // Update the student's data
//             $student->update($studentData);
//         } else {
//             // Collect error if student not found
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2,
//                 'error' => 'Student not found',
//                 'data' => $studentData,
//             ];
//         }
//     }

//     // If there are invalid rows, return them in the response
//     if (!empty($invalidRows)) {
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => $invalidRows,
//         ], 422);
//     }

//     // Return a success response
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }


  // with parents
// public function updateCsvData(Request $request)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         'Class' => 'class_id',
//         'Division' => 'section_id',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Log the mapped student data
//         \Log::info('Mapped Student Data for Row ' . ($rowIndex + 2) . ': ', $studentData);

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
//                 'error' => 'Missing student ID',
//                 'data' => $studentData, // Include the invalid data for better debugging
//             ];
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         // If student exists, update the data
//         if ($student) {
//             // Handle parent creation or update
//             $parentData = [
//                 'father_name' => $studentData['father_name'] ?? null,
//                 'father_occupation' => $studentData['father_occupation'] ?? null,
//                 'f_mobile' => $studentData['father_mobile'] ?? null,
//                 'f_email' => $studentData['father_email'] ?? null,
//                 'mother_name' => $studentData['mother_name'] ?? null,
//                 'mother_occupation' => $studentData['mother_occupation'] ?? null,
//                 'm_mobile' => $studentData['mother_mobile'] ?? null,
//                 'm_emailid' => $studentData['mother_email'] ?? null,
//                 'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
//                 'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
//                 'f_dob' => $studentData['dob'] ?? null, // Use dob directly
//                 'm_dob' => $studentData['dob'] ?? null, // You might want to separate these based on your logic
//             ];

//             // Check if the parent already exists based on mobile number or Aadhaar
//             $parent = Parents::where('f_mobile', $parentData['f_mobile'])->orWhere('m_mobile', $parentData['m_mobile'])->first();

//             if (!$parent) {
//                 // Create a new parent if not found
//                 $parent = Parents::create($parentData);
//             }

//             // Update the student's parent_id
//             $student->parent_id = $parent->parent_id;
//             $student->save();

//             // Update the student's data (excluding parent fields)
//             unset($studentData['father_name'], $studentData['father_occupation'], $studentData['father_mobile'], $studentData['father_email']);
//             unset($studentData['mother_name'], $studentData['mother_occupation'], $studentData['mother_mobile'], $studentData['mother_email']);
//             $student->update($studentData);
//         } else {
//             // Collect error if student not found
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2,
//                 'error' => 'Student not found',
//                 'data' => $studentData,
//             ];
//         }
//     }

//     // If there are invalid rows, return them in the response
//     if (!empty($invalidRows)) {
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => $invalidRows,
//         ], 422);
//     }

//     // Return a success response
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }



   
    // public function updateCsvData(Request $request)
    // {
    //     // Validate that a CSV file is uploaded
    //     $request->validate([
    //         'file' => 'required|file|mimes:csv,txt|max:2048',
    //     ]);
        
    
    //     // Read the uploaded CSV file
    //     $file = $request->file('file');
    //     $csvData = file_get_contents($file);
    //     $rows = array_map('str_getcsv', explode("\n", $csvData));
    //     $header = array_shift($rows); // Extract the header row
    
    //     // Define a map for CSV columns to database fields
    //     $columnMap = [
    //         '*Code' => 'student_id',
    //         '*First Name' => 'first_name',
    //         'Mid name' => 'mid_name',
    //         'last name' => 'last_name',
    //         '*Gender' => 'gender',
    //         '*DOB(in dd/mm/yyyy format)' => 'dob',
    //         'Student Aadhaar No.' => 'stu_aadhaar_no',
    //         'Mother Tongue' => 'mother_tongue',
    //         'Religion' => 'religion',
    //         '*Blood Group' => 'blood_group',
    //         'caste' => 'caste',
    //         'Sub Caste' => 'subcaste',
    //         'Class' => 'class_id',
    //         'Division' => 'section_id',
    //         '*Mother Name' => 'mother_name',
    //         'Mother Occupation' => 'mother_occupation',
    //         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
    //         '*Mother Email-Id' => 'mother_email',
    //         '*Father Name' => 'father_name',
    //         'Father Occupation' => 'father_occupation',
    //         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
    //         '*Father Email-Id' => 'father_email',
    //         'Mother Aadhaar No.' => 'mother_aadhaar_no',
    //         'Father Aadhaar No.' => 'father_aadhaar_no',
    //         '*Address' => 'permant_add',
    //         '*City' => 'city',
    //         '*State' => 'state',
    //         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
    //         '*GRN No' => 'reg_no',
    //     ];
    
    //     // Prepare an array to collect invalid rows for reporting
    //     $invalidRows = [];
    
    //     // Loop through each row of data
    //     foreach ($rows as $rowIndex => $row) {
    //         // Skip empty rows
    //         if (empty($row)) {
    //             continue;
    //         }
    
    //         // Map CSV columns to database fields
    //         $studentData = [];
    //         foreach ($header as $index => $columnName) {
    //             if (isset($columnMap[$columnName])) {
    //                 $dbField = $columnMap[$columnName];
    //                 $studentData[$dbField] = $row[$index] ?? null;
    //             }
    //         }
    
    //         // Validate that `student_id` exists
    //         if (empty($studentData['student_id'])) {
    //             $invalidRows[] = [
    //                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
    //                 'error' => 'Missing student ID',
    //             ];
    //             continue;
    //         }
    
    //         // Find the student by `student_id`
    //         $student = Student::where('student_id', $studentData['student_id'])->first();
    
    //         // If student exists, update the data
    //         if ($student) {
    //             // Handle date format for 'dob' and 'admission_date' if provided in 'dd/mm/yyyy' format
    //             if (!empty($studentData['dob'])) {
    //                 $dob = \DateTime::createFromFormat('d/m/Y', $studentData['dob']);
    //                 if ($dob) {
    //                     $studentData['dob'] = $dob->format('Y-m-d');
    //                 } else {
    //                     $invalidRows[] = [
    //                         'row' => $rowIndex + 2,
    //                         'error' => 'Invalid DOB format',
    //                     ];
    //                     continue;
    //                 }
    //             }
    
    //             if (!empty($studentData['admission_date'])) {
    //                 $admissionDate = \DateTime::createFromFormat('d/m/Y', $studentData['admission_date']);
    //                 if ($admissionDate) {
    //                     $studentData['admission_date'] = $admissionDate->format('Y-m-d');
    //                 } else {
    //                     $invalidRows[] = [
    //                         'row' => $rowIndex + 2,
    //                         'error' => 'Invalid admission date format',
    //                     ];
    //                     continue;
    //                 }
    //             }
    
    //             // Update the student's data
    //             $student->update($studentData);
    //         } else {
    //             // Collect error if student not found
    //             $invalidRows[] = [
    //                 'row' => $rowIndex + 2,
    //                 'error' => 'Student not found',
    //             ];
    //         }
    //     }
    
    //     // If there are invalid rows, return them in the response
    //     if (!empty($invalidRows)) {
    //         return response()->json([
    //             'message' => 'Some rows contained errors.',
    //             'invalid_rows' => $invalidRows,
    //         ], 422);
    //     }
    
    //     // Return a success response
    //     return response()->json(['message' => 'Student data updated successfully.'], 200);
    // }
   
   // First code 
//     public function updateCsvData(Request $request)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         '*Code' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         'Class' => 'class_id',
//         'Division' => 'section_id',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
//                 'error' => 'Missing student ID',
//             ];
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         // If student exists, update the data
//         if ($student) {
//             // Handle date format for 'dob' and 'admission_date'
//             if (!empty($studentData['dob'])) {
//                 $dob = \DateTime::createFromFormat('d/m/Y', $studentData['dob']);
//                 if ($dob) {
//                     $studentData['dob'] = $dob->format('Y-m-d');
//                 } else {
//                     $invalidRows[] = [
//                         'row' => $rowIndex + 2,
//                         'error' => 'Invalid DOB format',
//                     ];
//                     continue;
//                 }
//             }

//             if (!empty($studentData['admission_date'])) {
//                 $admissionDate = \DateTime::createFromFormat('d/m/Y', $studentData['admission_date']);
//                 if ($admissionDate) {
//                     $studentData['admission_date'] = $admissionDate->format('Y-m-d');
//                 } else {
//                     $invalidRows[] = [
//                         'row' => $rowIndex + 2,
//                         'error' => 'Invalid admission date format',
//                     ];
//                     continue;
//                 }
//             }

//             // Update the student's data
//             $student->update($studentData);
//         } else {
//             // Collect error if student not found
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2,
//                 'error' => 'Student not found',
//             ];
//         }
//     }

//     // If there are invalid rows, return them in the response
//     if (!empty($invalidRows)) {
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => $invalidRows,
//         ], 422);
//     }

//     // Return a success response
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }


//   Second working code 
// public function updateCsvData(Request $request)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         '*Code' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         'Class' => 'class_id',
//         'Division' => 'section_id',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = isset($row[$index]) ? trim($row[$index]) : null; // Trim the value
//             }
//         }

//         // Log the mapped student data for debugging
//         \Log::info("Mapped Student Data for Row $rowIndex: ", $studentData);

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
//                 'error' => 'Missing student ID',
//                 'data' => $studentData // Log the mapped data for context
//             ];
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         // If student exists, update the data
//         if ($student) {
//             // Handle date format for 'dob' and 'admission_date'
//             if (!empty($studentData['dob'])) {
//                 $dob = \DateTime::createFromFormat('d/m/Y', $studentData['dob']);
//                 if ($dob) {
//                     $studentData['dob'] = $dob->format('Y-m-d');
//                 } else {
//                     $invalidRows[] = [
//                         'row' => $rowIndex + 2,
//                         'error' => 'Invalid DOB format',
//                     ];
//                     continue;
//                 }
//             }

//             if (!empty($studentData['admission_date'])) {
//                 $admissionDate = \DateTime::createFromFormat('d/m/Y', $studentData['admission_date']);
//                 if ($admissionDate) {
//                     $studentData['admission_date'] = $admissionDate->format('Y-m-d');
//                 } else {
//                     $invalidRows[] = [
//                         'row' => $rowIndex + 2,
//                         'error' => 'Invalid admission date format',
//                     ];
//                     continue;
//                 }
//             }

//             // Update the student's data
//             $student->update($studentData);
//         } else {
//             // Collect error if student not found
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2,
//                 'error' => 'Student not found',
//             ];
//         }
//     }

//     // If there are invalid rows, return them in the response
//     if (!empty($invalidRows)) {
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => $invalidRows,
//         ], 422);
//     }

//     // Return a success response
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }


// public function updateCsvData(Request $request)
// {
//     // Validate that a CSV file is uploaded
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     // Read the uploaded CSV file
//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract the header row

//     // Define a map for CSV columns to database fields
//     $columnMap = [
//         'student_id' => 'student_id',
//         '*First Name' => 'first_name',
//         'Mid name' => 'mid_name',
//         'last name' => 'last_name',
//         '*Gender' => 'gender',
//         '*DOB(in dd/mm/yyyy format)' => 'dob',
//         'Student Aadhaar No.' => 'stu_aadhaar_no',
//         'Mother Tongue' => 'mother_tongue',
//         'Religion' => 'religion',
//         '*Blood Group' => 'blood_group',
//         'caste' => 'caste',
//         'Sub Caste' => 'subcaste',
//         'Class' => 'class_id',
//         'Division' => 'section_id',
//         '*Mother Name' => 'mother_name',
//         'Mother Occupation' => 'mother_occupation',
//         '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
//         '*Mother Email-Id' => 'mother_email',
//         '*Father Name' => 'father_name',
//         'Father Occupation' => 'father_occupation',
//         '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
//         '*Father Email-Id' => 'father_email',
//         'Mother Aadhaar No.' => 'mother_aadhaar_no',
//         'Father Aadhaar No.' => 'father_aadhaar_no',
//         '*Address' => 'permant_add',
//         '*City' => 'city',
//         '*State' => 'state',
//         '*DOA(in dd/mm/yyyy format)' => 'admission_date',
//         '*GRN No' => 'reg_no',
//     ];

//     // Prepare an array to collect invalid rows for reporting
//     $invalidRows = [];

//     // Loop through each row of data
//     foreach ($rows as $rowIndex => $row) {
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }

//         // Map CSV columns to database fields
//         $studentData = [];
//         foreach ($header as $index => $columnName) {
//             if (isset($columnMap[$columnName])) {
//                 $dbField = $columnMap[$columnName];
//                 $studentData[$dbField] = $row[$index] ?? null;
//             }
//         }

//         // Log the mapped student data
//         \Log::info('Mapped Student Data for Row ' . ($rowIndex + 2) . ': ', $studentData);

//         // Validate that `student_id` exists
//         if (empty($studentData['student_id'])) {
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2, // Adding 2 to account for the header row and 0-based index
//                 'error' => 'Missing student ID',
//                 'data' => $studentData, // Include the invalid data for better debugging
//             ];
//             continue;
//         }

//         // Find the student by `student_id`
//         $student = Student::where('student_id', $studentData['student_id'])->first();

//         // If student exists, update the data
//         if ($student) {
//             // Handle date format for 'dob' and 'admission_date'
//             if (!empty($studentData['dob'])) {
//                 $dob = \DateTime::createFromFormat('d/m/Y', $studentData['dob']);
//                 if ($dob) {
//                     $studentData['dob'] = $dob->format('Y-m-d');
//                 } else {
//                     $invalidRows[] = [
//                         'row' => $rowIndex + 2,
//                         'error' => 'Invalid DOB format',
//                         'data' => $studentData,
//                     ];
//                     continue;
//                 }
//             }

//             if (!empty($studentData['admission_date'])) {
//                 $admissionDate = \DateTime::createFromFormat('d/m/Y', $studentData['admission_date']);
//                 if ($admissionDate) {
//                     $studentData['admission_date'] = $admissionDate->format('Y-m-d');
//                 } else {
//                     $invalidRows[] = [
//                         'row' => $rowIndex + 2,
//                         'error' => 'Invalid admission date format',
//                         'data' => $studentData,
//                     ];
//                     continue;
//                 }
//             }

//             // Update the student's data
//             $student->update($studentData);
//         } else {
//             // Collect error if student not found
//             $invalidRows[] = [
//                 'row' => $rowIndex + 2,
//                 'error' => 'Student not found',
//                 'data' => $studentData,
//             ];
//         }
//     }

//     // If there are invalid rows, return them in the response
//     if (!empty($invalidRows)) {
//         return response()->json([
//             'message' => 'Some rows contained errors.',
//             'invalid_rows' => $invalidRows,
//         ], 422);
//     }

//     // Return a success response
//     return response()->json(['message' => 'Student data updated successfully.'], 200);
// }
    
    // public function editUser(Request $request)
    // {
    //     $user = Auth::user();
    //     $teacher = $user->getTeacher;
    //     if ($teacher) {
    //         return response()->json([
    //             'user' => $user,                
    //         ]);
    //     } else {
    //         return response()->json([
    //             'message' => 'Teacher information not found.',
    //         ], 404);
    //     }
    // }
    


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


    
// public function updateCsvData(Request $request)
// {
//     \Log::info('CSV upload started');

//     // Validate CSV file
//     $request->validate([
//         'file' => 'required|file|mimes:csv,txt|max:2048',
//     ]);

//     $file = $request->file('file');
//     if (!$file->isValid()) {
//         return response()->json(['message' => 'Invalid file upload'], 400);
//     }

//     // Get the contents of the uploaded file
//     $csvData = file_get_contents($file->getRealPath());
//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     $header = array_shift($rows); // Extract header

//     // Log the CSV header
//     \Log::info('CSV Header: ' . json_encode($header)); // Log header row

//     // Process each row and log the data
//     foreach ($rows as $rowIndex => $row) {
//         if (empty(array_filter($row))) {
//             \Log::info("Row $rowIndex is empty, skipping...");
//             continue; // Skip empty rows
//         }

//         // Log raw row data
//         \Log::info("CSV Row $rowIndex: " . json_encode($row)); // Log each row's data
//     }

//     // Return a success response indicating data has been printed
//     return response()->json(['message' => 'CSV data processed successfully. Check logs for details.'], 200);
// }