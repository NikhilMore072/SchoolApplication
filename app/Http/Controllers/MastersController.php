<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Section;
use App\Models\Division;
use App\Models\Students;
use App\Models\Attendence;
use App\Models\UserMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class MastersController extends Controller
{
    // academic_yr
    public function getAcademicyearlist(Request $request){

        $academicyearlist = Setting::get()->academic_yr;
        return response()->json($academicyearlist);

          }

    public function getStudentData(){

        $academicYr = $request->header('X-Academic-Year');
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }
        $count = Students::where('IsDelete', 'N')->count();
        $currentDate = Carbon::now()->toDateString();
        $present = Attendence::where('only_date', $currentDate)
                            ->where('attendance_status', '0')
                            ->where('attendance_status', '0')
                            ->count();
        return response()->json([
            'count'=>$count,
            'present'=>$present,
        ]);
    }

    public function staff(){
     
       $teachingStaff = UserMaster::where('IsDelete','N')
                        ->where('role_id','T')
                        ->count();

        $non_teachingStaff = UserMaster::where('IsDelete', 'N')
                        ->whereIn('role_id', ['A', 'F', 'M', 'L', 'X', 'Y'])
                        ->count();            

       return response()->json([
        'teachingStaff'=>$teachingStaff,
        'non_teachingStaff'=>$non_teachingStaff,
       ]);                 
    }


    public function getbirthday(Request $request){
        $academicYr = $request->header('X-Academic-Year');
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }
        $currentDate = Carbon::now()->toDateString();

        $count  =Students::where('IsDelete', 'N')
                          ->where('dob',$currentDate)
                          ->where('academic_yr',$academicYr)
                          ->count();

          $list  =Students::where('IsDelete', 'N')
                          ->where('dob',$currentDate)
                          ->get();
        return response()->json([
            'count'=>$count,
            'list'=>$list,
        ]);                  
    }


    public function showSection(Request $request)
{
    $academicYr = $request->header('X-Academic-Year');
    if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }
    
    $data = Section::where('academic_yr', $academicYr)->get();
    return response()->json($data);
}

public function storeSection(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $academicYr = $request->header('X-Academic-Year');
    if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }



    // Create a new section
    $section = new Section();
    $section->name = $request->name;
    $section->academic_yr = $academicYr;

    // Save the section to the database
    $section->save();

    // Return success response
    return response()->json([
        'status' => 200,
        'message' => 'Section created successfully',
    ]);
}

public function editSection($id)
{
    $section = Section::find($id);

    if (!$section) {
        return response()->json(['message' => 'Section not found', 'success' => false], 404);
    }

    return response()->json($section);
}

public function updateSection(Request $request, $id)
{
    $request->validate([
        'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'name.regex' => 'The name field must contain only alphabetic characters without spaces.',
    ]);

    $section = Section::find($id);
    $academicYr = $request->header('X-Academic-Year');
    if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }

    if (!$section) {
        return response()->json(['message' => 'Section not found', 'success' => false], 404);
    }

    $section->name = $request->name; // Set the section name
    $section->academic_yr = $academicYr;
    $section->save();

    return response()->json([
        'status' => 200,
        'message' => 'Section updated successfully',
    ]);
}


public function deleteSection($id)
{
    $section = Section::find($id);

    if (!$section) {
        return response()->json(['message' => 'Section not found', 'success' => false], 404);
    }

    $section->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Section deleted successfully',
    ]);
}


// Methods for the classes model

public function getClass(Request $request)
{   
    $academicYr = $request->header('X-Academic-Year');
    if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }
    $classes = Classes::with('getDepartment')->where('academic_yr', $academicYr)->get();
    return response()->json($classes);
}


// public function storeClass(Request $request)
// {
//     $academicYr = $request->header('X-Academic-Year');
//     if (!$academicYr) {
//         return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
//     }

//     $request->validate([
//         'name' => ['required', 'string', 'max:255'],
//         'name_numeric' => ['required', 'integer'],
//         'department_id' => ['required', 'integer'],
//     ], [
//         'name.required' => 'The name field is required.',
//         'name.string' => 'The name field must be a string.',
//         'name.max' => 'The name field must not exceed 255 characters.',
//         'name_numeric.required' => 'The numeric name field is required.',
//         'name_numeric.integer' => 'The numeric name field must be an integer.',
//         'department_id.required' => 'The department ID is required.',
//         'department_id.integer' => 'The department ID must be an integer.',
//     ]);

//     $class = new Classes();
//     $class->name = $request->name;
//     $class->name_numeric = $request->name_numeric;
//     $class->department_id = $request->department_id;
//     $class->academic_yr = $academicYr;

