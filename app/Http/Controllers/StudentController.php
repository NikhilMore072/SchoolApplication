<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::all();
        return response()->json($students);
    }
    

    public function list()
    {
        $students = Student::all();
        return view('studentlist',compact('students'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'house' => 'nullable|string|max:255',
            'admitted_in_class' => 'nullable|string|max:255',
            'gender' => 'required|string|max:10',
            'blood_group' => 'nullable|string|max:5',
            'nationality' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'mother_tongue' => 'nullable|string|max:255',
            'emergency_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'date_of_admission' => 'required|date',
            'grn_no' => 'nullable|string|max:255',
            'student_id_no' => 'nullable|string|max:255',
            'student_aadhaar_no' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:255',
            'caste' => 'nullable|string|max:255',
            'emergency_address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:20',
            'transport_mode' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'allergies' => 'nullable|string|max:255',
            'height' => 'nullable|numeric',
            'roll_no' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'terms_and_conditions' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'has_spectacles' => 'nullable|boolean',
        ]);

        $student = Student::create($validatedData);
        return response()->json([
            'status' => 200,
            'message' => 'Student created successfully',
        ], 201);
    }

    public function show($id)
    {
        $student = Student::findOrFail($id);
        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'house' => 'nullable|string|max:255',
            'admitted_in_class' => 'nullable|string|max:255',
            'gender' => 'required|string|max:10',
            'blood_group' => 'nullable|string|max:5',
            'nationality' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'mother_tongue' => 'nullable|string|max:255',
            'emergency_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'date_of_admission' => 'required|date',
            'grn_no' => 'nullable|string|max:255',
            'student_id_no' => 'nullable|string|max:255',
            'student_aadhaar_no' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:255',
            'caste' => 'nullable|string|max:255',
            'emergency_address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:20',
            'transport_mode' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'allergies' => 'nullable|string|max:255',
            'height' => 'nullable|numeric',
            'roll_no' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'terms_and_conditions' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'has_spectacles' => 'nullable|boolean',
        ]);

        $student = Student::findOrFail($id);
        $student->update($validatedData);
        return response()->json([
            'status' => 200,
            'message' => 'Student Updated successfully',
        ], 201);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Student Deleted successfully',
        ], 201);
    }
}
