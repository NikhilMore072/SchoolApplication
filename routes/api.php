    <?php

    // use Illuminate\Http\Request;
    // use Illuminate\Support\Facades\Route;
    // use App\Http\Controllers\LoginController;
    // use App\Http\Controllers\MastersController;
    // use App\Http\Controllers\StudentController;
    // use Illuminate\Session\Middleware\StartSession;




    // Route::middleware([StartSession::class])->post('/login', [LoginController::class, 'login'])->name('login');
    // Route::middleware(['auth:sanctum', StartSession::class,])->group(function () {
    //     Route::get('/getAuthUser', [MastersController::class, 'getAuthUser']);
    //     Route::put('/updateauthacademicyear', [MastersController::class, 'updateAcademicYearForAuthUser']);
    //     Route::get('/someControllerMethod', [LoginController::class, 'someControllerMethod']);

        
    //     Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    //     Route::get('/session-data', [LoginController::class, 'getSessionData']);
    //     // Route::get('/getAcademicyear', [LoginController::class, 'getAcademicyear']);
    //     Route::put('/updateAcademicYear', [LoginController::class, 'updateAcademicYear']);
    //     Route::post('/clearData', [LoginController::class, 'clearData'])->name('clearData');
    //     Route::put('/update_password', [LoginController::class, 'updatePassword']);
    //     Route::get('/editprofile', [LoginController::class, 'editUser']);
    //     Route::put('/update_profile', [LoginController::class, 'updateUser']);



    //     //Master and its sub module routes  Module Routes 
    //     //Section model Routes 
    //     Route::get('/sections', [MastersController::class, 'listSections']);
    //     Route::post('/sections', [MastersController::class, 'storeSection']);
    //     Route::get('/sections/{id}/edit', [MastersController::class, 'editSection']);
    //     Route::put('/sections/{id}', [MastersController::class, 'updateSection']);
    //     Route::delete('/sections/{id}', [MastersController::class, 'deleteSection']);

    //     //Classes Module Route  
    //     Route::get('/classes', [MastersController::class, 'getClass']);
    //     Route::post('/classes', [MastersController::class, 'storeClass']);
    //     Route::get('/classes/{id}', [MastersController::class, 'showClass']);
    //     Route::put('/classes/{id}', [MastersController::class, 'updateClass']);
    //     Route::delete('/classes/{id}', [MastersController::class, 'destroyClass']);

    //     // Division Module Routes 
    //     Route::get('/getDivision', [MastersController::class, 'getDivision']);

    //     // Dashboard API   
    //     Route::get('/studentss', [MastersController::class, 'getStudentData']);
    //     Route::get('/staff', [MastersController::class, 'staff']);
    //     Route::get('/getbirthday', [MastersController::class, 'getbirthday']);
    //     Route::get('/events', [MastersController::class, 'getEvents']);
    //     Route::get('/parent-notices', [MastersController::class, 'getParentNotices']);
    //     Route::get('/staff-notices', [MastersController::class, 'getNoticesForTeachers']);
    //     Route::get('/getClassDivisionTotalStudents', [MastersController::class, 'getClassDivisionTotalStudents']);
    //     Route::get('/getHouseViseStudent', [MastersController::class, 'getHouseViseStudent']);
    //     Route::get('/staffbirthdaycount', [MastersController::class, 'staffBirthdaycount']);
    //     Route::get('/staffbirthdaylist', [MastersController::class, 'staffBirthdayList']);
    //     Route::get('/send_teacher_birthday_email', [MastersController::class, 'sendTeacherBirthdayEmail']);
    //     Route::get('/ticketcount', [MastersController::class, 'ticketCount']);
    //     Route::get('/ticketlist', [MastersController::class, 'getTicketList']);
    //     Route::get('/feecollection', [MastersController::class, 'feeCollection']);
    //     Route::get('/fee_collection_list', [MastersController::class, 'feeCollectionList']);
    //     Route::get('/get_bank_accountName', [MastersController::class, 'getBankAccountName']);  
    //     Route::get('/getAcademicYear', [MastersController::class, 'getAcademicYears']);
    //     Route::get('/pending_collected_fee_data', [MastersController::class, 'pendingCollectedFeeData']);
    //     Route::get('/pending_collected_fee_data_list', [MastersController::class, 'pendingCollectedFeeDatalist']);
    //     Route::get('/collected_fee_list', [MastersController::class, 'collectedFeeList']);


    //     //Students Module Routes 
    //     Route::get('students', [StudentController::class, 'index']); 
    //     Route::post('students', [StudentController::class, 'store']); 
    //     Route::get('students/{id}/edit', [StudentController::class, 'show']); 
    //     Route::put('students/{id}/update', [StudentController::class, 'update']); 
    //     Route::delete('/students/{id}/delete', [StudentController::class, 'destroy']);


    //     // Staff Module API 
    //     Route::get('/staff_list', [MastersController::class, 'getStaffList']);
    //     Route::post('/store_staff', [MastersController::class, 'storeStaff']);
    //     Route::get('/teachers/{id}', [MastersController::class, 'editStaff']);
    //     Route::put('/teachers/{id}', [MastersController::class, 'updateStaff']);
    //     Route::delete('/teachers/{id}', [MastersController::class, 'deleteStaff']);

    // });

    // Route::middleware([StartSession::class])->get('/session-data', function (Request $request) {
    //     $sessionData = Session::get('sessionData');
    
    //     // Log retrieved session data for debugging
    //     Log::info('Retrieved Session Data:', [$sessionData]); // Wrap $sessionData in an array
        
    //     if (!$sessionData) {
    //         return response()->json(['message' => 'No session data found', 'success' => false], 404);
    //     }
    
    //     return response()->json(['sessionData' => $sessionData, 'success' => true], 200);
    // });



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MastersController;

