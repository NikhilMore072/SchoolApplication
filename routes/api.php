<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('students', [StudentController::class, 'index']); // Get all students
Route::post('students', [StudentController::class, 'store']); // Create a new student
Route::get('students/{id}', [StudentController::class, 'show']); // Get a specific student by ID
Route::put('students/{id}', [StudentController::class, 'update']); // Update a specific student by ID
Route::delete('students/{id}', [StudentController::class, 'destroy']); // Delete a specific student by ID
