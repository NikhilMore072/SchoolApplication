    <?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\RoleController;
    use App\Http\Controllers\AdminController;
    use App\Http\Controllers\LoginController;

    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

// Protected routes
    Route::middleware(['jwt.auth'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('sessionData', [AuthController::class, 'getUserDetails']);
        Route::post('update_academic_year', [AuthController::class, 'updateAcademicYear']);







        // Route::get('/getAuthUser', [AdminController::class, 'getAuthUser']);
        // Route::put('/updateauthacademicyear', [AdminController::class, 'updateAcademicYearForAuthUser']);
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
        Route::post('/check_section_name', [AdminController::class, 'checkSectionName']);
        Route::get('/sections', [AdminController::class, 'listSections']);
        Route::post('/sections', [AdminController::class, 'storeSection']);
        Route::get('/sections/{id}/edit', [AdminController::class, 'editSection']);
        Route::put('/sections/{id}', [AdminController::class, 'updateSection']);
        Route::delete('/sections/{id}', [AdminController::class, 'deleteSection']);

        //Classes Module Route  
        Route::post('/check_class_name', [AdminController::class, 'checkClassName']);
        Route::get('/classes', [AdminController::class, 'getClass']);
        Route::post('/classes', [AdminController::class, 'storeClass']);
        Route::get('/classes/{id}', [AdminController::class, 'showClass']);
        Route::put('/classes/{id}', [AdminController::class, 'updateClass']);
        Route::delete('/classes/{id}', [AdminController::class, 'destroyClass']);

        // Division Module Routes 
        Route::post('/check_division_name', [AdminController::class, 'checkDivisionName']);
        Route::get('/getDivision', [AdminController::class, 'getDivision']);
        Route::get('/get_class_for_division', [AdminController::class, 'getClassforDivision']);
        Route::post('/store_division', [AdminController::class, 'storeDivision']);
        Route::get('/getDivision/{id}', [AdminController::class, 'showDivision']);
        Route::put('/getDivision/{id}', [AdminController::class, 'updateDivision']);
        Route::delete('/getDivision/{id}', [AdminController::class, 'destroyDivision']);

        // Dashboard API   
        Route::get('/studentss', [AdminController::class, 'getStudentData']);
        Route::get('/staff', [AdminController::class, 'staff']);
        Route::get('/getbirthday', [AdminController::class, 'getbirthday']);
        Route::get('/events', [AdminController::class, 'getEvents']);
        Route::get('/parent-notices', [AdminController::class, 'getParentNotices']);
        Route::get('/staff-notices', [AdminController::class, 'getNoticesForTeachers']);
        Route::get('/getClassDivisionTotalStudents', [AdminController::class, 'getClassDivisionTotalStudents']);
        Route::get('/getHouseViseStudent', [AdminController::class, 'getHouseViseStudent']);
        Route::get('/staffbirthdaycount', [AdminController::class, 'staffBirthdaycount']);
        Route::get('/staffbirthdaylist', [AdminController::class, 'staffBirthdayList']);
        Route::get('/send_teacher_birthday_email', [AdminController::class, 'sendTeacherBirthdayEmail']);
        Route::get('/ticketcount', [AdminController::class, 'ticketCount']);
        Route::get('/ticketlist', [AdminController::class, 'getTicketList']);
        Route::get('/feecollection', [AdminController::class, 'feeCollection']);
        // Route::get('/fee_collection_list', [AdminController::class, 'feeCollectionList']);
        Route::get('/get_bank_accountName', [AdminController::class, 'getBankAccountName']);  
        Route::get('/getAcademicYear', [AdminController::class, 'getAcademicYears']);
        Route::get('/fee_collection_list', [AdminController::class, 'pendingCollectedFeeData']);
        // Route::get('/pending_collected_fee_data_list', [AdminController::class, 'pendingCollectedFeeDatalist']);
        Route::get('/collected_fee_list', [AdminController::class, 'collectedFeeList']);


        // Staff Module API 
        Route::get('/staff_list', [AdminController::class, 'getStaffList']);
        Route::post('/store_staff', [AdminController::class, 'storeStaff']);
        Route::get('/teachers/{id}', [AdminController::class, 'editStaff']);
        Route::put('/teachers/{id}', [AdminController::class, 'updateStaff']);
        Route::delete('/teachers/{id}', [AdminController::class, 'deleteStaff']);

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


        // Menus Model Routes 
        Route::get('/menus', [RoleController::class, 'getMenus']);
        Route::post('/menus', [RoleController::class, 'storeMenus']);
        Route::get('/menus/{id}', [RoleController::class, 'showMenus']);
        Route::put('/menus/{id}', [RoleController::class, 'updateMenus']);
        Route::delete('/menus/{id}', [RoleController::class, 'destroy']);

        // API for the subject master.
        Route::post('/check_subject_name', [AdminController::class, 'checkSubjectName']);
        Route::get('/subject', [AdminController::class, 'getSubjects']);
        Route::post('/subject', [AdminController::class, 'storeSubject']);
        Route::get('/subject/{id}', [AdminController::class, 'editSubject']);
        Route::put('/subject/{id}', [AdminController::class, 'updateSubject']);
        Route::delete('/subject/{id}', [AdminController::class, 'deleteSubject']);     
       

        // Subject Allotment Manage Tab 
        Route::get('/getClassList', [AdminController::class, 'getClassList']);//done  //list the class 
        Route::get('/divisions-and-subjects/{class_id}', [AdminController::class, 'getDivisionsAndSubjects']);//  done list the division and subject by selected class,    
        Route::get('/get_class_section', [AdminController::class, 'getallClass']); //Done  list the class name with the division
        Route::get('/get_subject_Alloted', [AdminController::class, 'getSubjectAlloted']); //Done  list the subject allotment base on the selected section_id
        Route::get('/get_subject_Alloted/{subjectId}', [AdminController::class, 'editSubjectAllotment']);//Done    return the object of subject with associated details for the selected subject
        Route::put('/update_subject_Alloted/{subjectId}', [AdminController::class, 'updateSubjectAllotment']);//Done  update 
        Route::delete('/delete_subject_Alloted/{subjectId}', [AdminController::class, 'deleteSubjectAllotment']);// Done  delete 
         
        // Allot Subjects
        Route::get('/get_divisions_and_subjects/{classId}', [AdminController::class, 'getDivisionsAndSubjects']); //Done   list the division and  the subject which are already allocated.
        Route::post('/store_subject_allotment', [AdminController::class, 'storeSubjectAllotment']); //Done 

        // Allot Teacher for a class 
        Route::get('/subject-allotment/section/{section_id}', [AdminController::class, 'getSubjectAllotmentWithTeachersBySection']);//Done   list the subject and the teachers
        // Route::put('/teacher-allotment/update', [AdminController::class, 'updateTeacherAllotment']);
        Route::put('/subject-allotments/{classId}/{sectionId}', [AdminController::class, 'updateTeacherAllotment']);
       
        // Allot Teachers 
        Route::get('/get_divisions/{classId}', [AdminController::class, 'getDivisionsbyClass']); //Done  Allot teacher tab list the division for the selected class.
        Route::get('/get_subjects/{sectionId}', [AdminController::class, 'getSubjectsbyDivision']);  //Done   Allot teacher tab list the subject  for the selected Division. 
        Route::get('/get_presubjects/{classId}', [AdminController::class, 'getPresignSubjectByDivision']);  //Done   Allot teacher tab list the subject(Presign Subjects )  for the selected Division. 
        Route::get('/get_presubjectss/{sectionId}', [AdminController::class, 'getSubjectsByDivisionWithAssigned']);  //Done   Allot teacher tab list the subject(Presign Subjects )  for the selected Division. 
        Route::get('/get_teacher_list', [AdminController::class, 'getTeacherNames']); //Done  Get the teacher list 
        Route::get('/get_presign_subject_by_teacher/{classID}/{sectionId}/{teacherID}', [AdminController::class, 'getPresignSubjectByTeacher']); // get the list of the preasign subject base on the selected clss_id,section_id,teacher_id .
        Route::post('/allot-teacher-for-subject/{class_id}/{section_id}', [AdminController::class, 'updateOrCreateSubjectAllotments']);



        // Route::post('/allotTeacherForSubjects', [AdminController::class, 'allotTeacherForSubjects']);
        // Route::get('/class/{classId}/subjects-allotment', [AdminController::class, 'getSubjectsAndSectionsByClass']);
        // Route::post('/allocate-teacher-for-class', [AdminController::class, 'allocateTeacherForClass']);
        // Route::get('/subject-allotment/{subjectId}/edit', [AdminController::class, 'editallocateTeacherForClass']);
        // Route::put('/subject-allotment/{subjectId}', [AdminController::class, 'updateallocateTeacherForClass']);
        // Route::delete('/subject-allotment/{subjectId}', [AdminController::class, 'deleteSubjectAlloted']);


        // Route::get('/student_base_on_class_id', [AdminController::class, 'getStudentListBaseonClass']);

        // Student Model Routes.
        Route::get('/getallClassWithStudentCount', [AdminController::class, 'getallSectionsWithStudentCount']);// Done for class dropdown.
        Route::get('/getStudentListBySection', [AdminController::class, 'getStudentListBySection']);// Done for student dropdown.
        Route::get('/students/{studentId}', [AdminController::class, 'getStudentById']); // Edit Student , for the view Student. and single student select for the list.
        Route::get('/student_by_reg_no/{reg_no}', [AdminController::class, 'getStudentByGRN']); // Student By GRN .
        Route::delete('/students/{studentId}', [AdminController::class, 'deleteStudent']);
        Route::patch('/students/{studentId}/deactivate', [AdminController::class, 'toggleActiveStudent']); // Done.
        Route::put('/students/{studentId}', [AdminController::class, 'updateStudentAndParent']);  
        Route::get('/check-user-id/{studentId}/{userId}', [AdminController::class, 'checkUserId']);  // API for the User_id unique check 
        Route::put('/resetPasssword/{user_id}', [AdminController::class, 'resetPasssword']);        

        //routes for the SubjectForReportCard
        Route::post('/check_subject_name_for_report_card', [AdminController::class, 'checkSubjectNameForReportCard']);
        Route::get('/subject_for_reportcard', [AdminController::class, 'getSubjectsForReportCard']);
        Route::post('/subject_for_reportcard', [AdminController::class, 'storeSubjectForReportCard']);
        Route::get('/subject_for_reportcard/{sub_rc_master_id}', [AdminController::class, 'editSubjectForReportCard']);
        Route::put('/subject_for_reportcard/{sub_rc_master_id}', [AdminController::class, 'updateSubjectForReportCard']);
        Route::delete('/subject_for_reportcard/{sub_rc_master_id}', [AdminController::class, 'deleteSubjectForReportCard']);
        
        //routes for the SubjectAllotment for the Report Card 
        Route::get('/get_subject_Alloted_for_report_card/{class_id}', [AdminController::class, 'getSubjectAllotmentForReportCard']);
        Route::get('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'getSubjectAllotmentById']);
        Route::put('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'updateSubjectType']);
        Route::delete('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'deleteSubjectAllotmentforReportcard']);
        Route::get('/get_sub_report_allotted/{class_id}/{subject_type}', [AdminController::class, 'editSubjectAllotmentforReportCard']);
        // Route::put('/get_sub_report_allotted/{class_id}', [AdminController::class, 'createOrUpdateSubjectAllotment']);
        Route::post('/subject-allotments-reportcard/{class_id}', [AdminController::class, 'createOrUpdateSubjectAllotment']);
});

Route::get('/students/download-template/{section_id}', [LoginController::class, 'downloadCsvTemplateWithData']);
// Route::post('/update-students-csv', [LoginController::class, 'updateCsvData']);
Route::post('/update-students-csv/{section_id}', [LoginController::class, 'updateCsvData']);
Route::get('/get_student_by_sectionId/{section_id}', [LoginController::class, 'getStudentListbysectionforregister']);
Route::get('/get_all_studentlist', [LoginController::class, 'getAllStudentListForRegister']);   


// Optionally, if you need to refresh tokens
Route::post('refresh', [AuthController::class, 'refresh']);

// Example of retrieving authenticated user information
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['jwt.auth']);

    



