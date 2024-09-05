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
        Route::post('/check_section_name', [MastersController::class, 'checkSectionName']);
        Route::get('/sections', [MastersController::class, 'listSections']);
        Route::post('/sections', [MastersController::class, 'storeSection']);
        Route::get('/sections/{id}/edit', [MastersController::class, 'editSection']);
        Route::put('/sections/{id}', [MastersController::class, 'updateSection']);
        Route::delete('/sections/{id}', [MastersController::class, 'deleteSection']);

        //Classes Module Route  
        Route::post('/check_class_name', [MastersController::class, 'checkClassName']);
        Route::get('/classes', [MastersController::class, 'getClass']);
        Route::post('/classes', [MastersController::class, 'storeClass']);
        Route::get('/classes/{id}', [MastersController::class, 'showClass']);
        Route::put('/classes/{id}', [MastersController::class, 'updateClass']);
        Route::delete('/classes/{id}', [MastersController::class, 'destroyClass']);

        // Division Module Routes 
        Route::post('/check_division_name', [MastersController::class, 'checkDivisionName']);
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
        Route::post('/check_subject_name', [MastersController::class, 'checkSubjectName']);
        Route::get('/subject', [MastersController::class, 'getSubjects']);
        Route::post('/subject', [MastersController::class, 'storeSubject']);
        Route::get('/subject/{id}', [MastersController::class, 'editSubject']);
        Route::put('/subject/{id}', [MastersController::class, 'updateSubject']);
        Route::delete('/subject/{id}', [MastersController::class, 'deleteSubject']);     
       

        // Manage Tab 
        Route::get('/getClassList', [MastersController::class, 'getClassList']);//done  //list the class 
        Route::get('/divisions-and-subjects/{class_id}', [MastersController::class, 'getDivisionsAndSubjects']);//  done list the division and subject by selected class,    
        Route::get('/get_class_section', [MastersController::class, 'getallClass']); //Done  list the class name with the division
        Route::get('/get_subject_Alloted', [MastersController::class, 'getSubjectAlloted']); //Done  list the subject allotment base on the selected section_id
        Route::get('/get_subject_Alloted/{subjectId}', [MastersController::class, 'editSubjectAllotment']);//Done    return the object of subject with associated details for the selected subject
        Route::put('/update_subject_Alloted/{subjectId}', [MastersController::class, 'updateSubjectAllotment']);//Done  update 
        Route::delete('/delete_subject_Alloted/{subjectId}', [MastersController::class, 'deleteSubjectAllotment']);// Done  delete 
         
        // Allot Subjects
        Route::get('/get_divisions_and_subjects/{classId}', [MastersController::class, 'getDivisionsAndSubjects']); //Done   list the division and  the subject which are already allocated.
        Route::post('/store_subject_allotment', [MastersController::class, 'storeSubjectAllotment']); //Done 

        // Allot Teacher for a class 
        Route::get('/subject-allotment/section/{section_id}', [MastersController::class, 'getSubjectAllotmentWithTeachersBySection']);//Done   list the subject and the teachers
        // Route::put('/teacher-allotment/update', [MastersController::class, 'updateTeacherAllotment']);
        Route::put('/subject-allotments/{classId}/{sectionId}', [MastersController::class, 'updateTeacherAllotment']);
       
        // Allot Teachers 
        Route::get('/get_divisions/{classId}', [MastersController::class, 'getDivisionsbyClass']); //Done  Allot teacher tab list the division for the selected class.
        Route::get('/get_subjects/{sectionId}', [MastersController::class, 'getSubjectsbyDivision']);  //Done   Allot teacher tab list the subject  for the selected Division. 
        Route::get('/get_presubjects/{classId}', [MastersController::class, 'getPresignSubjectByDivision']);  //Done   Allot teacher tab list the subject(Presign Subjects )  for the selected Division. 
        Route::get('/get_presubjectss/{sectionId}', [MastersController::class, 'getSubjectsByDivisionWithAssigned']);  //Done   Allot teacher tab list the subject(Presign Subjects )  for the selected Division. 
        Route::get('/get_teacher_list', [MastersController::class, 'getTeacherNames']); //Done  Get the teacher list 
        Route::get('/get_presign_subject_by_teacher/{classID}/{sectionId}/{teacherID}', [MastersController::class, 'getPresignSubjectByTeacher']); // get the list of the preasign subject base on the selected clss_id,section_id,teacher_id .
        Route::post('/allot-teacher-for-subject/{class_id}/{section_id}', [MastersController::class, 'updateOrCreateSubjectAllotments']);



        // Route::post('/allotTeacherForSubjects', [MastersController::class, 'allotTeacherForSubjects']);
        // Route::get('/class/{classId}/subjects-allotment', [MastersController::class, 'getSubjectsAndSectionsByClass']);
        // Route::post('/allocate-teacher-for-class', [MastersController::class, 'allocateTeacherForClass']);
        // Route::get('/subject-allotment/{subjectId}/edit', [MastersController::class, 'editallocateTeacherForClass']);
        // Route::put('/subject-allotment/{subjectId}', [MastersController::class, 'updateallocateTeacherForClass']);
        // Route::delete('/subject-allotment/{subjectId}', [MastersController::class, 'deleteSubjectAlloted']);


        // Route::get('/student_base_on_class_id', [MastersController::class, 'getStudentListBaseonClass']);
        Route::get('/getallClassWithStudentCount', [MastersController::class, 'getallSectionsWithStudentCount']);// Done for class dropdown.
        Route::get('/getStudentListBySection', [MastersController::class, 'getStudentListBySection']);// Done for student dropdown.
        Route::get('/students/{studentId}', [MastersController::class, 'getStudentById']); // Edit Student , for the view Student. and single student select for the list.
        Route::get('/student_by_reg_no/{reg_no}', [MastersController::class, 'getStudentByGRN']); // Student By GRN .
        Route::delete('/students/{studentId}', [MastersController::class, 'deleteStudent']);
        Route::patch('/students/{studentId}/deactivate', [MastersController::class, 'toggleActiveStudent']); // Done.
        Route::put('/students/{studentId}', [MastersController::class, 'updateStudentAndParent']);
      
        //routes for the SubjectForReportCard
        Route::post('/check_subject_name', [MastersController::class, 'checkSubjectName']);
        Route::get('/subject_for_reportcard', [MastersController::class, 'getSubjectsForReportCard']);
        Route::post('/subject_for_reportcard', [MastersController::class, 'storeSubjectForReportCard']);
        Route::get('/subject_for_reportcard/{sub_rc_master_id}', [MastersController::class, 'editSubjectForReportCard']);
        Route::put('/subject_for_reportcard/{sub_rc_master_id}', [MastersController::class, 'updateSubjectForReportCard']);
        Route::delete('/subject_for_reportcard/{sub_rc_master_id}', [MastersController::class, 'deleteSubjectForReportCard']);    
});



// Optionally, if you need to refresh tokens
Route::post('refresh', [AuthController::class, 'refresh']);

// Example of retrieving authenticated user information
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['jwt.auth']);

    



