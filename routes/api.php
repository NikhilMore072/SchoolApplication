    <?php




    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\LoginController;
    use App\Http\Controllers\MastersController;
    use App\Http\Controllers\StudentController;
    use Illuminate\Session\Middleware\StartSession;




    Route::middleware([StartSession::class])->post('/login', [LoginController::class, 'authenticate'])->name('login');
    Route::middleware(['auth:sanctum', StartSession::class])->group(function () {

        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/session-data', [LoginController::class, 'getSessionData']);
        Route::get('/getAcademicyear', [LoginController::class, 'getAcademicyear']);
        Route::put('/updateAcademicYear', [LoginController::class, 'updateAcademicYear']);


        //Master and its sub module routes  Module Routes 
        //Section model Routes 
        Route::get('/sections', [MastersController::class, 'showSection']);
        Route::post('/sections', [MastersController::class, 'storeSection']);
        Route::get('/sections/{id}/edit', [MastersController::class, 'editSection']);
        Route::put('/sections/{id}', [MastersController::class, 'updateSection']);
        Route::delete('/sections/{id}', [MastersController::class, 'deleteSection']);

        //Classes Module Route  
        Route::get('/classes', [MastersController::class, 'getClass']);
        Route::post('/classes', [MastersController::class, 'storeClass']);
        Route::get('/classes/{id}', [MastersController::class, 'showClass']);
        Route::put('/classes/{id}', [MastersController::class, 'updateClass']);
        Route::delete('/classes/{id}', [MastersController::class, 'destroyClass']);

        // Division Module Routes 
        Route::get('/getDivision', [MastersController::class, 'getDivision']);


        // Dashboard API   
        Route::get('/studentss', [MastersController::class, 'getStudentData']);
        Route::get('/staff', [MastersController::class, 'staff']);
        Route::get('/getbirthday', [MastersController::class, 'getbirthday']);
        Route::get('/events', [MastersController::class, 'getEvents']);
        Route::get('/parent-notices', [MastersController::class, 'getParentNotices']);
        Route::get('/staff-notices', [MastersController::class, 'getNoticesForTeachers']);
        Route::get('/getClassDivisionTotalStudents', [MastersController::class, 'getClassDivisionTotalStudents']);
        Route::get('/staffbirthdaycount', [MastersController::class, 'staffBirthdaycount']);
        Route::get('/staffbirthdaylist', [MastersController::class, 'staffBirthdayList']);
        Route::get('/send_teacher_birthday_email', [MastersController::class, 'sendTeacherBirthdayEmail']);
        Route::get('/ticketcount', [MastersController::class, 'ticketCount']);
        Route::get('/ticketlist', [MastersController::class, 'getTicketList']);
        Route::get('/feecollection', [MastersController::class, 'feeCollection']);
        Route::get('/fee_collection_list', [MastersController::class, 'feeCollectionList']);

        //Students Module Routes 
        Route::get('students', [StudentController::class, 'index']); 
        Route::post('students', [StudentController::class, 'store']); 
        Route::get('students/{id}/edit', [StudentController::class, 'show']); 
        Route::put('students/{id}/update', [StudentController::class, 'update']); 
        Route::delete('/students/{id}/delete', [StudentController::class, 'destroy']);

    });



