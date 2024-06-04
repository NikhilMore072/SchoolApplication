 <?php


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
Route::get('/getdata', [LoginController::class, 'getdata'])->name('getdata');
Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::middleware([\Illuminate\Session\Middleware\StartSession::class])->get('/test-session', function (Request $request) {
    // Store a value in the session
    $request->session()->put('test_key', 'test_value');

    // Retrieve the value from the session
    $value = $request->session()->get('test_key');

    return response()->json(['session_value' => $value]);
});