// Public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('sessionData', [AuthController::class, 'getUserDetails']);
    Route::post('update_academic_year', [AuthController::class, 'updateAcademicYear']);


        // Route::get('/getAuthUser', [MastersController::class, 'getAuthUser']);
        // Route::put('/updateauthacademicyear', [MastersController::class, 'updateAcademicYearForAuthUser']);
        // Route::get('/someControllerMethod', [LoginController::class, 'someControllerMethod']);

        
        // Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        // Route::get('/session-data', [LoginController::class, 'getSessionData']);
        Route::get('/getAcademicyear', [LoginController::class, 'getAcademicyear']);
        // Route::put('/updateAcademicYear', [LoginController::class, 'updateAcademicYear']);
        Route::post('/clearData', [LoginController::class, 'clearData'])->name('clearData');
        Route::put('/update_password', [LoginController::class, 'updatePassword']);
        Route::get('/editprofile', [AuthController::class, 'editUser']);
        Route::put('/update_profile', [AuthController::class, 'updateUser']);

        //Master and its sub module routes  Module Routes 
        //Section model Routes 
        Route::get('/sections', [MastersController::class, 'listSections']);
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
        Route::get('/get_class_for_division', [MastersController::class, 'getClassforDivision']);
        Route::post('/store_division', [MastersController::class, 'storeDivision']);
        Route::get('/getDivision/{id}', [MastersController::class, 'showDivision']);
        Route::put('/getDivision/{id}', [MastersController::class, 'updateDivision']);
        Route::delete('/getDivision/{id}', [MastersController::class, 'destroyDivision']);

        // Dashboard API   
        Route::get('/studentss', [MastersController::class, 'getStudentData']);
        Route::get('/staff', [MastersController::class, 'staff']);
        Route::get('/getbirthday', [MastersController::class, 'getbirthday']);
        Route::get('/events', [MastersController::class, 'getEvents']);
        Route::get('/parent-notices', [MastersController::class, 'getParentNotices']);
        Route::get('/staff-notices', [MastersController::class, 'getNoticesForTeachers']);
        Route::get('/getClassDivisionTotalStudents', [MastersController::class, 'getClassDivisionTotalStudents']);
        Route::get('/getHouseViseStudent', [MastersController::class, 'getHouseViseStudent']);
        Route::get('/staffbirthdaycount', [MastersController::class, 'staffBirthdaycount']);
        Route::get('/staffbirthdaylist', [MastersController::class, 'staffBirthdayList']);
        Route::get('/send_teacher_birthday_email', [MastersController::class, 'sendTeacherBirthdayEmail']);
        Route::get('/ticketcount', [MastersController::class, 'ticketCount']);
        Route::get('/ticketlist', [MastersController::class, 'getTicketList']);
        Route::get('/feecollection', [MastersController::class, 'feeCollection']);
        // Route::get('/fee_collection_list', [MastersController::class, 'feeCollectionList']);
        Route::get('/get_bank_accountName', [MastersController::class, 'getBankAccountName']);  
        Route::get('/getAcademicYear', [MastersController::class, 'getAcademicYears']);
        Route::get('/fee_collection_list', [MastersController::class, 'pendingCollectedFeeData']);
        // Route::get('/pending_collected_fee_data_list', [MastersController::class, 'pendingCollectedFeeDatalist']);
        Route::get('/collected_fee_list', [MastersController::class, 'collectedFeeList']);


        //Students Module Routes 
        // Route::get('students', [StudentController::class, 'index']); 
        // Route::post('students', [StudentController::class, 'store']); 
        // Route::get('students/{id}/edit', [StudentController::class, 'show']); 
        // Route::put('students/{id}/update', [StudentController::class, 'update']); 
        // Route::delete('/students/{id}/delete', [StudentController::class, 'destroy']);


        // Staff Module API 
        Route::get('/staff_list', [MastersController::class, 'getStaffList']);
        Route::post('/store_staff', [MastersController::class, 'storeStaff']);
        Route::get('/teachers/{id}', [MastersController::class, 'editStaff']);
        Route::put('/teachers/{id}', [MastersController::class, 'updateStaff']);
        Route::delete('/teachers/{id}', [MastersController::class, 'deleteStaff']);



        // Roles Routes 
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'delete'])->name('roles.delete');

        //Showing Roles with the Permissions   showRoles
        Route::get('/show_roles', [RoleController::class, 'showRoles']);
        Route::get('/show_access/{roleId}', [RoleController::class, 'showAccess']);
        Route::post('/update_access/{roleId}', [RoleController::class, 'updateAccess']);
        Route::get('/navmenulist', [RoleController::class, 'navMenulist']);

        Route::get('/menus', [RoleController::class, 'getMenus']);
        Route::post('/menus', [RoleController::class, 'storeMenus']);
        Route::get('/menus/{id}', [RoleController::class, 'showMenus']);
        Route::put('/menus/{id}', [RoleController::class, 'updateMenus']);
        Route::delete('/menus/{id}', [RoleController::class, 'destroy']);

        // API for the subject master.
        Route::get('/subject', [MastersController::class, 'getSubjects']);
        Route::post('/subject', [MastersController::class, 'storeSubject']);
        Route::get('/subject/{id}', [MastersController::class, 'editSubject']);
        Route::put('/subject/{id}', [MastersController::class, 'updateSubject']);
        Route::delete('/subject/{id}', [MastersController::class, 'deleteSubject']);     
       


        Route::get('/get_subject_Alloted', [MastersController::class, 'getSubjectAlloted']);
        Route::get('/class/{classId}/subjects-sections', [MastersController::class, 'getSubjectsAndSectionsByClass']);
        Route::post('/store_subject_allotment', [MastersController::class, 'storeSubjectAllotment']);
        Route::get('/get_all_class', [MastersController::class, 'getallClass']);
        Route::post('/allocate-teacher-for-class', [MastersController::class, 'allocateTeacherForClass']);
        Route::get('/subject-allotment/{subjectId}/edit', [MastersController::class, 'editallocateTeacherForClass']);
        Route::put('/subject-allotment/{subjectId}', [MastersController::class, 'updateallocateTeacherForClass']);
        Route::delete('/subject-allotment/{subjectId}', [MastersController::class, 'deleteSubjectAlloted']);


        // Route::get('/student_base_on_class_id', [MastersController::class, 'getStudentListBaseonClass']);
        Route::get('/getallClassWithStudentCount', [MastersController::class, 'getallSectionsWithStudentCount']);
        Route::get('/getStudentListBySection', [MastersController::class, 'getStudentListBySection']);
        Route::get('/students/{studentId}', [MastersController::class, 'getStudentById']);
        Route::delete('/students/{studentId}', [MastersController::class, 'deleteStudent']);
        Route::patch('/students/{studentId}/deactivate', [MastersController::class, 'inAvateStudent']);
        Route::put('/students/{studentId}', [MastersController::class, 'updateStudentAndParent']);
});



// Optionally, if you need to refresh tokens
Route::post('refresh', [AuthController::class, 'refresh']);

// Example of retrieving authenticated user information
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['jwt.auth']);

    