//     $class->save();

//     return response()->json([
//         'status' => 200,
//         'message' => 'Class created successfully',
//     ]);
// }

// public function showClass($id)
// {
//     $class = Classes::find($id);

//     if (!$class) {
//         return response()->json(['message' => 'Class not found', 'success' => false], 404);
//     }

//     return response()->json($class);
// }

// public function updateClass(Request $request, $id)
// {
//     $request->validate([
//         'name' => ['required', 'string', 'max:255'],
//         'name_numeric' => ['required', 'integer'],
//         'department_id' => ['required', 'integer'],
//     ], [
//         'name.required' => 'The name field is required.',
//         'name.string' => 'The name field must be a string.',
//         'name.max' => 'The name field must not exceed 255 characters.',
//         'name_numeric.required' => 'The numeric name field is required.',
//         'name_numeric.integer' => 'The numeric name field must be an integer.',
//         'department_id.required' => 'The department ID is required.',
//         'department_id.integer' => 'The department ID must be an integer.',
//     ]);

//     $class = Classes::find($id);

//     if (!$class) {
//         return response()->json(['message' => 'Class not found', 'success' => false], 404);
//     }

//     $academicYr = $request->header('X-Academic-Year');
//     if (!$academicYr) {
//         return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
//     }

//     $class->name = $request->name;
//     $class->name_numeric = $request->name_numeric;
//     $class->department_id = $request->department_id;
//     $class->academic_yr = $academicYr;

//     $class->save();

//     return response()->json([
//         'status' => 200,
//         'message' => 'Class updated successfully',
//     ]);
// }
public function storeClass(Request $request)
{
    $academicYr = $request->header('X-Academic-Year');
    if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }

    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'department_id' => ['required', 'integer'],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'department_id.required' => 'The department ID is required.',
        'department_id.integer' => 'The department ID must be an integer.',
    ]);

    $class = new Classes();
    $class->name = $request->name;
    $class->department_id = $request->department_id;
    $class->academic_yr = $academicYr;

    $class->save();

    return response()->json([
        'status' => 200,
        'message' => 'Class created successfully',
    ]);
}
public function updateClass(Request $request, $id)
{
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'department_id' => ['required', 'integer'],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'department_id.required' => 'The department ID is required.',
        'department_id.integer' => 'The department ID must be an integer.',
    ]);

    $class = Classes::find($id);

    if (!$class) {
        return response()->json(['message' => 'Class not found', 'success' => false], 404);
    }

    $academicYr = $request->header('X-Academic-Year');
    if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }

    $class->name = $request->name;
    $class->department_id = $request->department_id;
    $class->academic_yr = $academicYr;

    $class->save();

    return response()->json([
        'status' => 200,
        'message' => 'Class updated successfully',
    ]);
}
public function getDepartments()
{
    $departments = Section::all();
    return response()->json($departments);
}





public function destroyClass($id)
{
    $class = Classes::find($id);

    if (!$class) {
        return response()->json(['message' => 'Class not found', 'success' => false], 404);
    }

    $class->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Class deleted successfully',
    ]);
}


// Methods for the Divisons

public function getDivision(Request $request)
{
    $academicYr = $request->header('X-Academic-Year');    
    $divisions = Division::with('getClass.getDepartment')
                         ->where('academic_yr', $academicYr)
                         ->get();    
    return response()->json($divisions);
}

public function store(Request $request)
{
    $academicYr = $request->header('X-Academic-Year');   

    $division = new Division();
    $division->name = $request->name;
    $division->class_id = $request->class_id;
    $division->academic_yr = $academicYr;
    $division->save();
    return response()->json([
        'status' => 200,
        'message' => 'Class created successfully',
    ]);
}



public function show($id)
{
       $division = Division::with('getClass')->find($id);

    if (is_null($division)) {
        return response()->json(['message' => 'Division not found'], 404);
    }

    return response()->json($division);
}


public function updateDivision(Request $request, $id)
{
    $academicYr = $request->header('X-Academic-Year');   

    $division = Division::find($id);
    if (!$division) {
        return response()->json([
            'status' => 404,
            'message' => 'Division not found',
        ], 404);
    }

    $division->name = $request->name;
    $division->class_id = $request->class_id;
    $division->academic_yr = $academicYr;
    $division->save();

    return response()->json([
        'status' => 200,
        'message' => 'Division updated successfully',
    ]);
}


public function destroy($id)
{
    $division = Division::find($id);

    if (is_null($division)) {
        return response()->json(['message' => 'Division not found'], 404);
    }

    $division->delete();

    return response()->json(['message' => 'Division deleted successfully']);
}

   






}
