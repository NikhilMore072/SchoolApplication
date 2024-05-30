 <?php

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\LoginController;
// use App\Http\Controllers\StudentController;


// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::get('students', [StudentController::class, 'index']); 
// Route::post('students', [StudentController::class, 'store']); 
// Route::get('students/{id}/edit', [StudentController::class, 'show']); 
// Route::put('students/{id}/update', [StudentController::class, 'update']); 
// Route::delete('/students/{id}/delete', [StudentController::class, 'destroy']);

// Route::post('/login', [LoginController::class, 'authenticate'])->name('login'); 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StudentController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('students', [StudentController::class, 'index']); 
    Route::post('students', [StudentController::class, 'store']); 
    Route::get('students/{id}/edit', [StudentController::class, 'show']); 
    Route::put('students/{id}/update', [StudentController::class, 'update']); 
    Route::delete('/students/{id}/delete', [StudentController::class, 'destroy']);
});

Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout'])->name('logout');







