<?php

namespace App\Http\Controllers;

use Exception;
use Validator;
use App\Models\User;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Classes;
use App\Models\Parents;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Division;
use App\Mail\WelcomeEmail;
use App\Models\Attendence;
use App\Models\UserMaster;
use App\Models\MarkHeading;
use App\Models\StaffNotice;
use Illuminate\Http\Request;
use App\Models\SubjectMaster;
use App\Models\ContactDetails;
use Illuminate\Support\Carbon;
use App\Models\BankAccountName;
use Illuminate\Validation\Rule;
use App\Models\SubjectAllotment;
use Illuminate\Http\JsonResponse;
use App\Mail\TeacherBirthdayEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Models\SubjectForReportCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\DeletedContactDetails;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\SubjectAllotmentForReportCard;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Support\Facades\Auth;


class AdminController extends Controller
{
    public function hello(){
        return view('hello');
    }

public function sendTeacherBirthdayEmail()
{
    $currentMonth = Carbon::now()->format('m');
    $currentDay = Carbon::now()->format('d');

    $teachers = Teacher::whereMonth('birthday', $currentMonth)
                        ->whereDay('birthday', $currentDay)
                        ->get();

    foreach ($teachers as $teacher) {
        $textmsg = "Dear {$teacher->name},<br><br>";
        $textmsg .= "Wishing you many happy returns of the day. May the coming year be filled with peace, prosperity, good health, and happiness.<br/><br/>";
        $textmsg .= "Best Wishes,<br/>";
        $textmsg .= "St. Arnolds Central School";

        $data = [
            'title' => 'Birthday Greetings!!',
            'body' => $textmsg,
            'teacher' => $teacher
        ];

        Mail::to($teacher->email)->send(new TeacherBirthdayEmail($data));
    }

    return response()->json(['message' => 'Birthday emails sent successfully']);
}



    public function getAcademicyearlist(Request $request){

        $academicyearlist = Setting::get()->academic_yr;
        return response()->json($academicyearlist);

          }

    public function getStudentData(Request $request){

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');  

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }
        $count = Student::where('IsDelete', 'N')
                          ->where('academic_yr',$academicYr)
                          ->count();
        $currentDate = Carbon::now()->toDateString();
        $present = Attendence::where('only_date', $currentDate)
                            ->where('attendance_status', '0')
                            ->where('academic_yr',$academicYr)
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


    public function staffBirthdaycount(Request $request)
{
    $currentDate = Carbon::now();
    $count = Teacher::where('IsDelete', 'N')
                     ->whereMonth('birthday', $currentDate->month)
                     ->whereDay('birthday', $currentDate->day)
                     ->count();

    return response()->json([
        'count' => $count,       
    ]);
}

public function staffBirthdayList(Request $request)
{
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year'); 
        if (!$academicYr) {
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }

    $currentDate = Carbon::now();

    $staffBirthday = Teacher::where('IsDelete', 'N')
        ->whereMonth('birthday', $currentDate->month)
        ->whereDay('birthday', $currentDate->day)
        ->get();

    return response()->json([
        'staffBirthday' => $staffBirthday,
    ]);
}


    public function getEvents(Request $request): JsonResponse
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year'); 
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }

        $currentDate = Carbon::now();
        $month = $request->input('month', $currentDate->month);
        $year = $request->input('year', $currentDate->year);

        $events = Event::select([
                'events.unq_id',
                'events.title',
                'events.event_desc',
                'events.start_date',
                'events.end_date',
                'events.start_time',
                'events.end_time',
                DB::raw('GROUP_CONCAT(class.name) as class_name')
            ])
            ->join('class', 'events.class_id', '=', 'class.class_id')
            ->where('events.isDelete', 'N')
            ->where('events.publish', 'Y')
            ->where('events.academic_yr', $academicYr)
            ->whereMonth('events.start_date', $month)
            ->whereYear('events.start_date', $year)
            ->groupBy('events.unq_id', 'events.title', 'events.event_desc', 'events.start_date', 'events.end_date', 'events.start_time', 'events.end_time')
            ->orderBy('events.start_date')
            ->orderByDesc('events.start_time')
            ->get();

        return response()->json($events);
    }


    public function getParentNotices(Request $request): JsonResponse
    {
        // $academicYr = $request->header('X-Academic-Year');
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year'); 
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }

        // Retrieve parent notices with their related class names
        $parentNotices = Notice::select([
                'subject',
                'notice_desc',
                'notice_date',
                'notice_type',
                \DB::raw('GROUP_CONCAT(class.name) as class_name')
            ])
            ->join('class', 'notice.class_id', '=', 'class.class_id') // Adjusted table name to singular 'class'
            ->where('notice.publish', 'Y')
            ->where('notice.academic_yr', $academicYr)
            ->groupBy('notice.subject', 'notice.notice_desc', 'notice.notice_date', 'notice.notice_type')
            ->orderBy('notice_date')
            ->get();

        return response()->json(['parent_notices' => $parentNotices]);
    }

    public function getNoticesForTeachers(Request $request): JsonResponse
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year'); 
        // Fetch notices with teacher names
        $notices = StaffNotice::select([
                'staff_notice.subject',
                'staff_notice.notice_desc',
                'staff_notice.notice_date',
                'staff_notice.notice_type',
                DB::raw('GROUP_CONCAT(t.name) as staff_name')
            ])
            ->join('teacher as t', 't.teacher_id', '=', 'staff_notice.teacher_id')
            ->where('staff_notice.publish', 'Y')
            ->where('staff_notice.academic_yr', $academicYr)
            ->groupBy('staff_notice.subject', 'staff_notice.notice_desc', 'staff_notice.notice_date', 'staff_notice.notice_type')
            ->orderBy('staff_notice.notice_date')
            ->get();

        return response()->json(['notices' => $notices, 'success' => true]);
    }

// public function getClassDivisionTotalStudents()
// {
//     $results = DB::table('class as c')
//         ->leftJoin('section as s', 'c.class_id', '=', 's.class_id')
//         ->leftJoin(DB::raw('(SELECT section_id, COUNT(student_id) AS students_count FROM student GROUP BY section_id) as st'), 's.section_id', '=', 'st.section_id')
//         ->select(
//             DB::raw("CONCAT(c.name, ' ', COALESCE(s.name, 'No division assigned')) AS class_division"),
//             DB::raw("SUM(st.students_count) AS total_students"),
//             'c.name as class_name',
//             's.name as section_name'
//         )
//         ->groupBy('c.name', 's.name')
//         ->orderBy('c.name')
//         ->orderBy('s.name')
//         ->get();

//     return response()->json($results);
// }

public function getClassDivisionTotalStudents(Request $request)
{
    // Get the academic year from the token payload
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    // Validate academic year
    if (!$academicYr) {
        return response()->json(['error' => 'Academic year is missing'], 400);
    }

    $results = DB::table('class as c')
        ->leftJoin('section as s', 'c.class_id', '=', 's.class_id')
        ->leftJoin(DB::raw("
            (SELECT section_id, COUNT(student_id) AS students_count
             FROM student
             WHERE academic_yr = '{$academicYr}'  -- Filter by academic year
             GROUP BY section_id) as st
        "), 's.section_id', '=', 'st.section_id')
        ->select(
            DB::raw("CONCAT(c.name, ' ', COALESCE(s.name, 'No division assigned')) AS class_division"),
            DB::raw("SUM(st.students_count) AS total_students"),
            'c.name as class_name',
            's.name as section_name'
        )
        ->groupBy('c.name', 's.name')
        ->orderBy('c.name')
        ->orderBy('s.name')
        ->get();

    return response()->json($results);
}


 public function ticketCount(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $role_id = $payload->get('role_id');

    $count = DB::table('ticket')
           ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
           ->where('service_type.role_id',$role_id)
           ->where('ticket.acd_yr',$academicYr)
           ->where('ticket.status', '!=', 'Closed')
           ->count();

           return response()->json(['count' => $count]);
 }
 public function getTicketList(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $role_id = $payload->get('role_id');

    $tickets = DB::table('ticket')
             ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
             ->join('student', 'ticket.student_id', '=', 'student.student_id')
             ->where('service_type.role_id', $role_id)
             ->where('ticket.acd_yr',$academicYr)
             ->where('ticket.status', '!=', 'Closed')
             ->orderBy('ticket.raised_on', 'DESC')
             ->select(
                 'ticket.*', 
                 'service_type.service_name', 
                 'student.first_name', 
                 'student.mid_name', 
                 'student.last_name'
             )
             ->get();

return response()->json($tickets);

 }

 public function feeCollection(Request $request) {
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 

    DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    $sql = "
        SELECT SUM(installment_fees - concession - paid_amount) AS pending_fee 
        FROM (
            SELECT s.student_id, s.installment, installment_fees, COALESCE(SUM(d.amount), 0) AS concession, 0 AS paid_amount 
            FROM view_student_fees_category s
            LEFT JOIN fee_concession_details d ON s.student_id = d.student_id AND s.installment = d.installment 
            WHERE s.academic_yr = ? AND due_date < CURDATE() 
                AND s.student_installment NOT IN (
                    SELECT student_installment 
                    FROM view_student_fees_payment a 
                    WHERE a.academic_yr = ?
                ) 
            GROUP BY s.student_id, s.installment, installment_fees

            UNION

            SELECT f.student_id AS student_id, b.installment AS installment, b.installment_fees, COALESCE(SUM(c.amount), 0) AS concession, SUM(f.fees_paid) AS paid_amount 
            FROM view_student_fees_payment f
            LEFT JOIN fee_concession_details c ON f.student_id = c.student_id AND f.installment = c.installment 
            JOIN view_fee_allotment b ON f.fee_allotment_id = b.fee_allotment_id AND b.installment = f.installment 
            WHERE f.academic_yr = ?
            GROUP BY f.student_id, b.installment, b.installment_fees, c.installment, b.fees_category_id
            HAVING (b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)
        ) as z
    ";

    $results = DB::select($sql, [$academicYr, $academicYr, $academicYr]);

    $pendingFee = $results[0]->pending_fee;

    return response()->json($pendingFee);
}


// public function getHouseViseStudent(Request $request) {
//     $className = $request->input('class_name');
//     // $academicYear = $request->header('X-Academic-Year');
//     $sessionData = session('sessionData');
//     if (!$sessionData) {
//         return response()->json(['message' => 'Session data not found', 'success' => false], 404);
//     }

//     $academicYr = $sessionData['academic_yr'] ?? null;
//     if (!$academicYr) {
//         return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
//     }


//     $results = DB::select("
//         SELECT CONCAT(class.name, ' ', section.name) AS class_section,
//                house.house_name AS house_name,
//                house.color_code AS color_code,
//                COUNT(student.student_id) AS student_counts
//         FROM student
//         JOIN class ON student.class_id = class.class_id
//         JOIN section ON student.section_id = section.section_id
//         JOIN house ON student.house = house.house_id
//         WHERE student.IsDelete = 'N'
//           AND class.name = ?
//           AND student.academic_yr = ?
//         GROUP BY class_section, house_name, house.color_code
//         ORDER BY class_section, house_name
//     ", [$className, $academicYr]);

//     return response()->json($results);
// }

public function getHouseViseStudent(Request $request) {
    $className = $request->input('class_name');
    // $sessionData = session('sessionData');
    // if (!$sessionData) {
    //     return response()->json(['message' => 'Session data not found', 'success' => false], 404);
    // }

    // $academicYr = $sessionData['academic_yr'] ?? null;
    // if (!$academicYr) {
    //     return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
    // }
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $query = "
        SELECT CONCAT(class.name, ' ', section.name) AS class_section,
               house.house_name AS house_name,
               house.color_code AS color_code,
               COUNT(student.student_id) AS student_counts
        FROM student
        JOIN class ON student.class_id = class.class_id
        JOIN section ON student.section_id = section.section_id
        JOIN house ON student.house = house.house_id
        WHERE student.IsDelete = 'N'
          AND student.academic_yr = ?
    ";

    $params = [$academicYr];

    if ($className) {
        $query .= " AND class.name = ?";
        $params[] = $className;
    }

    $query .= "
        GROUP BY class_section, house_name, house.color_code
        ORDER BY class_section, house_name
    ";

    $results = DB::select($query, $params);

    return response()->json($results);
}



public function getAcademicYears(Request $request)
    {
        $user = Auth::user();
        $activeAcademicYear = Setting::where('active', 'Y')->first()->academic_yr;

        $settings = Setting::all();

        if ($user->role_id === 'P') {
            $settings = $settings->filter(function ($setting) use ($activeAcademicYear) {
                return $setting->academic_yr <= $activeAcademicYear;
            });
        }
        $academicYears = $settings->pluck('academic_yr');

        return response()->json([
            'academic_years' => $academicYears,
            'settings' => $settings
        ]);
    }


public function getAuthUser()
{
    $user = auth()->user();
    $academic_yr = $user->academic_yr;

    return response()->json([
        'user' => $user,
        'academic_yr' => $academic_yr,
    ]);
}


// public function updateAcademicYearForAuthUser(Request $request)
// {
//     $user = Auth::user();     
//     if ($user) {
//         session(['academic_yr' => $request->newAcademicYear]);
//         Log::info('New academic year set:', ['user_id' => $user->id, 'academic_yr' => $request->newAcademicYear]);
//     }
// }


public function getBankAccountName()
{
    $bankAccountName = BankAccountName::all();
    return response()->json([
        'bankAccountName' => $bankAccountName,       
    ]);
}

public function pendingCollectedFeeData(): JsonResponse
{
     DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    $subQuery1 = DB::table('view_student_fees_category as s')
        ->leftJoin('fee_concession_details as d', function ($join) {
            $join->on('s.student_id', '=', 'd.student_id')
                 ->on('s.installment', '=', 'd.installment');
        })
        ->select(
            's.student_id',
            's.installment',
            's.installment_fees',
            DB::raw('COALESCE(SUM(d.amount), 0) as concession'),
            DB::raw('0 as paid_amount')
        )
        ->where('s.academic_yr', '2023-2024')
        ->where('s.installment', '<>', 4)
        ->where('s.due_date', '<', DB::raw('CURDATE()'))
        ->whereNotIn('s.student_installment', function ($query) {
            $query->select('a.student_installment')
                  ->from('view_student_fees_payment as a')
                  ->where('a.academic_yr', '2023-2024');
        })
        ->groupBy('s.student_id', 's.installment');

    $subQuery2 = DB::table('view_student_fees_payment as f')
        ->leftJoin('fee_concession_details as c', function ($join) {
            $join->on('f.student_id', '=', 'c.student_id')
                 ->on('f.installment', '=', 'c.installment');
        })
        ->join('view_fee_allotment as b', function ($join) {
            $join->on('f.fee_allotment_id', '=', 'b.fee_allotment_id')
                 ->on('b.installment', '=', 'f.installment');
        })
        ->select(
            'f.student_id as student_id',
            'b.installment as installment',
            'b.installment_fees',
            DB::raw('COALESCE(SUM(c.amount), 0) as concession'),
            DB::raw('SUM(f.fees_paid) as paid_amount')
        )
        ->where('b.installment', '<>', 4)
        ->where('f.academic_yr', '2023-2024')
        ->groupBy('f.installment', 'c.installment')
        ->havingRaw('(b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)');

    $unionQuery = $subQuery1->union($subQuery2);

    $finalQuery = DB::table(DB::raw("({$unionQuery->toSql()}) as z"))
        ->select(
            'z.installment',
            DB::raw('SUM(z.installment_fees - z.concession - z.paid_amount) as pending_fee')
        )
        ->groupBy('z.installment')
        ->mergeBindings($unionQuery) 
        ->get();

    return response()->json($finalQuery);
}


public function pendingCollectedFeeDatalist(Request $request): JsonResponse
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    $subQuery1 = DB::table('view_student_fees_category as s')
        ->leftJoin('fee_concession_details as d', function ($join) {
            $join->on('s.student_id', '=', 'd.student_id')
                 ->on('s.installment', '=', 'd.installment');
        })
        ->select(
            's.student_id',
            's.installment',
            's.installment_fees',
            DB::raw('COALESCE(SUM(d.amount), 0) as concession'),
            DB::raw('0 as paid_amount')
        )
        ->where('s.academic_yr', $academicYr)
        ->where('s.installment', '<>', 4)
        ->where('s.due_date', '<', DB::raw('CURDATE()'))
        ->whereNotIn('s.student_installment', function ($query) use ($academicYr) {
            $query->select('a.student_installment')
                  ->from('view_student_fees_payment as a')
                  ->where('a.academic_yr', $academicYr);
        })
        ->groupBy('s.student_id', 's.installment');

    $subQuery2 = DB::table('view_student_fees_payment as f')
        ->leftJoin('fee_concession_details as c', function ($join) {
            $join->on('f.student_id', '=', 'c.student_id')
                 ->on('f.installment', '=', 'c.installment');
        })
        ->join('view_fee_allotment as b', function ($join) {
            $join->on('f.fee_allotment_id', '=', 'b.fee_allotment_id')
                 ->on('b.installment', '=', 'f.installment');
        })
        ->select(
            'f.student_id as student_id',
            'b.installment as installment',
            'b.installment_fees',
            DB::raw('COALESCE(SUM(c.amount), 0) as concession'),
            DB::raw('SUM(f.fees_paid) as paid_amount')
        )
        ->where('b.installment', '<>', 4)
        ->where('f.academic_yr', $academicYr)
        ->groupBy('f.installment', 'c.installment')
        ->havingRaw('(b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)');

    $unionQuery = $subQuery1->union($subQuery2);

    $finalQuery = DB::table(DB::raw("({$unionQuery->toSql()}) as z"))
        ->select(
            'z.installment',
            DB::raw('SUM(z.installment_fees - z.concession - z.paid_amount) as pending_fee')
        )
        ->groupBy('z.installment')
        ->mergeBindings($unionQuery)
        ->get();

    return response()->json($finalQuery);
}


public function collectedFeeList(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $bankAccountNames = DB::table('bank_account_name')
        ->whereIn('account_name', ['Nursery', 'KG', 'School'])
        ->pluck('account_name')
        ->toArray();

    $query = DB::table('view_fees_payment_record as a')
        ->join('view_fees_payment_detail as d', 'a.fees_payment_id', '=', 'd.fees_payment_id')
        ->join('student as b', 'a.student_id', '=', 'b.student_id')
        ->join('class as c', 'b.class_id', '=', 'c.class_id')
        ->select(DB::raw("'Total' as account"), 'd.installment', DB::raw('SUM(d.amount) as amount'))
        ->where('a.isCancel', 'N')
        ->where('a.academic_yr', $academicYr)
        ->groupBy('d.installment');

    foreach ($bankAccountNames as $class) {
        $query->union(function ($query) use ($class, $academicYr) {
            $query->select(DB::raw("'{$class}' as account"), 'd.installment', DB::raw('SUM(d.amount) as amount'))
                ->from('view_fees_payment_record as a')
                ->join('view_fees_payment_detail as d', 'a.fees_payment_id', '=', 'd.fees_payment_id')
                ->join('student as b', 'a.student_id', '=', 'b.student_id')
                ->join('class as c', 'b.class_id', '=', 'c.class_id')
                ->where('a.isCancel', 'N')
                ->where('a.academic_yr', $academicYr);

            if ($class === 'Nursery') {
                $query->where('c.name', 'Nursery');
            } elseif ($class === 'KG') {
                $query->whereIn('c.name', ['LKG', 'UKG']);
            } elseif ($class === 'School') {
                $query->whereIn('c.name', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']);
            }

            $query->groupBy('d.installment');
        });
    }

    $results = $query->get();

    $formattedResults = [];

    foreach ($results as $result) {
        $account = $result->account;

        if ($account !== 'Total') {
            $formattedResults[$account][] = [
                'installment' => $result->installment,
                'amount' => $result->amount,
            ];
        }
    }

    return response()->json($formattedResults);
}


public function listSections(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $sections = Section::where('academic_yr', $academicYr)->get();
        
        return response()->json($sections);
  }

  public function checkSectionName(Request $request)
  {
      $request->validate([
          'name' => 'required|string|max:30',
      ]);
      $name = $request->input('name');
      $exists = Section::where(DB::raw('LOWER(name)'), strtolower($name))->exists();
      return response()->json(['exists' =>$exists]);
  }

public function updateSection(Request $request, $id)
{
        $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z]+$/'],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'name.regex' => 'The name field must contain only alphabetic characters without spaces.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors(),
        ], 422);
    }

    $section = Section::find($id);
    if (!$section) {
        return response()->json(['message' => 'Section not found', 'success' => false], 404);
    }
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');

    // Update the section
    $section->name = $request->name;
    $section->academic_yr = $academicYr;
    $section->save();

    // Return success response
    return response()->json([
        'status' => 200,
        'message' => 'Section updated successfully',
    ]);
}

public function storeSection(Request $request)
{
    $validator = \Validator::make($request->all(), [
        'name' => [
            'required', 
            'string', 
            'max:255', 
            'regex:/^[a-zA-Z]+$/', 
        ],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'name.regex' => 'The name field must contain only alphabetic characters without spaces.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors(),
        ], 422);
    }

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');

    $section = new Section();
    $section->name = $request->name;
    $section->academic_yr = $academicYr;
    $section->save();

    return response()->json([
        'status' => 201,
        'message' => 'Section created successfully',
        'data' => $section,
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

public function deleteSection($id)
{
    $section = Section::find($id);
    
    if (!$section) {
        return response()->json(['message' => 'Section not found', 'success' => false], 404);
    }    
    if ($section->classes()->exists()) {
        return response()->json(['message' => 'This section is in use and cannot be deleted.', 'success' => false], 400);
    }

    $section->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Section deleted successfully',
        'success' => true
    ]);
}


 // Methods for the classes model

 public function checkClassName(Request $request)
 {
     $request->validate([
         'name' => 'required|string|max:30',
     ]); 
     $name = $request->input('name');     
     $exists = Classes::where(DB::raw('LOWER(name)'), strtolower($name))->exists(); 
     return response()->json(['exists' => $exists]);
 }
 

public function getClass(Request $request)
{   
    $payload = getTokenPayload($request);    
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    $classes = Classes::with('getDepartment')
        ->withCount('students')
        ->where('academic_yr', $academicYr)
        ->get();
    return response()->json($classes);
}

public function storeClass(Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    $validator = \Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:30'],
        'department_id' => ['required', 'integer'],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'department_id.required' => 'The department ID is required.',
        'department_id.integer' => 'The department ID must be an integer.',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors(),
        ], 422);
    }

    $class = new Classes();
    $class->name = $request->name;
    $class->department_id = $request->department_id;
    $class->academic_yr = $academicYr;
    $class->save();
    return response()->json([
        'status' => 201,
        'message' => 'Class created successfully',
        'data' => $class,
    ]);
}

public function updateClass(Request $request, $id)
{
    $validator = \Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:30'],
        'department_id' => ['required', 'integer'],
    ], [
        'name.required' => 'The name field is required.',
        'name.string' => 'The name field must be a string.',
        'name.max' => 'The name field must not exceed 255 characters.',
        'department_id.required' => 'The department ID is required.',
        'department_id.integer' => 'The department ID must be an integer.',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors(),
        ], 422);
    }
    $class = Classes::find($id);
    if (!$class) {
        return response()->json(['message' => 'Class not found', 'success' => false], 404);
    }
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    $class->name = $request->name;
    $class->department_id = $request->department_id;
    $class->academic_yr = $academicYr;
    $class->save();
    return response()->json([
        'status' => 200,
        'message' => 'Class updated successfully',
        'data' => $class,
    ]);
}

public function showClass($id)
{
    $class = Classes::find($id);
    if (!$class) {
        return response()->json(['message' => 'Class not found', 'success' => false], 404);
    }

    // Return the class data
    return response()->json([
        'status' => 200,
        'message' => 'Class retrieved successfully',
        'data' => $class,
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
public function checkDivisionName(Request $request)
{     
      $messages = [
        'name.required' => 'The division name is required.',
        'name.string' => 'The division name must be a string.',
        'name.max' => 'The division name may not be greater than 30 characters.',
        'class_id.required' => 'The class ID is required.',
        'class_id.integer' => 'The class ID must be an integer.',
        'class_id.exists' => 'The selected class ID is invalid.',
    ];
   
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:30',
        'class_id' => 'required|integer|exists:class,class_id',
    ], $messages);

   
    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }
    $validatedData = $validator->validated();
    $name = $validatedData['name'];
    $classId = $validatedData['class_id'];

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    $exists = Division::where(DB::raw('LOWER(name)'), strtolower($name))
        ->where('class_id', $classId)
        ->where('academic_yr', $academicYr)
        ->exists();
    return response()->json(['exists' => $exists]);
}


public function getDivision(Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $divisions = Division::with('getClass.getDepartment')
                         ->where('academic_yr', $academicYr)
                         ->get();    
    return response()->json($divisions);
}


public function  getClassforDivision(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
   $classList = Classes::where('academic_yr',$academicYr)->get();
   return response()->json($classList);
}


public function storeDivision(Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
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

public function updateDivision(Request $request, $id)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
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


public function showDivision($id)
{
       $division = Division::with('getClass')->find($id);

    if (is_null($division)) {
        return response()->json(['message' => 'Division not found'], 404);
    }

    return response()->json($division);
}

public function destroyDivision($id)
{
    $division = Division::find($id);

    if (is_null($division)) {
        return response()->json(['message' => 'Division not found'], 404);
    }

    $division->delete();
    return response()->json([
        'status' => 200,
        'message' => 'Division deleted successfully',
        'success' => true
                          ]
                            );
}


public function getStaffList(Request $request) {
    $stafflist = Teacher::where('designation', '!=', 'Caretaker.')
        ->get()
        ->map(function ($staff) {
            if ($staff->teacher_image_name) {
                $staff->teacher_image_name = Storage::url('teacher_images/' . $staff->teacher_image_name);
            } else {
                $staff->teacher_image_name = null; 
            }
            return $staff;
        });
    return response()->json($stafflist);
}

public function editStaff($id)
{
    try {
        // Find the teacher by ID
        $teacher = Teacher::findOrFail($id);

        // Check if the teacher has an image and generate the URL if it exists
        if ($teacher->teacher_image_name) {
            $teacher->teacher_image_url = Storage::url('teacher_images/' . $teacher->teacher_image_name);
        } else {
            $teacher->teacher_image_url = null;
        }

        // Find the associated user record
        $user = User::where('reg_id', $id)->first();

        return response()->json([
            'teacher' => $teacher,
            'user' => $user,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while fetching the teacher details',
            'error' => $e->getMessage()
        ], 500);
    }
}


// public function editStaff($id)
// {
//     try {
//         $teacher = Teacher::findOrFail($id);

//         return response()->json([
//             'message' => 'Teacher retrieved successfully!',
//             'teacher' => $teacher,
//         ], 200);
//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => 'An error occurred while retrieving the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

// public function storeStaff(Request $request)
// {
//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//             'teacher_image_name.string' => 'The file must be an image.',
//             'role.required' => 'The role field is required.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             'email' => 'required|string|email|max:255|unique:teacher,email',
//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
//             'teacher_image_name' => 'nullable|string', // Base64 string
//             'role' => 'required|string|max:255',
//         ], $messages);

//         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
//             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
//         }

//         // Handle base64 image
//         if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
//             $imageData = $request->input('teacher_image_name');
//             if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
//                 $imageData = substr($imageData, strpos($imageData, ',') + 1);
//                 $type = strtolower($type[1]); // jpg, png, gif
//                 if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
//                     throw new \Exception('Invalid image type');
//                 }
//                 $imageData = base64_decode($imageData);
//                 if ($imageData === false) {
//                     throw new \Exception('Base64 decode failed');
//                 }
//                 $filename = 'teacher_' . time() . '.' . $type;
//                 $filePath = storage_path('app/public/teacher_images/'.$filename);

//                 // Ensure directory exists
//                 $directory = dirname($filePath);
//                 if (!is_dir($directory)) {
//                     mkdir($directory, 0755, true);
//                 }

//                 // Save image to file
//                 if (file_put_contents($filePath, $imageData) === false) {
//                     throw new \Exception('Failed to save image file');
//                 }

//                 $validatedData['teacher_image_name'] = $filename;
//             } else {
//                 throw new \Exception('Invalid image data');
//             }
//         }

//         // Create Teacher record
//         $teacher = new Teacher();
//         $teacher->fill($validatedData);
//         $teacher->IsDelete = 'N';

//         if (!$teacher->save()) {
//             return response()->json([
//                 'message' => 'Failed to create teacher',
//             ], 500);
//         }

//         // Create User record
//         $user = User::create([
//             'email' => $validatedData['email'],
//             'name' => $validatedData['name'],
//             'password' => Hash::make('arnolds'),
//             'reg_id' => $teacher->teacher_id,
//             'role_id' => $validatedData['role'],
//             'IsDelete' => 'N',
//         ]);

//         if (!$user) {
//             // Rollback by deleting the teacher record if user creation fails
//             $teacher->delete();
//             return response()->json([
//                 'message' => 'Failed to create user',
//             ], 500);
//         }

//         return response()->json([
//             'message' => 'Teacher and user created successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 201);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             // Rollback by deleting the teacher record if an unexpected error occurs
//             $teacher->delete();
//         }
//         return response()->json([
//             'message' => 'An error occurred while creating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

// public function storeStaff(Request $request)
// {
//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//             'teacher_image_name.string' => 'The file must be an image.',
//             'role.required' => 'The role field is required.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             // 'email' => 'required|string|email|max:255|unique:teacher,email',
//             'email' => 'required|string|email|max:50',

//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
//             'teacher_image_name' => 'nullable|string', // Base64 string
//             'role' => 'required|string|max:255',
//         ], $messages);

//         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
//             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
//         }

//         // Handle base64 image
//         if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
//             $imageData = $request->input('teacher_image_name');
//             if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
//                 $imageData = substr($imageData, strpos($imageData, ',') + 1);
//                 $type = strtolower($type[1]); // jpg, png, gif
//                 if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
//                     throw new \Exception('Invalid image type');
//                 }
//                 $imageData = base64_decode($imageData);
//                 if ($imageData === false) {
//                     throw new \Exception('Base64 decode failed');
//                 }
//                 $filename = 'teacher_' . time() . '.' . $type;
//                 $filePath = storage_path('app/public/teacher_images/'.$filename);

//                 // Ensure directory exists
//                 $directory = dirname($filePath);
//                 if (!is_dir($directory)) {
//                     mkdir($directory, 0755, true);
//                 }

//                 // Save image to file
//                 if (file_put_contents($filePath, $imageData) === false) {
//                     throw new \Exception('Failed to save image file');
//                 }

//                 $validatedData['teacher_image_name'] = $filename;
//             } else {
//                 throw new \Exception('Invalid image data');
//             }
//         }

//         // Generate the user email
//         $name = trim($validatedData['name']);
//         $userEmail = strtolower(str_replace(' ', '.', $name)) . '@arnolds';

//         // Create Teacher record
//         $teacher = new Teacher();
//         $teacher->fill($validatedData);
//         $teacher->IsDelete = 'N';

//         if (!$teacher->save()) {
//             return response()->json([
//                 'message' => 'Failed to create teacher',
//             ], 500);
//         }

//         // Create User record
//         $user = User::create([
//             'email' => $userEmail,
//             'name' => $validatedData['name'],
//             'password' => Hash::make('arnolds'),
//             'reg_id' => $teacher->teacher_id,
//             'role_id' => $validatedData['role'],
//             'IsDelete' => 'N',
//         ]);

//         if (!$user) {
//             // Rollback by deleting the teacher record if user creation fails
//             $teacher->delete();
//             return response()->json([
//                 'message' => 'Failed to create user',
//             ], 500);
//         }

//         // Send welcome email
//         Mail::to($validatedData['email'])->send(new WelcomeEmail($userEmail, 'arnolds'));

//         return response()->json([
//             'message' => 'Teacher and user created successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 201);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             // Rollback by deleting the teacher record if an unexpected error occurs
//             $teacher->delete();
//         }
//         return response()->json([
//             'message' => 'An error occurred while creating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

// public function storeStaff(Request $request)
// {
//     DB::beginTransaction(); // Start the transaction

//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//             'teacher_image_name.string' => 'The file must be an image.',
//             'role.required' => 'The role field is required.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             'email' => 'required|string|email|max:50',
//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
//             'teacher_image_name' => 'nullable|string', // Base64 string
//             'role' => 'required|string|max:255',
//         ], $messages);

//         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
//             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
//         }

//         // Handle base64 image
//         if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
//             $imageData = $request->input('teacher_image_name');
//             if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
//                 $imageData = substr($imageData, strpos($imageData, ',') + 1);
//                 $type = strtolower($type[1]); // jpg, png, gif
//                 if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
//                     throw new \Exception('Invalid image type');
//                 }
//                 $imageData = base64_decode($imageData);
//                 if ($imageData === false) {
//                     throw new \Exception('Base64 decode failed');
//                 }
//                 $filename = 'teacher_' . time() . '.' . $type;
//                 $filePath = storage_path('app/public/teacher_images/'.$filename);

//                 // Ensure directory exists
//                 $directory = dirname($filePath);
//                 if (!is_dir($directory)) {
//                     mkdir($directory, 0755, true);
//                 }

//                 // Save image to file
//                 if (file_put_contents($filePath, $imageData) === false) {
//                     throw new \Exception('Failed to save image file');
//                 }

//                 $validatedData['teacher_image_name'] = $filename;
//             } else {
//                 throw new \Exception('Invalid image data');
//             }
//         }

//         // Generate the user email
//         $name = trim($validatedData['name']);
//         $userEmail = strtolower(str_replace(' ', '.', $name)) . '@arnolds';

//         // Create Teacher record
//         $teacher = new Teacher();
//         $teacher->fill($validatedData);
//         $teacher->IsDelete = 'N';

//         if (!$teacher->save()) {
//             DB::rollBack(); // Rollback the transaction
//             return response()->json([
//                 'message' => 'Failed to create teacher',
//             ], 500);
//         }

//         // Create User record
//         $user = User::create([
//             'email' => $userEmail,
//             'name' => $validatedData['name'],
//             'password' => Hash::make('arnolds'),
//             'reg_id' => $teacher->teacher_id,
//             'role_id' => $validatedData['role'],
//             'IsDelete' => 'N',
//         ]);

//         if (!$user) {
//             // Rollback by deleting the teacher record if user creation fails
//             $teacher->delete();
//             DB::rollBack(); // Rollback the transaction
//             return response()->json([
//                 'message' => 'Failed to create user',
//             ], 500);
//         }

//         // Send welcome email
//         Mail::to($validatedData['email'])->send(new WelcomeEmail($userEmail, 'arnolds'));

//         DB::commit(); // Commit the transaction

//         return response()->json([
//             'message' => 'Teacher and user created successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 201);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         DB::rollBack(); // Rollback the transaction on validation error
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             // Rollback by deleting the teacher record if an unexpected error occurs
//             $teacher->delete();
//         }
//         DB::rollBack(); // Rollback the transaction
//         return response()->json([
//             'message' => 'An error occurred while creating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }



public function storeStaff(Request $request)
{
    DB::beginTransaction(); // Start the transaction

    try {
        $messages = [
            'name.required' => 'The name field is mandatory.',
            'birthday.required' => 'The birthday field is required.',
            'date_of_joining.required' => 'The date of joining is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'phone.required' => 'The phone number is required.',
            'phone.max' => 'The phone number cannot exceed 15 characters.',
            'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
            'teacher_image_name.string' => 'The file must be an image.',
            'role.required' => 'The role field is required.',
        ];

        $validatedData = $request->validate([
            'employee_id' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'date_of_joining' => 'required|date',
            'sex' => 'required|string|max:10',
            'religion' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|string|email|max:50',
            'designation' => 'nullable|string|max:255',
            'academic_qual' => 'nullable|array',
            'academic_qual.*' => 'nullable|string|max:255',
            'professional_qual' => 'nullable|string|max:255',
            'special_sub' => 'nullable|string|max:255',
            'trained' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
            'teacher_image_name' => 'nullable|string', // Base64 string
            'role' => 'required|string|max:255',
        ], $messages);

        if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
            $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
        }

        // Handle base64 image
        if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
            $imageData = $request->input('teacher_image_name');
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    throw new \Exception('Invalid image type');
                }
                $imageData = base64_decode($imageData);
                if ($imageData === false) {
                    throw new \Exception('Base64 decode failed');
                }
                $filename = 'teacher_' . time() . '.' . $type;
                $filePath = storage_path('app/public/teacher_images/'.$filename);

                // Ensure directory exists
                $directory = dirname($filePath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save image to file
                if (file_put_contents($filePath, $imageData) === false) {
                    throw new \Exception('Failed to save image file');
                }

                $validatedData['teacher_image_name'] = $filename;
            } else {
                throw new \Exception('Invalid image data');
            }
        }

        // Generate the user email
        $name = trim($validatedData['name']);
        $userEmail = strtolower(str_replace(' ', '.', $name)) . '@arnolds';

        // Create Teacher record
        $teacher = new Teacher();
        $teacher->fill($validatedData);
        $teacher->IsDelete = 'N';

        if (!$teacher->save()) {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to create teacher',
            ], 500);
        }

        // Create User record
        $user = User::create([
            'email' => $userEmail,
            'name' => $validatedData['name'],
            'password' => Hash::make('arnolds'),
            'reg_id' => $teacher->teacher_id,
            'role_id' => $validatedData['role'],
            'IsDelete' => 'N',
        ]);

        if (!$user) {
            // Rollback by deleting the teacher record if user creation fails
            $teacher->delete();
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to create user',
            ], 500);
        }

        // Send welcome email
        Mail::to($validatedData['email'])->send(new WelcomeEmail($userEmail, 'arnolds'));

        // Call external API
        $response = Http::post('http://aceventura.in/demo/evolvuUserService/create_staff_userid', [
            'user_id' => $userEmail,
            'role' => $validatedData['role'],
            'short_name' => 'SACS',
        ]);

        // Log the API response
        Log::info('External API response:', [
            'url' => 'http://aceventura.in/demo/evolvuUserService/create_staff_userid',
            'status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        if ($response->successful()) {
            DB::commit(); // Commit the transaction
            return response()->json([
                'message' => 'Teacher and user created successfully!',
                'teacher' => $teacher,
                'user' => $user,
                'external_api_response' => $response->json(),
            ], 201);
        } else {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Teacher and user created, but external API call failed',
                'external_api_error' => $response->body(),
            ], 500);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack(); // Rollback the transaction on validation error
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        // Handle unexpected errors
        if (isset($teacher) && $teacher->exists) {
            // Rollback by deleting the teacher record if an unexpected error occurs
            $teacher->delete();
        }
        DB::rollBack(); // Rollback the transaction
        return response()->json([
            'message' => 'An error occurred while creating the teacher',
            'error' => $e->getMessage()
        ], 500);
    }
}

// public function updateStaff(Request $request, $id)
// {
//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'father_spouse_name' => 'nullable|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             'email' => 'required|string|email|max:255|unique:teacher,email,' . $id . ',teacher_id',
//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no,' . $id . ',teacher_id',
//             'teacher_image_name' => 'nullable|string|max:255',
//             'class_id' => 'nullable|integer',
//             'section_id' => 'nullable|integer',
//             'isDelete' => 'nullable|string|in:Y,N',
//         ], $messages);

//         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
//             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
//         }

//         $teacher = Teacher::findOrFail($id);
//         $teacher->fill($validatedData);

//         if (!$teacher->save()) {
//             return response()->json([
//                 'message' => 'Failed to update teacher',
//             ], 500);
//         }

//         $user = User::where('reg_id', $id)->first();
//         if ($user) {
//             // $existingUserWithEmail = User::where('email', $validatedData['email'])
//             //     ->where('id', '!=', $user->id)
//             //     ->first();

//             // if ($existingUserWithEmail) {
//             //     return response()->json([
//             //         'message' => 'The email address is already taken.',
//             //     ], 400);
//             // }

//             $user->name = $validatedData['name'];
//             // $user->email = $validatedData['email'];

//             if (!$user->save()) {
//                 // Rollback by reverting the teacher record if user update fails
//                 $teacher->delete();
//                 return response()->json([
//                     'message' => 'Failed to update user',
//                 ], 500);
//             }
//         }

//         return response()->json([
//             'message' => 'Teacher updated successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 200);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             $teacher->delete();
//         }
//         return response()->json([
//             'message' => 'An error occurred while updating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

// public function updateStaff(Request $request, $id)
// {
//     DB::beginTransaction(); // Start the transaction

//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//             'teacher_image_name.string' => 'The file must be an image.',
//             'role.required' => 'The role field is required.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'father_spouse_name' => 'nullable|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             'email' => 'required|string|email|max:255|unique:teacher,email,' . $id . ',teacher_id',
//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no,' . $id . ',teacher_id',
//             'teacher_image_name' => 'nullable|string', // Base64 string
//             'class_id' => 'nullable|integer',
//             'section_id' => 'nullable|integer',
//             'isDelete' => 'nullable|string|in:Y,N',
//         ], $messages);

//         // Handle base64 image
//         if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
//             $imageData = $request->input('teacher_image_name');
//             if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
//                 $imageData = substr($imageData, strpos($imageData, ',') + 1);
//                 $type = strtolower($type[1]); // jpg, png, gif
//                 if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
//                     throw new \Exception('Invalid image type');
//                 }
//                 $imageData = base64_decode($imageData);
//                 if ($imageData === false) {
//                     throw new \Exception('Base64 decode failed');
//                 }
//                 $filename = 'teacher_' . time() . '.' . $type;
//                 $filePath = storage_path('app/public/teacher_images/' . $filename);

//                 // Ensure directory exists
//                 $directory = dirname($filePath);
//                 if (!is_dir($directory)) {
//                     mkdir($directory, 0755, true);
//                 }

//                 // Save image to file
//                 if (file_put_contents($filePath, $imageData) === false) {
//                     throw new \Exception('Failed to save image file');
//                 }

//                 $validatedData['teacher_image_name'] = $filename;
//             } else {
//                 throw new \Exception('Invalid image data');
//             }
//         }

//         $teacher = Teacher::findOrFail($id);
//         $teacher->fill($validatedData);

//         if (!$teacher->save()) {
//             DB::rollBack(); // Rollback the transaction
//             return response()->json([
//                 'message' => 'Failed to update teacher',
//             ], 500);
//         }

//         $user = User::where('reg_id', $id)->first();
//         if ($user) {
//             $user->name = $validatedData['name'];
//             $user->email = $validatedData['email'];

//             if (!$user->save()) {
//                 // Rollback by reverting the teacher record if user update fails
//                 $teacher->delete();
//                 DB::rollBack(); // Rollback the transaction
//                 return response()->json([
//                     'message' => 'Failed to update user',
//                 ], 500);
//             }
//         }

//         DB::commit(); // Commit the transaction
//         return response()->json([
//             'message' => 'Teacher updated successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 200);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         DB::rollBack(); // Rollback the transaction on validation error
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             $teacher->delete();
//         }
//         DB::rollBack(); // Rollback the transaction
//         return response()->json([
//             'message' => 'An error occurred while updating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }


public function updateStaff(Request $request, $id)
{
    DB::beginTransaction(); // Start the transaction

    try {
        $messages = [
            'name.required' => 'The name field is mandatory.',
            'birthday.required' => 'The birthday field is required.',
            'date_of_joining.required' => 'The date of joining is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'phone.required' => 'The phone number is required.',
            'phone.max' => 'The phone number cannot exceed 15 characters.',
            'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
            'teacher_image_name.string' => 'The file must be an image.',
            'role.required' => 'The role field is required.',
        ];

        $validatedData = $request->validate([
            'employee_id' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'date_of_joining' => 'required|date',
            'sex' => 'required|string|max:10',
            'religion' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            // 'email' => 'required|string|email|max:255|unique:teacher,email,' . $id . ',teacher_id',
            'email' => 'required|string|email',
            'designation' => 'nullable|string|max:255',
            'academic_qual' => 'nullable|array',
            'academic_qual.*' => 'nullable|string|max:255',
            'professional_qual' => 'nullable|string|max:255',
            'special_sub' => 'nullable|string|max:255',
            'trained' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'aadhar_card_no' => 'nullable|string',
            'teacher_image_name' => 'nullable|string', // Base64 string
            // 'role' => 'required|string|max:255',
        ], $messages);

        if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
            $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
        }

        // Handle base64 image
        if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
            $imageData = $request->input('teacher_image_name');
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    throw new \Exception('Invalid image type');
                }
                $imageData = base64_decode($imageData);
                if ($imageData === false) {
                    throw new \Exception('Base64 decode failed');
                }
                $filename = 'teacher_' . time() . '.' . $type;
                $filePath = storage_path('app/public/teacher_images/'.$filename);

                // Ensure directory exists
                $directory = dirname($filePath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save image to file
                if (file_put_contents($filePath, $imageData) === false) {
                    throw new \Exception('Failed to save image file');
                }

                $validatedData['teacher_image_name'] = $filename;
            } else {
                throw new \Exception('Invalid image data');
            }
        }

        // Find the teacher record by ID
        $teacher = Teacher::findOrFail($id);
        $teacher->fill($validatedData);

        if (!$teacher->save()) {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to update teacher',
            ], 500);
        }

        // Update user associated with the teacher
        $user = User::where('reg_id', $teacher->teacher_id)->first();
        if ($user) {
            $user->name = $validatedData['name'];
            $user->email = strtolower(str_replace(' ', '.', trim($validatedData['name']))) . '@arnolds';

            if (!$user->save()) {
                DB::rollBack(); // Rollback the transaction
                return response()->json([
                    'message' => 'Failed to update user',
                ], 500);
            }
        }

        DB::commit(); // Commit the transaction
        return response()->json([
            'message' => 'Teacher updated successfully!',
            'teacher' => $teacher,
            'user' => $user,
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack(); // Rollback the transaction on validation error
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        // Handle unexpected errors
        if (isset($teacher) && $teacher->exists) {
            // Keep teacher record but return an error
        }
        DB::rollBack(); // Rollback the transaction
        return response()->json([
            'message' => 'An error occurred while updating the teacher',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function deleteStaff($id)
{
    try {
        $teacher = Teacher::findOrFail($id);
        $teacher->isDelete = 'Y';

        if ($teacher->save()) {
            $user = User::where('reg_id', $id)->first();
            if ($user) {
                $user->IsDelete = 'Y';
                $user->save();
            }

            return response()->json([
                'message' => 'Teacher marked as deleted successfully!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to mark teacher as deleted',
            ], 500);
        }
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while marking the teacher as deleted',
            'error' => $e->getMessage()
        ], 500);
    }
}


// Methods for  Subject Master  API 
public function getSubjects(Request $request)
{
    $subjects = SubjectMaster::all();
    return response()->json($subjects);
}

public function checkSubjectName(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'name' => 'required|string|max:30',
        'subject_type' => 'required|string|max:30',
    ]);

    $name = $validatedData['name'];
    $subjectType = $validatedData['subject_type'];

    // Check if the combination of name and subject_type exists
    $exists = SubjectMaster::whereRaw('LOWER(name) = ? AND LOWER(subject_type) = ?', [strtolower($name), strtolower($subjectType)])->exists();

    return response()->json(['exists' => $exists]);
}


public function storeSubject(Request $request)
{
    $messages = [
        'name.required' => 'The name field is required.',
        // 'name.unique' => 'The name has already been taken.',
        'subject_type.required' => 'The subject type field is required.',
        'subject_type.unique' => 'The subject type has already been taken.',
    ];

    try {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                // Rule::unique('subject_master', 'name')
            ],
            'subject_type' => [
                'required',
                'string',
                'max:255'
            ],
        ], $messages);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => 422,
            'errors' => $e->errors(),
        ], 422);
    }

    $subject = new SubjectMaster();
    $subject->name = $validatedData['name'];
    $subject->subject_type = $validatedData['subject_type'];
    $subject->save();

    return response()->json([
        'status' => 201,
        'message' => 'Subject created successfully',
    ], 201);
}

public function updateSubject(Request $request, $id)
    {
        $messages = [
            'name.required' => 'The name field is required.',
            // 'name.unique' => 'The name has already been taken.',
            'subject_type.required' => 'The subject type field is required.',
            // 'subject_type.unique' => 'The subject type has already been taken.',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    // Rule::unique('subject_master', 'name')->ignore($id, 'sm_id')
                ],
                'subject_type' => [
                    'required',
                    'string',
                    'max:255'
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $subject = SubjectMaster::find($id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ], 404);
        }

        $subject->name = $validatedData['name'];
        $subject->subject_type = $validatedData['subject_type'];
        $subject->save();

        return response()->json([
            'status' => 200,
            'message' => 'Subject updated successfully',
        ], 200);
    }



public function editSubject($id)
{
    $subject = SubjectMaster::find($id);

    if (!$subject) {
        return response()->json([
            'status' => 404,
            'message' => 'Subject not found',
        ]);
    }

    return response()->json($subject);
}

public function deleteSubject($id)
{
    $subject = SubjectMaster::find($id);

    if (!$subject) {
        return response()->json([
            'status' => 404,
            'message' => 'Subject not found',
        ]);
    }
    $subjectAllotmentExists = SubjectAllotment::where('sm_id', $id)->exists();
    if ($subjectAllotmentExists) {
        return response()->json([
            'status' => 400,
            'message' => 'Subject cannot be deleted because it is associated with other records.',
        ]);
    }
    $subject->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Subject deleted successfully',
        'success' => true
    ]);
}


public function getStudentListBaseonClass(Request $request){

    $Studentz = Student::count();

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 

     $Student = Student::where('academic_yr',$academicYr)->get();

     return response()->json(
        [
            'Studentz' =>$Studentz,
            'Student' =>$Student,
        ]
     );
}

//get the sections list with the student count 
public function getallSectionsWithStudentCount(Request $request)
{
    $payload = getTokenPayload($request);
    $academicYr = $payload->get('academic_year');
    $divisions = Division::with('getClass')
        ->withCount(['students' => function ($query) use ($academicYr) {
            $query->where('academic_yr', $academicYr);
        }])
        ->where('academic_yr', $academicYr)
        ->get();
    return response()->json($divisions);
}


 // get the student list by the section id 
public function getStudentListBySection(Request $request)
{
    $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year');    
    $sectionId = $request->query('section_id');    
    $query = Student::with(['parents','userMaster','getClass', 'getDivision',])->where('academic_yr', $academicYr)
        ->where('IsDelete','N');    
    if ($sectionId) {
        $query->where('section_id', $sectionId);
    }

    $students = $query->orderBy('roll_no')->get();    
    // $studentCount = $students->count();    
    
    return response()->json([
        // 'count' => $studentCount,
        'students' => $students,
      
    ]);
}

//  get the student list by there id  with the parent details 
public function getStudentById($studentId)
{
    $student = Student::with(['parents','userMaster', 'getClass', 'getDivision'])->find($studentId);
    
    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }    
 
    return response()->json(
        ['students' => [$student]] 
    );
}

public function getStudentByGRN($reg_no)
{
    $student = Student::with(['parents.user', 'getClass', 'getDivision'])
        ->where('reg_no', $reg_no)
        ->first();

    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }     
    return response()->json(['student' => [$student]]);
}

// public function deleteStudent($studentId)
// {
//     // Find the student by ID
//     $student = Student::find($studentId);
    
//     if (!$student) {
//         return response()->json(['error' => 'Student not found'], 404);
//     }

//     // Update the student's isDelete and isModify status to 'Y'
//     $student->isDelete = 'Y';
//     $student->isModify = 'Y';
//     $student->save();

//     // Hard delete the student from the user_master table
//     $userMaster = UserMaster::where('reg_id', $studentId)->first();
//     if ($userMaster) {
//         $userMaster->delete();
//     }

//     // Check if the student has siblings
//     $siblingsCount = Student::where('parent_id', $student->parent_id)
//         ->where('student_id', '!=', $studentId)
//         ->where('isDelete', 'N')
//         ->count();

//     // If no siblings are present, mark the parent as deleted in the parent table
//     if ($siblingsCount == 0) {
//         $parent = Parents::find($student->parent_id);
//         if ($parent) {
//             $parent->isDelete = 'Y';
//             $parent->save();

//             // Hard delete parent information from the user_master table
//             $userMasterParent = UserMaster::where('reg_id', $student->parent_id)->first();
//             if ($userMasterParent) {
//                 $userMasterParent->delete();
//             }

//             // Hard delete parent information from the contact_details table
//             ContactDetails::where('id', $student->parent_id)->delete();
//         }
//     }

//     // After deletion, check if the deleted information exists in the deleted_contact_details table
//     $deletedContact = DeletedContactDetails::where('student_id', $studentId)->first();
//     if (!$deletedContact) {
//         // Insert deleted contact details into the deleted_contact_details table
//         DeletedContactDetails::create([
//             'student_id' => $studentId,
//             'parent_id' => $student->parent_id,
//             'contact_info' => $student->contact_details // Assuming the contact info is available in the student model
//         ]);
//     }

//     return response()->json(['message' => 'Student deleted successfully']);
// }

public function deleteStudent( Request $request , $studentId)
{
    // Find the student by ID
    $student = Student::find($studentId);    
    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }

    // Update the student's isDelete and isModify status to 'Y'
    $payload = getTokenPayload($request);    
    $authUser = $payload->get('reg_id'); 
    $student->isDelete = 'Y';
    $student->isModify = 'Y';
    $student->deleted_by = $authUser;
    $student->deleted_date = Carbon::now();
    $student->save();
    
    $academicYr = $payload->get('academic_year'); 
    // Hard delete the student from the user_master table
    $userMaster = UserMaster::where('role_id','S')
                            ->where('reg_id', $studentId)->first();
                            if ($userMaster) {
                                $userMaster->delete();
                            }

    // Check if the student has siblings
    $siblingsCount = Student::where('academic_yr',$academicYr)
                                ->where('parent_id', $student->parent_id)
                                ->where('student_id', '!=', $studentId)
                                ->where('isDelete', 'N')
                                ->count();

    // If no siblings are present, mark the parent as deleted in the parent table
    if ($siblingsCount == 0) {
        $parent = Parents::find($student->parent_id);
        if ($parent) {
            $parent->isDelete = 'Y';
            $parent->save();

            // Soft Delete  delete parent information from the user_master table
            $userMasterParent = UserMaster::where('role_id','P')
                                           ->where('reg_id', $student->parent_id)->first();
            if ($userMasterParent) {
                $userMasterParent->IsDelete='Y';
                $userMasterParent->save();
            }

            // Hard delete parent information from the contact_details table
            ContactDetails::where('id', $student->parent_id)->delete();
        }
    }
    $parent1 = Parents::find($student->parent_id);

    // After deletion, check if the deleted information exists in the deleted_contact_details table
    $deletedContact = ContactDetails::where('id', $parent1)->first();
    if (!$deletedContact) {
        // Insert deleted contact details into the deleted_contact_details table
        DeletedContactDetails::create([
            'student_id' => $studentId,
            'parent_id' => $student->parent_id,
            'phone_no' => $student->parents->m_mobile, 
            'email_id' => $parent1->f_email, 
            'm_emailid' => $parent1->m_emailid 
        ]);
    }

    return response()->json(['message' => 'Student deleted successfully']);
    //while deleting  please cll the api for the evolvu database. while sibling is not present then  call the api to delete the paret 
}




public function toggleActiveStudent($studentId)
{
    $student = Student::find($studentId);     
    
    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }
    
    // Toggle isActive value
    if ($student->isActive == 'Y') {
        $student->isActive = 'N'; 
        $message = 'Student deactivated successfully';
    } else {
        $student->isActive = 'Y'; 
        $message = 'Student activated successfully';
    }
    $student->save();      

    return response()->json(['message' => $message]);
}


     public function resetPasssword($user_id){  
            
        $user = UserMaster::find($user_id);
        if(!$userID){
            return response()->json("User ID not found");
        }
        $password = "arnolds";
        $user->password=$password;
        $user->save();
        
        return response()->json(
                      [
                        'Status' => 200 ,
                         'Message' => "Password is reset to arnolds . "
                      ]
                      );
     }
   


public function updateStudentAndParent(Request $request, $studentId)
{
    try {
        // Log the start of the request
        Log::info("Starting updateStudentAndParent for student ID: {$studentId}");

        // Validate the incoming request for all fields
        $validatedData = $request->validate([
            // Student model fields
            'first_name' => 'nullable|string|max:100',
            'mid_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'house' => 'nullable|string|max:100',
            'student_name' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'admission_date' => 'nullable|date',
            'stud_id_no' => 'nullable|string|max:25',
            'stu_aadhaar_no' => 'nullable|string|max:14',
            'gender' => 'nullable|string',
            'mother_tongue' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:50',
            'admission_class' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'roll_no' => 'nullable|max:11',
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'religion' => 'nullable|string|max:255',
            'caste' => 'nullable|string|max:100',
            'subcaste' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:13',
            'emergency_name' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:11',
            'emergency_add' => 'nullable|string|max:200',
            'height' => 'nullable|numeric|max:4.1',
            'weight' => 'nullable|numeric|max:4.1',
            'allergies' => 'nullable|string|max:200',
            'nationality' => 'nullable|string|max:100',
            'pincode' => 'nullable|max:11',
            'image_name' => 'nullable|string',
            'has_specs' => 'nullable|string|max:1',
        
            // Parent model fields
            'father_name' => 'nullable|string|max:100',
            'father_occupation' => 'nullable|string|max:100',
            'f_office_add' => 'nullable|string|max:200',
            'f_office_tel' => 'nullable|string|max:11',
            'f_mobile' => 'nullable|string|max:10',
            'f_email' => 'nullable|string|max:50',
            'f_dob' => 'nullable|date',
            'parent_adhar_no' => 'nullable|string|max:14',
            'mother_name' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'm_office_add' => 'nullable|string|max:200',
            'm_office_tel' => 'nullable|string|max:11',
            'm_mobile' => 'nullable|string|max:10',
            'm_dob' => 'nullable|date',
            'm_emailid' => 'nullable|string|max:50',
            'm_adhar_no' => 'nullable|string|max:14',
        
            // Preferences for SMS and email as username
            'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
            'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
        ]);

        Log::info("Validation passed for student ID: {$studentId}");
        Log::info("Validation passed for student ID: {$request->SetEmailIDAsUsername}");

        // Convert relevant fields to uppercase
        $fieldsToUpper = [
            'first_name', 'mid_name', 'last_name', 'house', 'emergency_name', 
            'emergency_contact', 'nationality', 'city', 'state', 'birth_place', 
            'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste'
        ];

        foreach ($fieldsToUpper as $field) {
            if (isset($validatedData[$field])) {
                $validatedData[$field] = strtoupper(trim($validatedData[$field]));
            }
        }

        // Additional fields for parent model that need to be converted to uppercase
        $parentFieldsToUpper = [
            'father_name', 'mother_name', 'f_blood_group', 'm_blood_group', 'student_blood_group'
        ];

        foreach ($parentFieldsToUpper as $field) {
            if (isset($validatedData[$field])) {
                $validatedData[$field] = strtoupper(trim($validatedData[$field]));
            }
        }

        // Retrieve the token payload
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        Log::info("Academic year: {$academicYr} for student ID: {$studentId}");

        // Find the student by ID
        $student = Student::find($studentId);
        if (!$student) {
            Log::error("Student not found: ID {$studentId}");
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Check if specified fields have changed
        $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
        $isModified = false;

        foreach ($fieldsToCheck as $field) {
            if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
                $isModified = true;
                break;
            }
        }

        // If any of the fields are modified, set 'is_modify' to 'Y'
        if ($isModified) {
            $validatedData['is_modify'] = 'Y';
        }

        // Handle student image if provided
        if ($request->hasFile('student_image')) {
            $image = $request->file('student_image');
            $imageExtension = $image->getClientOriginalExtension();
            $imageName = $studentId . '.' . $imageExtension;
            $imagePath = public_path('uploads/student_image');

            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0755, true);
            }

            $image->move($imagePath, $imageName);
            $validatedData['image_name'] = $imageName;
            Log::info("Image uploaded for student ID: {$studentId}");
        }

        // Include academic year in the update data
        $validatedData['academic_yr'] = $academicYr;

        // Update student information
        $student->update($validatedData);
        Log::info("Student information updated for student ID: {$studentId}");

        // Handle parent details if provided
        $parent = Parents::find($student->parent_id);
        if ($parent) {
            $parent->update($request->only([
                'father_name', 'father_occupation', 'f_office_add', 'f_office_tel',
                'f_mobile', 'f_email', 'parent_adhar_no', 'mother_name',
                'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile',
                'm_emailid', 'm_adhar_no','m_dob','f_dob'
            ]));

            // Determine the phone number based on the 'SetToReceiveSMS' input
            $phoneNo = null;
            if ($request->input('SetToReceiveSMS') == 'Father') {
                $phoneNo = $parent->f_mobile;
            } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
                $phoneNo = $parent->m_mobile;
            }

            // Check if a record already exists with parent_id as the id
            $contactDetails = ContactDetails::find($student->parent_id);
            $phoneNo1 = $parent->f_mobile;
            if ($contactDetails) {
                // If the record exists, update the contact details
                $contactDetails->update([
                    'phone_no' => $phoneNo,
                    'alternate_phone_no' => $parent->f_mobile, // Assuming alternate phone is Father's mobile number
                    'email_id' => $parent->f_email, // Father's email
                    'm_emailid' => $parent->m_emailid, // Mother's email
                    'sms_consent' => 'y', // Store consent for SMS
                ]);
            } else {
                // If the record doesn't exist, create a new one with parent_id as the id
                DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid, sms_consent) VALUES (?, ?, ?, ?, ?, ?)', [
                    $student->parent_id,                
                    $parent->f_mobile,
                    $parent->f_email,
                    $parent->m_emailid,
                    'y', // sms_consent
                ]);
            }

            // Update email ID as username preference
            $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id','P')->first();
            Log::info("Student information updated for student ID: {$user}");

            // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                if ($user) {
                    // Conditional logic for setting email/phone based on SetEmailIDAsUsername
                    if ($request->SetEmailIDAsUsername === 'Father') {
                        $user->user_id = $parent->f_email; // Father's email
                    } elseif ($request->SetEmailIDAsUsername === 'Mother') {
                        $user->user_id = $parent->m_emailid; // Mother's email
                    } elseif ($request->SetEmailIDAsUsername === 'FatherMob') {
                        $user->user_id = $parent->f_mobile; // Father's mobile
                    } elseif ($request->SetEmailIDAsUsername === 'MotherMob') {
                        $user->user_id = $parent->m_mobile; // Mother's mobile
                    }

                    // Save the updated user record
                    $user->save();
                }
            

            $apiData = [
                'user_id' => '',
                'short_name' => 'SACS',
            ];

            $oldEmailPreference = $user->user_id; // Store old email preference for comparison

            // Check if the email preference changed
            if ($oldEmailPreference != $apiData['user_id']) {
                // Call the external API only if the email preference has changed
                $response = Http::post('http://aceventura.in/demo/evolvuUserService/user_create_new', $apiData);
                if ($response->successful()) {
                    Log::info("API call successful for student ID: {$studentId}");
                } else {
                    Log::error("API call failed for student ID: {$studentId}");
                }
            } else {
                Log::info("Email preference unchanged for student ID: {$studentId}");
            }
        }

        return response()->json(['success' => 'Student and parent information updated successfully']);
    } catch (Exception $e) {
        Log::error("Exception occurred for student ID: {$studentId} - " . $e->getMessage());
        return response()->json(['error' => 'An error occurred while updating information'], 500);
    }
   

    // return response()->json($request->all());

}






public function checkUserId($studentId, $userId)
{
    try {
        // Log the start of the request
        Log::info("Checking user ID: {$userId} for student ID: {$studentId}");

        // Retrieve the student record to get the parent_id
        $student = Student::find($studentId);
        if (!$student) {
            Log::error("Student not found: ID {$studentId}");
            return response()->json(['error' => 'Student not found'], 404);
        }

        $parentId = $student->parent_id;
        
        // Retrieve the user_id associated with this parent_id
        $parentUser = UserMaster::where('role_id', 'P')
            ->where('reg_id', $parentId)
            ->first();

          

            // return response()->json($parentUser);

        if (!$parentUser) {
            Log::error("User not found for parent_id: {$parentId}");
            return response()->json(['error' => 'User not found for the given parent ID'], 404);
        }

        $excludedUserId = $parentUser->user_id;

        $userExists = UserMaster::where('reg_id',$parentId)
            ->where('user_id', $userId)
            ->where('role_id','P')
            ->where('user_id', '=', $excludedUserId) 
            ->exists();

        if ($userExists) {
            Log::info("User ID exists and is not excluded for student ID: {$studentId}");
            return response()->json(['exists' => true], 200);
        } else {
            Log::info("User ID does not exist or is excluded for student ID: {$studentId}");
            return response()->json(['exists' => false], 200);
        }
    } catch (\Exception $e) {
        Log::error("Error checking user ID: " . $e->getMessage());
        return response()->json([
            'error' => 'Failed to check user ID.',
            'message' => $e->getMessage(),
        ], 500);
    }
}



// get all the class and their associated Division.
public function getallClass(Request $request)
{
    $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year');

    $divisions = Division::select('name', 'section_id', 'class_id')
        ->with(['getClass' => function($query) {
            $query->select('name', 'class_id');
        }])
        ->where('academic_yr', $academicYr)
        ->distinct()
        ->orderBy('class_id') 
        ->orderBy('section_id', 'asc')
        ->get();

    return response()->json($divisions);
}



//get all the subject allotment data base on the selected class and section 
public function getSubjectAlloted(Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');    
    $section = $request->query('section_id');
    $query = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
            ->where('academic_yr', $academicYr);

    if (!empty($section)) {
        $query->where('section_id', $section);
    } else {
        return response()->json([]);
    }

    $subjectAllotmentList = $query->
                             orderBy('class_id', 'DESC') // multiple section_id, sm_id
                             ->get();
    return response()->json($subjectAllotmentList);
} 
  
// Edit Subject Allotment base on the selectd Subject_id 
public function editSubjectAllotment(Request $request, $subjectId)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    
    $subjectAllotment = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
        ->where('subject_id', $subjectId)
        ->where('academic_yr', $academicYr)
        ->first();

    if (!$subjectAllotment) {
        return response()->json(['error' => 'Subject Allotment not found'], 404);
    }
    return response()->json($subjectAllotment);
}

// Update Subject Allotment base on the selectd Subject_id 
public function updateSubjectAllotment(Request $request, $subjectId)
{
    $request->validate([
        'teacher_id',
    ]);

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    $subjectAllotment = SubjectAllotment::where('subject_id', $subjectId)
        ->where('academic_yr', $academicYr)
        ->first();

    if (!$subjectAllotment) {
        return response()->json(['error' => 'Subject Allotment not found'], 404);
    }

    $subjectAllotment->teacher_id = $request->input('teacher_id');

    if ($subjectAllotment->save()) {
        return response()->json(['message' => 'Teacher updated successfully'], 200);
    }

    return response()->json(['error' => 'Failed to update Teacher'], 500);
}

//Delete Subject Allotment base on the selectd Subject_id 
public function deleteSubjectAllotment(Request $request, $subjectId)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    $subjectAllotment = SubjectAllotment::where('subject_id', $subjectId)
        ->where('academic_yr', $academicYr)
        ->first();

    // if (!$subjectAllotment) {
    //     return response()->json(['error' => 'Subject Allotment not found'], 404);
    // }
    // $isAllocated = StudentMark::where('subject_id', $subjectAllotment->subject_id)
    //     ->exists();

    // if ($isAllocated) {
    //     return response()->json(['error' => 'Subject Allotment cannot be deleted as it is associated with student marks'], 400);
    // }

    if ($subjectAllotment->delete()) {
        return response()->json([
            'status' => 200,
            'message' => 'Subject Allotment  deleted successfully',
            'success' => true
        ]);
    }

    return response()->json([
        'status' => 404,
        'message' => 'Error occured while deleting Subject Allotment',
        'success' => false
    ]);}
 
//Classs list
public function getClassList(Request $request)
{
    $payload = getTokenPayload($request);  
    $academicYr = $payload->get('academic_year');
    $classes =Classes::where('academic_yr', $academicYr)
                     ->orderBy('class_id')  //order 
                     ->get();
    return response()->json($classes);
}
  
//get  the divisions and the subjects base on the selectd class_id 
public function getDivisionsAndSubjects(Request $request, $classId)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');
    
    // Retrieve Class Information
    $class = Classes::find($classId);
    if (!$class) {
        return response()->json(['error' => 'Class not found'], 404);
    }
    
    $className = $class->name;

    // Fetch Division Names
    $divisionNames = Division::where('academic_yr', $academicYr)
        ->where('class_id', $classId)
        ->select('section_id', 'name')
        ->distinct()
        ->get();
    
    // Fetch Subjects Based on Class Type
    $subjects = ($className == 11 || $className == 12)
        ? $this->getAllSubjectsNotHsc()
        : $this->getAllSubjectsOfHsc();
    $count = $subjects->count();
    // Return Combined Response
    return response()->json([
        'divisions' => $divisionNames,
        'subjects' => $subjects,
        'count' => $count
    ]);
}

private function getAllSubjectsOfHsc()
{
    return SubjectMaster::whereIn('subject_type', ['Compulsory', 'Optional', 'Co-Scholastic_hsc', 'Social'])->get();
}

private function getAllSubjectsNotHsc()
{
    return SubjectMaster::whereIn('subject_type', ['Scholastic', 'Co-Scholastic', 'Social'])->get();
}



// Save the Subject Allotment  
// public function storeSubjectAllotment(Request $request)
// {
//     $validatedData = $request->validate([
//         'class_id' => 'required|exists:class,class_id',
//         'section_ids' => 'required|array',
//         'section_ids.*' => 'exists:section,section_id', 
//         'subject_ids' => 'required|array',
//         'subject_ids.*' => 'exists:subject_master,sm_id',
//     ]);

//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     $classId = $validatedData['class_id'];
//     $sectionIds = $validatedData['section_ids'];
//     $subjectIds = $validatedData['subject_ids'];

//     foreach ($sectionIds as $sectionId) {
//         foreach ($subjectIds as $subjectId) {
//             $existingAllotment = SubjectAllotment::where([
//                 ['class_id', '=', $classId],
//                 ['section_id', '=', $sectionId],
//                 ['sm_id', '=', $subjectId],
//                 ['academic_yr', '=', $academicYr],
//             ])->first();

//             if (!$existingAllotment) {
//                 SubjectAllotment::create([
//                     'class_id' => $classId,
//                     'section_id' => $sectionId,
//                     'sm_id' => $subjectId,
//                     'academic_yr' => $academicYr,
//                 ]);
//             }
//         }
//     }

//     return response()->json([
//         'message' => 'Subject allotment details stored successfully',
//     ], 201);
// }

public function storeSubjectAllotment(Request $request)
{
    try {
        Log::info('Starting subject allotment process.', ['request_data' => $request->all()]);

        // Validate the request data
        $validatedData = $request->validate([
            'class_id' => 'required|exists:class,class_id',
            'section_ids' => 'required|array',
            'section_ids.*' => 'exists:section,section_id', 
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subject_master,sm_id',
        ]);

        // Retrieve token payload
        $payload = getTokenPayload($request);
        if (!$payload) {
            Log::error('Invalid or missing token.', ['request_data' => $request->all()]);
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');

        $classId = $validatedData['class_id'];
        $sectionIds = $validatedData['section_ids'];
        $subjectIds = $validatedData['subject_ids'];

        foreach ($sectionIds as $sectionId) {
            Log::info('Processing section', ['section_id' => $sectionId]);

            // Fetch existing subject allotments for the class, section, and academic year
            $existingAllotments = SubjectAllotment::where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('academic_yr', $academicYr)
                ->get();

            $existingSubjectIds = $existingAllotments->pluck('sm_id')->toArray();

            // Identify subject IDs that need to be removed (set to null)
            $subjectIdsToRemove = array_diff($existingSubjectIds, $subjectIds);
            Log::info('Subjects to remove', ['subject_ids_to_remove' => $subjectIdsToRemove]);

            if (!empty($subjectIdsToRemove)) {
                // Set sm_id to null for the removed subjects
                SubjectAllotment::where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('academic_yr', $academicYr)
                    ->whereIn('sm_id', $subjectIdsToRemove)
                    ->update(['sm_id' => null]);

                Log::info('Removed subjects', ['class_id' => $classId, 'section_id' => $sectionId, 'removed_subject_ids' => $subjectIdsToRemove]);
            }

            // Add or update the subject allotments
            foreach ($subjectIds as $subjectId) {
                $existingAllotment = SubjectAllotment::where([
                    ['class_id', '=', $classId],
                    ['section_id', '=', $sectionId],
                    ['sm_id', '=', $subjectId],
                    ['academic_yr', '=', $academicYr],
                ])->first();

                if (!$existingAllotment) {
                    Log::info('Creating new subject allotment', [
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'subject_id' => $subjectId,
                        'academic_year' => $academicYr,
                    ]);

                    SubjectAllotment::create([
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'sm_id' => $subjectId,
                        'academic_yr' => $academicYr,
                    ]);
                } else {
                    Log::info('Subject allotment already exists', [
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'subject_id' => $subjectId,
                        'academic_year' => $academicYr,
                    ]);
                }
            }
        }

        Log::info('Subject allotment process completed successfully.');

        return response()->json([
            'message' => 'Subject allotment details stored successfully',
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error during subject allotment process.', [
            'error_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'error' => 'An error occurred during the subject allotment process. Please try again later.'
        ], 500);
    }
}






public function getSubjectAllotmentWithTeachersBySection(Request $request, $sectionId)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    $subjectAllotments = SubjectAllotment::with(['getSubject', 'getTeacher'])
        ->where('section_id', $sectionId)
        ->where('academic_yr', $academicYr)
        ->get()
        ->groupBy('sm_id');

    // Create a new array to hold the transformed data
    $transformedData = [];

    foreach ($subjectAllotments as $smId => $allotments) {
        // Get the first record to extract subject details (assuming all records for a sm_id have the same subject)
        $firstRecord = $allotments->first();
        $subjectName = $firstRecord->getSubject->name ?? 'Unknown Subject';

        // Transform each allotment, reducing repetition
        $allotmentDetails = $allotments->map(function ($allotment) {
            return [
                'subject_id' => $allotment->subject_id,
                'class_id' => $allotment->class_id,
                'section_id' => $allotment->section_id,
                'teacher_id' => $allotment->teacher_id,
                'teacher' => $allotment->getTeacher ? [
                    'teacher_id' => $allotment->getTeacher->teacher_id,
                    'name' => $allotment->getTeacher->name,
                    'designation' => $allotment->getTeacher->designation,
                    'experience' => $allotment->getTeacher->experience,
                    // Add any other relevant teacher details here
                ] : null,
                'created_at' => $allotment->created_at,
                'updated_at' => $allotment->updated_at,
            ];
        });

        // Add the sm_id and subject name to the transformed data
        $transformedData[$smId] = [
            'subject_name' => $subjectName,
            'details' => $allotmentDetails
        ];
    }

    return response()->json([
        'status' => 'success',
        'data' => $transformedData
    ]);
}



// public function updateTeacherAllotment(Request $request, $classId, $sectionId)
// {
//     // Retrieve the incoming data
//     $subjects = $request->input('subjects'); // Expecting an array of subjects with details
//     $payload = getTokenPayload($request);

//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     // Step 1: Fetch existing records
//     $existingRecords = SubjectAllotment::where('class_id', $classId)
//         ->where('section_id', $sectionId)
//         ->where('academic_yr', $academicYr)
//         ->get();

//     // Collect IDs to keep
//     $idsToKeep = [];

//     // Step 2: Iterate through the subjects from the input and process updates
//     foreach ($subjects as $sm_id => $subjectData) {
//         foreach ($subjectData['details'] as $detail) {
//             // If subject_id is null, get the max subject_id from the database and increment by 1
//             if ($detail['subject_id'] === null) {
//                 $maxSubjectId = SubjectAllotment::max('subject_id');
//                 $detail['subject_id'] = $maxSubjectId ? $maxSubjectId + 1 : 1;
//             }

//             // Store the identifier in the list of IDs to keep
//             $idsToKeep[] = [
//                 'subject_id' => $detail['subject_id'],
//                 'class_id' => $classId,
//                 'section_id' => $sectionId,
//                 'teacher_id' => $detail['teacher_id'],
//                 'sm_id' => $sm_id
//             ];

//             // Check if the subject allotment exists based on subject_id, class_id, section_id, and academic_yr
//             $subjectAllotment = SubjectAllotment::where('subject_id', $detail['subject_id'])
//                 ->where('class_id', $classId)
//                 ->where('section_id', $sectionId)
//                 ->where('academic_yr', $academicYr)
//                 ->first();

//             if ($detail['teacher_id'] === null) {
//                 // If teacher_id is null, delete the record 
//                 if ($subjectAllotment) {
//                     $subjectAllotment->delete();
//                 }
//             } else {
//                 if ($subjectAllotment) {
//                     // Update the existing record
//                     $subjectAllotment->update([
//                         'teacher_id' => $detail['teacher_id'],
//                     ]);
//                 } else {
//                     // Create a new record if it doesn't exist
//                     SubjectAllotment::create([
//                         'subject_id' => $detail['subject_id'],
//                         'class_id' => $classId,
//                         'section_id' => $sectionId,
//                         'teacher_id' => $detail['teacher_id'],
//                         'academic_yr' => $academicYr,
//                         'sm_id' => $sm_id // Use the sm_id from the subjects keys
//                     ]);
//                 }
//             }
//         }
//     }

//     // Step 3: Delete records not present in the input data
//     $idsToKeepArray = array_map(function ($item) {
//         return [
//             'subject_id' => $item['subject_id'],
//             'class_id' => $item['class_id'],
//             'section_id' => $item['section_id'],
//             'teacher_id' => $item['teacher_id'],
//             'sm_id' => $item['sm_id'],
//         ];
//     }, $idsToKeep);

//     $idsToKeepArray = array_map(function ($item) {
//         return implode(',', [
//             $item['subject_id'],
//             $item['class_id'],
//             $item['section_id'],
//             $item['teacher_id'],
//             $item['sm_id'],
//         ]);
//     }, $idsToKeepArray);

//     $existingRecordsToDelete = $existingRecords->filter(function ($record) use ($idsToKeepArray) {
//         $recordKey = implode(',', [
//             $record->subject_id,
//             $record->class_id,
//             $record->section_id,
//             $record->teacher_id,
//             $record->sm_id,
//         ]);

//         return !in_array($recordKey, $idsToKeepArray);
//     });

//     foreach ($existingRecordsToDelete as $record) {
//         $record->delete();
//     }

//     return response()->json([
//         'status' => 'success',
//         'message' => 'Subject allotments updated successfully.',
//     ]);
// }


// public function updateTeacherAllotment(Request $request, $classId, $sectionId)
// {
//     // Retrieve the incoming data
//     $subjects = $request->input('subjects'); // Expecting an array of subjects with details
//     $payload = getTokenPayload($request);

//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     // Step 1: Fetch existing records
//     $existingRecords = SubjectAllotment::where('class_id', $classId)
//         ->where('section_id', $sectionId)
//         ->where('academic_yr', $academicYr)
//         ->get();

//     // Collect IDs to keep
//     $idsToKeep = [];

//     // Step 2: Iterate through the subjects from the input and process updates
//     foreach ($subjects as $sm_id => $subjectData) {
//         foreach ($subjectData['details'] as $detail) {
//             // If subject_id is null, get the max subject_id from the database and increment by 1
//             if ($detail['subject_id'] === null) {
//                 $maxSubjectId = SubjectAllotment::max('subject_id');
//                 $detail['subject_id'] = $maxSubjectId ? $maxSubjectId + 1 : 1;
//             }

//             // Store the identifier in the list of IDs to keep
//             $idsToKeep[] = [
//                 'subject_id' => $detail['subject_id'],
//                 'class_id' => $classId,
//                 'section_id' => $sectionId,
//                 'teacher_id' => $detail['teacher_id'],
//                 'sm_id' => $sm_id
//             ];

//             // Check if the subject allotment exists based on subject_id, class_id, section_id, and academic_yr
//             $subjectAllotment = SubjectAllotment::where('subject_id', $detail['subject_id'])
//                 ->where('class_id', $classId)
//                 ->where('section_id', $sectionId)
//                 ->where('academic_yr', $academicYr)
//                 ->first();

//             if ($detail['teacher_id'] === null) {
//                 // If teacher_id is null, delete the record 
//                 if ($subjectAllotment) {
//                     $subjectAllotment->delete();
//                 }
//             } else {
//                 if ($subjectAllotment) {
//                     // Update the existing record
//                     $subjectAllotment->update([
//                         'teacher_id' => $detail['teacher_id'],
//                     ]);
//                 } else {
//                     // Create a new record if it doesn't exist
//                     SubjectAllotment::create([
//                         'subject_id' => $detail['subject_id'],
//                         'class_id' => $classId,
//                         'section_id' => $sectionId,
//                         'teacher_id' => $detail['teacher_id'],
//                         'academic_yr' => $academicYr,
//                         'sm_id' => $sm_id // Use the sm_id from the subjects keys
//                     ]);
//                 }
//             }
//         }
//     }

//     // Step 3: Delete records not present in the input data, but retain one record with null teacher_id if needed
//     $idsToKeepArray = array_map(function ($item) {
//         return implode(',', [
//             $item['subject_id'],
//             $item['class_id'],
//             $item['section_id'],
//             $item['teacher_id'],
//             $item['sm_id'],
//         ]);
//     }, $idsToKeep);

//     $groupedExistingRecords = $existingRecords->groupBy('sm_id');

//     foreach ($groupedExistingRecords as $sm_id => $records) {
//         $recordsToDelete = $records->filter(function ($record) use ($idsToKeepArray) {
//             $recordKey = implode(',', [
//                 $record->subject_id,
//                 $record->class_id,
//                 $record->section_id,
//                 $record->teacher_id,
//                 $record->sm_id,
//             ]);
//             return !in_array($recordKey, $idsToKeepArray);
//         });

//         $recordCount = $recordsToDelete->count();

//         if ($recordCount > 1) {
//             // Delete all but one, and set teacher_id to null on the remaining one
//             $recordsToDelete->slice(1)->each->delete();
//             $recordsToDelete->first()->update(['teacher_id' => null]);
//         } elseif ($recordCount == 1) {
//             // Just set teacher_id to null
//             $recordsToDelete->first()->update(['teacher_id' => null]);
//         }
//     }

//     return response()->json([
//         'status' => 'success',
//         'message' => 'Subject allotments updated successfully.',
//     ]);
// }

public function updateTeacherAllotment(Request $request, $classId, $sectionId)
{
    // Retrieve the incoming data
    $subjects = $request->input('subjects'); // Expecting an array of subjects with details
    $payload = getTokenPayload($request);

    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    // Step 1: Fetch existing records
    $existingRecords = SubjectAllotment::where('class_id', $classId)
        ->where('section_id', $sectionId)
        ->where('academic_yr', $academicYr)
        ->get();

    // Collect IDs to keep
    $idsToKeep = [];

    // Step 2: Iterate through the subjects from the input and process updates
    foreach ($subjects as $sm_id => $subjectData) {
        // Ensure sm_id is not null or empty before proceeding
        if (empty($sm_id)) {
            return response()->json(['error' => 'Invalid subject module ID (sm_id) provided.'], 400);
        }

        foreach ($subjectData['details'] as $detail) {
            // Ensure subject_id is not null or empty, otherwise generate a new subject_id
            if ($detail['subject_id'] === null) {
                $maxSubjectId = SubjectAllotment::max('subject_id');
                $detail['subject_id'] = $maxSubjectId ? $maxSubjectId + 1 : 1;
            }

            // Store the identifier in the list of IDs to keep
            $idsToKeep[] = [
                'subject_id' => $detail['subject_id'],
                'class_id' => $classId,
                'section_id' => $sectionId,
                'teacher_id' => $detail['teacher_id'],
                'sm_id' => $sm_id
            ];

            // Check if the subject allotment exists based on subject_id, class_id, section_id, and academic_yr
            $subjectAllotment = SubjectAllotment::where('subject_id', $detail['subject_id'])
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('academic_yr', $academicYr)
                ->where('sm_id', $sm_id)
                ->first();

            if ($detail['teacher_id'] === null) {
                // If teacher_id is null, delete the record 
                if ($subjectAllotment) {
                    $subjectAllotment->delete();
                }
            } else {
                if ($subjectAllotment) {
                    // Update the existing record
                    $subjectAllotment->update([
                        'teacher_id' => $detail['teacher_id'],
                    ]);
                } else {
                    // Create a new record if it doesn't exist
                    SubjectAllotment::create([
                        'subject_id' => $detail['subject_id'],
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'teacher_id' => $detail['teacher_id'],
                        'academic_yr' => $academicYr,
                        'sm_id' => $sm_id // Ensure sm_id is correctly passed
                    ]);
                }
            }
        }
    }

    // Step 3: Delete records not present in the input data, but retain one record with null teacher_id if needed
    $idsToKeepArray = array_map(function ($item) {
        return implode(',', [
            $item['subject_id'],
            $item['class_id'],
            $item['section_id'],
            $item['teacher_id'],
            $item['sm_id'],
        ]);
    }, $idsToKeep);

    $groupedExistingRecords = $existingRecords->groupBy('sm_id');

    foreach ($groupedExistingRecords as $sm_id => $records) {
        $recordsToDelete = $records->filter(function ($record) use ($idsToKeepArray) {
            $recordKey = implode(',', [
                $record->subject_id,
                $record->class_id,
                $record->section_id,
                $record->teacher_id,
                $record->sm_id,
            ]);
            return !in_array($recordKey, $idsToKeepArray);
        });

        $recordCount = $recordsToDelete->count();

        if ($recordCount > 1) {
            // Delete all but one, and set teacher_id to null on the remaining one
            $recordsToDelete->slice(1)->each->delete();
            $recordsToDelete->first()->update(['teacher_id' => null]);
        } elseif ($recordCount == 1) {
            // Just set teacher_id to null
            $recordsToDelete->first()->update(['teacher_id' => null]);
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Subject allotments updated successfully.',
    ]);
}

private function determineSubjectId($academicYr, $smId, $teacherId, $existingTeacherRecords)
{
    Log::info('Determining subject_id', [
        'academic_year' => $academicYr,
        'sm_id' => $smId,
        'teacher_id' => $teacherId
    ]);

    $existingRecord = $existingTeacherRecords->firstWhere('teacher_id', $teacherId);
    if ($existingRecord) {
        Log::info('Reusing existing subject_id', ['subject_id' => $existingRecord->subject_id]);
        return $existingRecord->subject_id;
    }

    $newSubjectId = SubjectAllotment::max('subject_id') + 1;
    Log::info('Generated new subject_id', ['subject_id' => $newSubjectId]);

    return $newSubjectId;
}

// Allot teacher Tab APIs 
public function getTeacherNames(Request $request){      
    $teacherList = UserMaster::Where('role_id','T')->get();
    return response()->json($teacherList);
}

// Get the divisions list base on the selected Class
public function getDivisionsbyClass(Request $request, $classId)
{
    $payload = getTokenPayload($request);
    $academicYr = $payload->get('academic_year');    
    // Retrieve Class Information
    $class = Classes::find($classId);    
    // $className = $class->name;
    // Fetch Division Names
    $divisionNames = Division::where('academic_yr', $academicYr)
        ->where('class_id', $classId)
        ->select('section_id', 'name')
        ->orderBy('section_id','asc')
        ->distinct()
        ->get(); 
    
    // Return Combined Response
    return response()->json([
        'divisions' => $divisionNames,
    ]);
}

// Get the Subject list base on the Division  
public function getSubjectsbyDivision(Request $request, $sectionId)
{
    $payload = getTokenPayload($request);
    $academicYr = $payload->get('academic_year');
    
    // Retrieve Division Information
    $division = Division::find($sectionId);
    if (!$division) {
        return response()->json(['error' => '']);
    }

    // Fetch Class Information based on the division
    $class = Classes::find($division->class_id);
    if (!$class) {
        return response()->json(['error' => 'Class not found'], 404);
    }

    $className = $class->name;

    // Determine subjects based on class name
    $subjects = ($className == 11 || $className == 12)
        ? $this->getAllSubjectsNotHsc()
        : $this->getAllSubjectsOfHsc();
    
    // Return Combined Response
    return response()->json([
        'subjects' => $subjects
    ]);
}

public function getPresignSubjectByDivision(Request $request, $classId)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');

    // Retrieve section_id(s) from the query parameters
    $sectionIds = $request->query('section_id', []);

    // Ensure sectionIds is an array
    if (!is_array($sectionIds)) {
        return response()->json(['error' => 'section_id must be an array'], 400);
    }

    $subjects = SubjectAllotment::with('getSubject')
    ->select('sm_id', DB::raw('MAX(subject_id) as subject_id')) // Aggregate subject_id if needed
    ->where('academic_yr', $academicYr)
    ->where('class_id', $classId)
    ->whereIn('section_id', $sectionIds)
    ->groupBy('sm_id')
    ->get();


    $count = $subjects->count();

    return response()->json([
        'subjects' => $subjects,
        'count' => $count
    ]);
}

public function getPresignSubjectByTeacher(Request $request,$classID, $sectionId,$teacherID)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    
    $subjects = SubjectAllotment::with('getSubject')
    ->where('academic_yr', $academicYr)
    ->where('class_id', $classID)
    ->where('section_id', $sectionId)
    ->where('teacher_id', $teacherID)
    ->groupBy('sm_id', 'subject_id')
    ->get(); 
    return response()->json([
        'subjects' => $subjects
    ]);
}

// public function updateOrCreateSubjectAllotments($class_id, $section_id, Request $request)
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');
//     $validatedData = $request->validate([
//         'subjects' => 'required|array',
//         'subjects.*.sm_id' => 'required|integer|exists:subject_master,sm_id',
//         'subjects.*.teacher_id' => 'nullable|integer|exists:teacher,teacher_id',
//         'subjects.*.subject_id' => 'nullable|integer|exists:subject,subject_id',
//     ]);

//     $subjects = $validatedData['subjects'];

//     foreach ($subjects as $subjectData) {
//         if (isset($subjectData['subject_id'])) {
//             // Update existing record
//             SubjectAllotment::updateOrCreate(
//                 [
//                     'subject_id' => $subjectData['subject_id'],
//                     'class_id' => $class_id,
//                     'section_id' => $section_id,
//                     'academic_yr' => $academicYr,

//                 ],
//                 [
//                     'sm_id' => $subjectData['sm_id'],
//                     'teacher_id' => $subjectData['teacher_id'],
//                 ]
//             );
//         } else {
//             // Create new record
//             SubjectAllotment::updateOrCreate(
//                 [
//                     'class_id' => $class_id,
//                     'section_id' => $section_id,
//                     'sm_id' => $subjectData['sm_id'],
//                     'academic_yr' => $academicYr, 

//                 ],
//                 [
//                     'teacher_id' => $subjectData['teacher_id'],
//                 ]
//             );
//         }
//     }

//     return response()->json(['success' => 'Subject allotments updated or created successfully']);
// }

public function updateOrCreateSubjectAllotments($class_id, $section_id, Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    $validatedData = $request->validate([
        'subjects' => 'required|array',
        'subjects.*.sm_id' => 'required|integer|exists:subject_master,sm_id',
        'subjects.*.teacher_id' => 'nullable|integer|exists:teacher,teacher_id',
        'subjects.*.subject_id' => 'nullable|integer|exists:subject,subject_id',
    ]);

    $subjects = $validatedData['subjects'];
    
    // Get existing subject allotments for the class, section, and academic year
    $existingAllotments = SubjectAllotment::where('class_id', $class_id)
        ->where('section_id', $section_id)
        ->where('academic_yr', $academicYr)
        ->get()
        ->keyBy('sm_id'); // Use sm_id as the key for easy comparison

    $inputSmIds = collect($subjects)->pluck('sm_id')->toArray();
    $existingSmIds = $existingAllotments->pluck('sm_id')->toArray();

    // Iterate through the input subjects and update or create records
    foreach ($subjects as $subjectData) {
        if (isset($subjectData['subject_id'])) {
            // Update existing record
            SubjectAllotment::updateOrCreate(
                [
                    'subject_id' => $subjectData['subject_id'],
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'academic_yr' => $academicYr,
                ],
                [
                    'sm_id' => $subjectData['sm_id'],
                    'teacher_id' => $subjectData['teacher_id'],
                ]
            );
        } else {
            // Create new record
            SubjectAllotment::updateOrCreate(
                [
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'sm_id' => $subjectData['sm_id'],
                    'academic_yr' => $academicYr,
                ],
                [
                    'teacher_id' => $subjectData['teacher_id'],
                ]
            );
        }
    }

    // Handle extra records in the existing allotments that are not in the input
    $extraSmIds = array_diff($existingSmIds, $inputSmIds);
    foreach ($extraSmIds as $extraSmId) {
        $existingAllotments[$extraSmId]->update(['teacher_id' => null]);
    }

    return response()->json(['success' => 'Subject allotments updated or created successfully']);
}

// Metods for the Subject for report card  
public function getSubjectsForReportCard(Request $request)
{
    $subjects = SubjectForReportCard::all();
    return response()->json(
        ['subjects'=>$subjects]
    );
}

public function checkSubjectNameForReportCard(Request $request)
{
    $validatedData = $request->validate([
        'sequence' => 'required|string|max:30',
    ]);

    $sequence = $validatedData['sequence'];
    // return response()->json($sequence);
    $exists = SubjectForReportCard::where(DB::raw('LOWER(sequence)'), strtolower($sequence))->exists();
    $exists = SubjectForReportCard::where('sequence', $sequence)->exists();
    return response()->json(['exists' => $exists]);
}


public function storeSubjectForReportCard(Request $request)
{
    $messages = [
        'name.required' => 'The name field is required.',
        'sequence.required' => 'The sequence field is required.',
    ];

    try {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                
            ],
            'sequence' => [
                'required',
                'Integer'
               
            ],
        ], $messages);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => 422,
            'errors' => $e->errors(),
        ], 422);
    }

    $subject = new SubjectForReportCard();
    $subject->name = $validatedData['name'];
    $subject->sequence = $validatedData['sequence'];
    $subject->save();

    return response()->json([
        'status' => 201,
        'message' => 'Subject created successfully',
    ], 201);
}

// public function updateSubjectForReportCard(Request $request, $sub_rc_master_id)
//     {
//         $messages = [
//             'name.required' => 'The name field is required.',
//             // 'name.unique' => 'The name has already been taken.',
//             'sequence.required' => 'The sequence field is required.',
//             // 'subject_type.unique' => 'The subject type has already been taken.',
//         ];

//         try {
//             $validatedData = $request->validate([
//                 'name' => [
//                     'required',
//                     'string',
//                     'max:30',
//                 ],
//                 'sequence' => [
//                     'required',
//                     'Integer'
                    
//                 ],
//             ], $messages);
//         } catch (\Illuminate\Validation\ValidationException $e) {
//             return response()->json([
//                 'status' => 422,
//                 'errors' => $e->errors(),
//             ], 422);
//         }

//         $subject = SubjectForReportCard::find($sub_rc_master_id);

//         if (!$subject) {
//             return response()->json([
//                 'status' => 404,
//                 'message' => 'Subject not found',
//             ], 404);
//         }

//         $subject->name = $validatedData['name'];
//         $subject->sequence = $validatedData['sequence'];
//         $subject->save();

//         return response()->json([
//             'status' => 200,
//             'message' => 'Subject updated successfully',
//         ], 200);
//     }

public function updateSubjectForReportCard(Request $request, $sub_rc_master_id)
{
    $messages = [
        'name.required' => 'The name field is required.',
        'sequence.required' => 'The sequence field is required.',
        'sequence.unique' => 'The sequence has already been taken.',
    ];

    try {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
            ],
            'sequence' => [
                'required',
                'integer',
                // Ensures the sequence is unique, but ignores the current record's sequence
                Rule::unique('subjects_on_report_card_master', 'sequence')->ignore($sub_rc_master_id, 'sub_rc_master_id')
            ],
        ], $messages);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 422,
            'errors' => $e->errors(),
        ], 422);
    }

    // Find the subject by sub_rc_master_id
    $subject = SubjectForReportCard::find($sub_rc_master_id);

    if (!$subject) {
        return response()->json([
            'status' => 404,
            'message' => 'Subject not found',
        ], 404);
    }

    // Update the subject with validated data
    $subject->name = $validatedData['name'];
    $subject->sequence = $validatedData['sequence'];
    $subject->save();

    return response()->json([
        'status' => 200,
        'message' => 'Subject updated successfully',
    ], 200);
}


    

public function editSubjectForReportCard($sub_rc_master_id)
{
    $subject = SubjectForReportCard::find($sub_rc_master_id);

    if (!$subject) {
        return response()->json([
            'status' => 404,
            'message' => 'Subject not found',
        ]);
    }

    return response()->json($subject);
}

public function deleteSubjectForReportCard($sub_rc_master_id)
{
    $subject = SubjectForReportCard::find($sub_rc_master_id);

    if (!$subject) {
        return response()->json([
            'status' => 404,
            'message' => 'Subject not found',
        ]);
    }

    //Delete condition pending 
    // $subjectAllotmentExists = SubjectAllotment::where('sm_id', $id)->exists();
    // if ($subjectAllotmentExists) {
    //     return response()->json([
    //         'status' => 400,
    //         'message' => 'Subject cannot be deleted because it is associated with other records.',
    //     ]);
    // }
    $subject->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Subject deleted successfully',
        'success' => true
    ]);
}


// Method for Subject Allotment for the report Card 
 
public function getSubjectAllotmentForReportCard(Request $request,$class_id)
{  
     $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year');

    $subjectAllotments = SubjectAllotmentForReportCard::where('academic_yr',$academicYr)
                                ->where('class_id', $class_id)
                                ->with('getSubjectsForReportCard','getClases')
                                ->get();

    return response()->json([
        'subjectAllotments' => $subjectAllotments,
    ]);
}
// for Edit 
public function getSubjectAllotmentById($sub_reportcard_id)
{
    $subjectAllotment = SubjectAllotmentForReportCard::where('sub_reportcard_id', $sub_reportcard_id)
                                ->with('getSubjectsForReportCard')
                                ->first();

    if (!$subjectAllotment) {
        return response()->json(['error' => 'Subject Allotment not found'], 404);
    }

    return response()->json([
        'subjectAllotment' => $subjectAllotment,
    ]);
}

// for update 
public function updateSubjectType(Request $request, $sub_reportcard_id)
{
    $subjectAllotment = SubjectAllotmentForReportCard::find($sub_reportcard_id);
    if (!$subjectAllotment) {
        return response()->json(['error' => 'Subject Allotment not found'], 404);
    }

    $request->validate([
        'subject_type' => 'required|string',
    ]);
    $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year');

    $subjectAllotment->subject_type = $request->input('subject_type');
    $subjectAllotment->academic_yr = $academicYr;

    $subjectAllotment->save();

    return response()->json(['message' => 'Subject type updated successfully']);
}

// for delete
public function deleteSubjectAllotmentforReportcard($sub_reportcard_id)
{
    $subjectAllotment = SubjectAllotmentForReportCard::find($sub_reportcard_id);

    if (!$subjectAllotment) {
        return response()->json(['error' => 'Subject Allotment not found'], 404);
    }

    // // Check if the subject allotment is associated with any MarkHeading
    // $isAssociatedWithMarkHeading = MarkHeading::where('sub_reportcard_id', $sub_reportcard_id)->exists();

    // if ($isAssociatedWithMarkHeading) {
    //     return response()->json(['error' => 'Cannot delete: Subject allotment is associated with a Mark Heading'], 400);
    // }

    // Hard delete the subject allotment
    $subjectAllotment->delete();

    return response()->json(['message' => 'Subject allotment deleted successfully']);
}
   // for the Edit 
public function editSubjectAllotmentforReportCard(Request $request, $class_id, $subject_type)
{   
    $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year');
    // Fetch the list of subjects for the selected class_id and subject_type
    $subjectAllotments = SubjectAllotmentForReportCard::where('academic_yr',$academicYr)
                                    ->where('class_id', $class_id)
                                    ->where('subject_type', $subject_type)
                                    ->with('getSubjectsForReportCard') // Include subject details
                                    ->get();

    // Check if subject allotments are found
    if ($subjectAllotments->isEmpty()) {
        return response()->json([]);
    }

    return response()->json([
        'message' => 'Subject allotments retrieved successfully',
        'subjectAllotments' => $subjectAllotments,
    ]);
}


public function createOrUpdateSubjectAllotment(Request $request, $class_id)
{
    $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year'); // Get academic year from token payload

    // Validate the request parameters
    $request->validate([
        'subject_type'     => 'required|string',
        'subject_ids'      => 'array',
        'subject_ids.*'    => 'integer',
    ]);

    // Log the incoming request
    Log::info('Received request to create/update subject allotment', [
        'class_id' => $class_id,
        'subject_type' => $request->input('subject_type'),
        'subject_ids' => $request->input('subject_ids'),
        'academic_yr' => $academicYr, // Log the academic year for reference
    ]);

    // Fetch existing subject allotments
    $existingAllotments = SubjectAllotmentForReportCard::where('class_id', $class_id)
                                    ->where('subject_type', $request->input('subject_type'))
                                    ->where('academic_yr', $academicYr) // Ensure academic year is considered
                                    ->get();

    Log::info('Fetched existing subject allotments', ['existingAllotments' => $existingAllotments]);

    $existingSubjectIds = $existingAllotments->pluck('sub_rc_master_id')->toArray();
    $inputSubjectIds = $request->input('subject_ids');

    $newSubjectIds = array_diff($inputSubjectIds, $existingSubjectIds);
    $deallocateSubjectIds = array_diff($existingSubjectIds, $inputSubjectIds);
    $updateSubjectIds = array_intersect($inputSubjectIds, $existingSubjectIds);

    Log::info('Comparison results', [
        'newSubjectIds' => $newSubjectIds,
        'updateSubjectIds' => $updateSubjectIds,
        'deallocateSubjectIds' => $deallocateSubjectIds
    ]);

    // Create new allotments
    foreach ($newSubjectIds as $subjectId) {
        SubjectAllotmentForReportCard::create([
            'class_id'         => $class_id,
            'sub_rc_master_id' => $subjectId,
            'subject_type'     => $request->input('subject_type'),
            'academic_yr'      => $academicYr, // Set academic year
        ]);

        Log::info('Created new subject allotment', [
            'class_id' => $class_id,
            'sub_rc_master_id' => $subjectId,
            'subject_type' => $request->input('subject_type'),
            'academic_yr' => $academicYr,
        ]);
    }

    // Update existing allotments
    foreach ($updateSubjectIds as $subjectId) {
        $allotment = SubjectAllotmentForReportCard::where('class_id', $class_id)
                        ->where('subject_type', $request->input('subject_type'))
                        ->where('academic_yr', $academicYr) // Ensure academic year is considered
                        ->where('sub_rc_master_id', $subjectId)
                        ->first();

        Log::info('Fetched allotment for update', [
            'allotment' => $allotment
        ]);

        if ($allotment) {
            $allotment->sub_rc_master_id = $subjectId;
            $allotment->academic_yr = $academicYr; // Update academic year
            $allotment->save();

            Log::info('Updated subject allotment', [
                'class_id' => $class_id,
                'sub_rc_master_id' => $subjectId,
                'subject_type' => $request->input('subject_type'),
                'academic_yr' => $academicYr
            ]);
        } else {
            Log::warning('Subject allotment not found for update', [
                'class_id' => $class_id,
                'sub_rc_master_id' => $subjectId,
                'subject_type' => $request->input('subject_type')
            ]);
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }
    }

    // Deallocate subjects
    foreach ($deallocateSubjectIds as $subjectId) {
        $allotment = SubjectAllotmentForReportCard::where('class_id', $class_id)
                        ->where('subject_type', $request->input('subject_type'))
                        ->where('academic_yr', $academicYr) // Ensure academic year is considered
                        ->where('sub_rc_master_id', $subjectId)
                        ->first();

        Log::info('Fetched allotment for deallocation', [
            'allotment' => $allotment
        ]);

        if ($allotment) {
            $allotment->delete();

            Log::info('Deallocated subject allotment', [
                'class_id' => $class_id,
                'sub_rc_master_id' => $subjectId,
                'subject_type' => $request->input('subject_type'),
                'academic_yr' => $academicYr
            ]);
        } else {
            Log::warning('Subject allotment not found for deallocation', [
                'class_id' => $class_id,
                'sub_rc_master_id' => $subjectId,
                'subject_type' => $request->input('subject_type')
            ]);
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }
    }

    Log::info('Subject allotments updated successfully for class_id', ['class_id' => $class_id, 'academic_yr' => $academicYr]);

    return response()->json(['message' => 'Subject allotments updated successfully']);
}




}
















































































































// public function updateStudentAndParent(Request $request, $studentId)
// {
//     // Validate the incoming request for all fields
//     $validatedData = $request->validate([
//         // Student model fields
//         'first_name' => 'required|string|max:255',
//         'mid_name' => 'nullable|string|max:255',
//         'last_name' => 'required|string|max:255',
//         'student_name' => 'nullable|string|max:255',
//         'dob' => 'required|date',
//         'gender' => 'required|string',
//         'admission_date' => 'nullable|date',
//         'stud_id_no' => 'nullable|string|max:255',
//         'mother_tongue' => 'nullable|string|max:255',
//         'birth_place' => 'nullable|string|max:255',
//         'admission_class' => 'nullable|string|max:255',
//         'roll_no' => 'nullable|string|max:255',
//         'class_id' => 'required|integer',
//         'section_id' => 'nullable|integer',
//         'fees_paid' => 'nullable|numeric',
//         'blood_group' => 'nullable|string|max:255',
//         'religion' => 'nullable|string|max:255',
//         'caste' => 'nullable|string|max:255',
//         'subcaste' => 'nullable|string|max:255',
//         'transport_mode' => 'nullable|string|max:255',
//         'vehicle_no' => 'nullable|string|max:255',
//         'bus_id' => 'nullable|integer',
//         'emergency_name' => 'nullable|string|max:255',
//         'emergency_contact' => 'nullable|string|max:255',
//         'emergency_add' => 'nullable|string|max:255',
//         'height' => 'nullable|numeric',
//         'weight' => 'nullable|numeric',
//         'has_specs' => 'nullable|boolean',
//         'allergies' => 'nullable|string|max:255',
//         'nationality' => 'nullable|string|max:255',
//         'permant_add' => 'nullable|string|max:255',
//         'city' => 'nullable|string|max:255',
//         'state' => 'nullable|string|max:255',
//         'pincode' => 'nullable|string|max:255',
//         'IsDelete' => 'nullable|boolean',
//         'prev_year_student_id' => 'nullable|integer',
//         'isPromoted' => 'nullable|boolean',
//         'isNew' => 'nullable|boolean',
//         'isModify' => 'nullable|boolean',
//         'isActive' => 'nullable|boolean',
//         'reg_no' => 'nullable|string|max:255',
//         'house' => 'nullable|string|max:255',
//         'stu_aadhaar_no' => 'nullable|string|max:255',
//         'category' => 'nullable|string|max:255',
//         'last_date' => 'nullable|date',
//         'slc_no' => 'nullable|string|max:255',
//         'slc_issue_date' => 'nullable|date',
//         'leaving_remark' => 'nullable|string|max:255',
//         'deleted_date' => 'nullable|date',
//         'deleted_by' => 'nullable|string|max:255',
//         'image_name' => 'nullable|string|max:255',
//         'guardian_name' => 'nullable|string|max:255',
//         'guardian_add' => 'nullable|string|max:255',
//         'guardian_mobile' => 'nullable|string|max:255',
//         'relation' => 'nullable|string|max:255',
//         'guardian_image_name' => 'nullable|string|max:255',
//         'udise_pen_no' => 'nullable|string|max:255',
//         'added_bk_date' => 'nullable|date',
//         'added_by' => 'nullable|string|max:255',

//         // Parent model fields
//         'father_name' => 'nullable|string|max:255',
//         'father_occupation' => 'nullable|string|max:255',
//         'f_office_add' => 'nullable|string|max:255',
//         'f_office_tel' => 'nullable|string|max:255',
//         'f_mobile' => 'nullable|string|max:255',
//         'f_email' => 'nullable|string|max:255',
//         'mother_name' => 'nullable|string|max:255',
//         'mother_occupation' => 'nullable|string|max:255',
//         'm_office_add' => 'nullable|string|max:255',
//         'm_office_tel' => 'nullable|string|max:255',
//         'm_mobile' => 'nullable|string|max:255',
//         'm_emailid' => 'nullable|string|max:255',
//         'parent_adhar_no' => 'nullable|string|max:255',
//         'm_adhar_no' => 'nullable|string|max:255',
//         'f_dob' => 'nullable|date',
//         'm_dob' => 'nullable|date',
//         'f_blood_group' => 'nullable|string|max:255',
//         'm_blood_group' => 'nullable|string|max:255',
//         'father_image_name' => 'nullable|string|max:255',
//         'mother_image_name' => 'nullable|string|max:255',

//         'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//         'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother',
//     ]);

//     // Retrieve the token payload
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     // Find the student by ID
//     $student = Student::find($studentId);

//     if (!$student) {
//         return response()->json(['error' => 'Student not found'], 404);
//     }

//     // Handle student image if provided
//     if ($request->has('student_image')) {
//         $base64Image = $request->input('student_image');
//         $imageParts = explode(';', $base64Image);
//         $imageType = explode(':', $imageParts[0])[1];
//         $imageExtension = str_replace('image/', '', $imageType);
//         $image = str_replace('data:image/' . $imageExtension . ';base64,', '', $base64Image);
//         $image = str_replace(' ', '+', $image);
//         $imageName = $studentId . '.' . $imageExtension;
//         $imagePath = public_path('uploads/student_image');

//         // Create directory if it doesn't exist
//         if (!file_exists($imagePath)) {
//             mkdir($imagePath, 0755, true);
//         }

//         // Save the image
//         file_put_contents($imagePath . '/' . $imageName, base64_decode($image));
//         $validatedData['image_name'] = $imageName;
//     }

//     // Include academic year in the update data
//     $validatedData['academic_yr'] = $academicYr;

//     // Update student information
//     $student->update($validatedData);

//     // Handle parent details if provided
//     $parent = Parents::find($student->parent_id);

//     if ($parent) {
//         $parentData = $request->only([
//             'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 'f_mobile', 'f_email',
//             'mother_name', 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid',
//             'parent_adhar_no', 'm_adhar_no', 'f_dob', 'm_dob', 'f_blood_group', 'm_blood_group',
//         ]);

//         // Update SMS contact preference
//         $contactDetails = ContactDetails::where('id', $student->parent_id)->first();
//         if ($request->input('SetToReceiveSMS') == 'Father') {
//             $contactDetails->update(['phone_no' => $parent->f_mobile]);
//         } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//             $contactDetails->update(['phone_no' => $parent->m_mobile]);
//         }

//         // Update email ID as username preference
//         $user = UserMaster::where('reg_id', $student->parent_id)->first();
//         if ($request->input('SetEmailIDAsUsername') == 'Father') {  
//             $user->update(['user_id' => $parent->f_email]);
//         } elseif ($request->input('SetEmailIDAsUsername') == 'Mother') {
//             $user->update(['user_id' => $parent->m_emailid]);
//         }

//         $parent->update($parentData);
//     }

//     return response()->json([
//         "status" => "success",
//         "message" => "Student updated successfully",
//         "data" => $student
//     ]);
// }


// public function updateStudentAndParent(Request $request, $studentId)
// {
//     // Validate the incoming request for all fields
//     $validatedData = $request->validate([
//         // Student model fields
//         'first_name' => 'required|string|max:100',
//         'mid_name' => 'nullable|string|max:100',
//         'last_name' => 'required|string|max:100',
//         'house' => 'nullable|string|max:100',
//         'student_name' => 'required|string|max:100',
//         'dob' => 'required|date',
//         'admission_date' => 'required|date', // Changed from 'nullable' to 'required'
//         'stud_id_no' => 'nullable|string|max:25',
//         'stu_aadhaar_no' => 'required|string|max:14',
//         'gender' => 'required|string',
//         'mother_tongue' => 'required|string|max:20', // Changed from 'nullable' to 'required'
//         'birth_place' => 'nullable|string|max:50',
//         'admission_class' => 'required|string|max:255',
//         'city' => 'required|string|max:100', // Changed from 'nullable' to 'required'
//         'state' => 'required|string|max:100', // Changed from 'nullable' to 'required'
//         'roll_no' => 'nullable|string|max:11',
//         'class_id' => 'required|integer',
//         'section_id' => 'required|integer', // Changed from 'nullable' to 'required'
//         'religion' => 'required|string|max:255', // Changed from 'nullable' to 'required'
//         'caste' => 'nullable|string|max:100',
//         'subcaste' => 'required|string|max:255', // Changed from 'nullable' to 'required'
//         'vehicle_no' => 'nullable|string|max:13',
//         'emergency_name' => 'nullable|string|max:100',
//         'emergency_contact' => 'nullable|string|max:11',
//         'emergency_add' => 'nullable|string|max:200',
//         'height' => 'nullable|numeric|max:4.1',
//         'weight' => 'nullable|numeric|max:4.1',
//         'allergies' => 'nullable|string|max:200',
//         'nationality' => 'required|string|max:100', // Changed from 'nullable' to 'required'
//         'pincode' => 'nullable|string|max:11',
//         'image_name' => 'nullable|string|max:255',

//         // Parent model fields
//         'father_name' => 'required|string|max:100',
//         'father_occupation' => 'nullable|string|max:100',
//         'f_office_add' => 'nullable|string|max:200',
//         'f_office_tel' => 'nullable|string|max:11',
//         'f_mobile' => 'required|string|max:10', // Changed from 'nullable' to 'required'
//         'f_email' => 'required|string|max:50', // Changed from 'nullable' to 'required'
//         'father_adhar_card' => 'required|string|max:14',
//         'mother_name' => 'required|string|max:100',
//         'mother_occupation' => 'nullable|string|max:100',
//         'm_office_add' => 'nullable|string|max:200',
//         'm_office_tel' => 'nullable|string|max:11',
//         'm_mobile' => 'required|string|max:10', // Changed from 'nullable' to 'required'
//         'm_emailid' => 'required|string|max:50', // Changed from 'nullable' to 'required'
//         'mother_adhar_card' => 'required|string|max:14',

//         // Preferences for SMS and email as username
//         'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//         'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother',
//     ]);

//     // Retrieve the token payload
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     // Find the student by ID
//     $student = Student::find($studentId);

//     if (!$student) {
//         return response()->json(['error' => 'Student not found'], 404);
//     }

//     // Handle student image if provided
//     if ($request->has('student_image')) {
//         $base64Image = $request->input('student_image');
//         $imageParts = explode(';', $base64Image);
//         $imageType = explode(':', $imageParts[0])[1];
//         $imageExtension = str_replace('image/', '', $imageType);
//         $image = str_replace('data:image/' . $imageExtension . ';base64,', '', $base64Image);
//         $image = str_replace(' ', '+', $image);
//         $imageName = $studentId . '.' . $imageExtension;
//         $imagePath = public_path('uploads/student_image');

//         // Create directory if it doesn't exist
//         if (!file_exists($imagePath)) {
//             mkdir($imagePath, 0755, true);
//         }

//         // Save the image
//         file_put_contents($imagePath . '/' . $imageName, base64_decode($image));
//         $validatedData['image_name'] = $imageName;
//     }

//     // Include academic year in the update data
//     $validatedData['academic_yr'] = $academicYr;

//     // Update student information
//     $student->update($validatedData);

//     // Handle parent details if provided
//     $parent = Parents::find($student->parent_id);

//     if ($parent) {
//         $parentData = $request->only([
//             'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 'f_mobile', 'f_email',
//             'mother_name', 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid',
//             'parent_adhar_no', 'm_adhar_no', 'father_adhar_card', 'mother_adhar_card',
//         ]);

//         // Update SMS contact preference
//         $contactDetails = ContactDetails::where('id', $student->parent_id)->first();
//         if ($request->input('SetToReceiveSMS') == 'Father') {
//             $contactDetails->update(['phone_no' => $parent->f_mobile]);
//         } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//             $contactDetails->update(['phone_no' => $parent->m_mobile]);
//         }

//         // Update email ID as username preference
//         $user = UserMaster::where('reg_id', $student->parent_id)->first();
//         if ($request->input('SetEmailIDAsUsername') == 'Father') {  
//             $user->update(['user_id' => $parent->f_email]);
//         } elseif ($request->input('SetEmailIDAsUsername') == 'Mother') {
//             $user->update(['user_id' => $parent->m_emailid]);
//         }

//         $parent->update($parentData);
//     }

//     return response()->json([
//         "status" => "success",
//         "message" => "Student and parent information updated successfully",
//         "data" => $student
//     ]);
// }


// public function updateStudentAndParent(Request $request, $studentId)
// {
//     // Validate the incoming request for all fields
//     $validatedData = $request->validate([
//         // Student model fields
//         'first_name' => 'required|string|max:100',
//         'mid_name' => 'nullable|string|max:100',
//         'last_name' => 'required|string|max:100',
//         'house' => 'nullable|string|max:100',
//         'student_name' => 'required|string|max:100',
//         'dob' => 'required|date',
//         'admission_date' => 'required|date',
//         'stud_id_no' => 'nullable|string|max:25',
//         'stu_aadhaar_no' => 'required|string|max:14',
//         'gender' => 'required|string',
//         'mother_tongue' => 'required|string|max:20',
//         'birth_place' => 'nullable|string|max:50',
//         'admission_class' => 'required|string|max:255',
//         'city' => 'required|string|max:100',
//         'state' => 'required|string|max:100',
//         'roll_no' => 'nullable|string|max:11',
//         'class_id' => 'required|integer',
//         'section_id' => 'required|integer',
//         'religion' => 'required|string|max:255',
//         'caste' => 'nullable|string|max:100',
//         'subcaste' => 'required|string|max:255',
//         'vehicle_no' => 'nullable|string|max:13',
//         'emergency_name' => 'nullable|string|max:100',
//         'emergency_contact' => 'nullable|string|max:11',
//         'emergency_add' => 'nullable|string|max:200',
//         'height' => 'nullable|numeric|max:4.1',
//         'weight' => 'nullable|numeric|max:4.1',
//         'allergies' => 'nullable|string|max:200',
//         'nationality' => 'required|string|max:100',
//         'pincode' => 'nullable|string|max:11',
//         'image_name' => 'nullable|string|max:255',

//         // Parent model fields
//         'father_name' => 'required|string|max:100',
//         'father_occupation' => 'nullable|string|max:100',
//         'f_office_add' => 'nullable|string|max:200',
//         'f_office_tel' => 'nullable|string|max:11',
//         'f_mobile' => 'required|string|max:10',
//         'f_email' => 'required|string|max:50',
//         'father_adhar_card' => 'required|string|max:14',
//         'mother_name' => 'required|string|max:100',
//         'mother_occupation' => 'nullable|string|max:100',
//         'm_office_add' => 'nullable|string|max:200',
//         'm_office_tel' => 'nullable|string|max:11',
//         'm_mobile' => 'required|string|max:10',
//         'm_emailid' => 'required|string|max:50',
//         'mother_adhar_card' => 'required|string|max:14',

//         // Preferences for SMS and email as username
//         'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//         'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother',
//     ]);

//     // Retrieve the token payload
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     // Find the student by ID
//     $student = Student::find($studentId);
//     if (!$student) {
//         return response()->json(['error' => 'Student not found'], 404);
//     }

//     // Check if specified fields have changed
//     $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
//     $isModified = false;

//     foreach ($fieldsToCheck as $field) {
//         if ($student->$field != $validatedData[$field]) {
//             $isModified = true;
//             break;
//         }
//     }

//     // If any of the fields are modified, set 'is_modify' to 'Y'
//     if ($isModified) {
//         $validatedData['is_modify'] = 'Y';
//     }

//     // Handle student image if provided
//     if ($request->has('student_image')) {
//         $base64Image = $request->input('student_image');
//         $imageParts = explode(';', $base64Image);
//         $imageType = explode(':', $imageParts[0])[1];
//         $imageExtension = str_replace('image/', '', $imageType);
//         $image = str_replace('data:image/' . $imageExtension . ';base64,', '', $base64Image);
//         $image = str_replace(' ', '+', $image);
//         $imageName = $studentId . '.' . $imageExtension;
//         $imagePath = public_path('uploads/student_image');

//         if (!file_exists($imagePath)) {
//             mkdir($imagePath, 0755, true);
//         }

//         file_put_contents($imagePath . '/' . $imageName, base64_decode($image));
//         $validatedData['image_name'] = $imageName;
//     }

//     // Include academic year in the update data
//     $validatedData['academic_yr'] = $academicYr;

//     // Update student information
//     $student->update($validatedData);

//     // Handle parent details if provided
//     $parent = Parents::find($student->parent_id);
//     if ($parent) {
//         $parentData = $request->only([
//             'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 'f_mobile', 'f_email',
//             'mother_name', 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid',
//             'parent_adhar_no', 'm_adhar_no', 'father_adhar_card', 'mother_adhar_card',
//         ]);

//         // Update SMS contact preference
//         $contactDetails = ContactDetails::where('id', $student->parent_id)->first();
//         if ($request->input('SetToReceiveSMS') == 'Father') {
//             $contactDetails->update(['phone_no' => $parent->f_mobile]);
//         } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//             $contactDetails->update(['phone_no' => $parent->m_mobile]);
//         }

//         // Update email ID as username preference
//         $user = UserMaster::where('reg_id', $student->parent_id)->first();
//         if ($request->input('SetEmailIDAsUsername') == 'Father') {
//             $user->update(['user_id' => $parent->f_email]);
//         } elseif ($request->input('SetEmailIDAsUsername') == 'Mother') {
//             $user->update(['user_id' => $parent->m_emailid]);
//         }

        

//         $parent->update($parentData);
//     }

//     return response()->json([
//         "status" => "success",
//         "message" => "Student and parent information updated successfully",
//         "data" => $student
//     ]);
// }





// public function storeSubject(Request $request)
// {
//     $messages = [
//         'name.required' => 'The name field is required.',
//         'name.regex' => 'The name may only contain letters.',
//         'subject_type.required' => 'The subject type field is required.',
//         'subject_type.regex' => 'The subject type may only contain letters.',
//     ];

//     $validatedData = $request->validate([
//         'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
//         'subject_type' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
//     ], $messages);

//     $subject = new SubjectMaster();
//     $subject->name = $validatedData['name'];
//     $subject->subject_type = $validatedData['subject_type'];
//     $subject->save();

//     return response()->json([
//         'status' => 201,
//         'message' => 'Subject created successfully',
//     ], 201);
// }

// public function updateSubject(Request $request, $id)
// {
//     $messages = [
//         'name.required' => 'The name field is required.',
//         'name.regex' => 'The name may only contain letters.',
//         'subject_type.required' => 'The subject type field is required.',
//         'subject_type.regex' => 'The subject type may only contain letters.',
//     ];

//     $validatedData = $request->validate([
//         'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
//         'subject_type' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
//     ], $messages);

//     $subject = SubjectMaster::find($id);

//     if (!$subject) {
//         return response()->json([
//             'status' => 404,
//             'message' => 'Subject not found',
//         ], 404);
//     }

//     $subject->name = $validatedData['name'];
//     $subject->subject_type = $validatedData['subject_type'];
//     $subject->save();

//     return response()->json([
//         'status' => 200,
//         'message' => 'Subject updated successfully',
//     ], 200);
// }




// public function updateTeacherAllotment(Request $request, $classId, $sectionId)
// {
//     // Retrieve the incoming data
//     $subjects = $request->input('subjects'); // Expecting an array of subjects with details
//     $payload = getTokenPayload($request);

//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');
    
//     // Iterate through the subjects
//     foreach ($subjects as $sm_id => $subjectData) {
//         foreach ($subjectData['details'] as $detail) {
//             // If subject_id is null, get the max subject_id from the database and increment by 1
//             if ($detail['subject_id'] === null) {
//                 $maxSubjectId = SubjectAllotment::max('subject_id');
//                 $detail['subject_id'] = $maxSubjectId ? $maxSubjectId + 1 : 1;
//             }

//             // Check if the subject allotment exists based on subject_id, class_id, and section_id
//             $subjectAllotment = SubjectAllotment::where('subject_id', $detail['subject_id'])
//                 ->where('class_id', $classId)
//                 ->where('section_id', $sectionId)
//                 ->first();

//             if ($detail['teacher_id'] === null) {
//                 // If teacher_id is null, delete the record or handle accordingly
//                 if ($subjectAllotment) {
//                     // Check the count of records with the same class_id, section_id, and sm_id
//                     $count = SubjectAllotment::where('class_id', $classId)
//                         ->where('section_id', $sectionId)
//                         ->where('sm_id', $sm_id)
//                         ->count();

//                     if ($count > 1) {
//                         // Delete the record if more than one exists
//                         $subjectAllotment->delete();
//                     } else {
//                         // Set teacher_id to null if this is the only record
//                         $subjectAllotment->update([
//                             'teacher_id' => null,
//                         ]);
//                     }
//                 }
//             } else {
//                 if ($subjectAllotment) {
//                     // Update the existing record
//                     $subjectAllotment->update([
//                         'teacher_id' => $detail['teacher_id'],
//                     ]);
//                 } else {
//                     // Create a new record if it doesn't exist
//                     SubjectAllotment::create([
//                         'subject_id' => $detail['subject_id'],
//                         'class_id' => $classId,
//                         'section_id' => $sectionId,
//                         'teacher_id' => $detail['teacher_id'],
//                         'academic_yr' => $academicYr,
//                         'sm_id' => $sm_id // Use the sm_id from the subjects keys
//                     ]);
//                 }
//             }
//         }
//     }

//     return response()->json([
//         'status' => 'success',
//         'message' => 'Subject allotments updated successfully.',
//     ]);
// }


// Get the Division and subject for the selected class preasign.
// public function getSubjectAllotedForClassDivision($classId)  // change in the API for the selected class and division shows the subjects
// {
//     $subjectAllotments = SubjectAllotment::with(['getDivision', 'getSubject'])
//         ->where('class_id', $classId)
//         ->get();  
//     $subjectAllotmentsCount = SubjectAllotment::with(['getDivision', 'getSubject'])
//         ->where('class_id', $classId)
//         ->count();  
//     return response()->json(
//       ['subjectAllotments'=>$subjectAllotments,
//        'subjectAllotmentsCount' =>$subjectAllotmentsCount      
//       ]
//     );
// }



// Get the Subject List base on the selectd  Division Pre-Asign Subjects.
// public function getPresignSubjectByDivision(Request $request, $sectionId)
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year'); 
    
//     $subjects = SubjectAllotment::with('getSubject')
//     ->where('academic_yr', $academicYr)
//     ->where('section_id', $sectionId)
//     ->groupBy('sm_id', 'subject_id')
//     ->get(); 

//     $count = $subjects->count();
//     return response()->json([
//         'subjects' => $subjects,
//         'count' =>$count
//     ]);
// }

// public function getPresignSubjectByDivision(Request $request,$classId, $sectionId )
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
    
//     $academicYr = $payload->get('academic_year'); 
    
//     $subjects = SubjectAllotment::with('getSubject')
//         ->where('academic_yr', $academicYr)
//         ->where('class_id', $classId) 
//         ->where('section_id', $sectionId)        
//         ->groupBy('sm_id', 'subject_id')
//         ->get(); 

//     $count = $subjects->count();

//     return response()->json([
//         'subjects' => $subjects,
//         'count' => $count
//     ]);
// }



// Get the Subject-Allotment details subject with teachers By Section
// public function getSubjectAllotmentWithTeachersBySection(Request $request, $sectionId)
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     $subjectAllotments = SubjectAllotment::with(['getSubject', 'getTeacher'])
//         ->where('section_id', $sectionId)
//         ->where('academic_yr', $academicYr)
//         ->get()
//         ->groupBy('sm_id');

//     // Create a new array to hold the transformed data
//     $transformedData = [];

//     foreach ($subjectAllotments as $smId => $allotments) {
//         // Get the first record to extract subject details (assuming all records for a sm_id have the same subject)
//         $firstRecord = $allotments->first();
//         $subjectName = $firstRecord->getSubject->name ?? 'Unknown Subject';

//         // Add the sm_id and subject name to the transformed data
//         $transformedData[$smId] = [
//             'subject_name' => $subjectName,
//             'details' => $allotments
//         ];
//     }

//     return response()->json([
//         'status' => 'success',
//         'data' => $transformedData
//     ]);
// }




// public function allotTeacherForSubjects(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'class_id' => 'required|exists:class,class_id',
//             'section_id' => 'required|exists:section,section_id',
//             'teacher_id' => 'required|exists:teacher,teacher_id',
//             'sm_ids' => 'required|array',
//             'sm_ids.*' => 'exists:subject_master,sm_id'
//         ]);

//         if ($validator->fails()) {
//             Log::error('Validation failed for subject allotment.', [
//                 'errors' => $validator->errors()->toArray()
//             ]);
//             return response()->json(['error' => $validator->errors()], 422);
//         }

//         try {
//             Log::info('Starting subject allotment for teacher.', [
//                 'class_id' => $request->input('class_id'),
//                 'section_id' => $request->input('section_id'),
//                 'teacher_id' => $request->input('teacher_id'),
//                 'sm_ids' => $request->input('sm_ids')
//             ]);

//             // Token validation
//             $payload = getTokenPayload($request);
//             if (!$payload) {
//                 Log::warning('Invalid or missing token for subject allotment request.');
//                 return response()->json(['error' => 'Invalid or missing token'], 401);
//             }
//             $academicYr = $payload->get('academic_year');

//             // Retrieve data from the request
//             $classId = $request->input('class_id');
//             $sectionId = $request->input('section_id');
//             $teacherId = $request->input('teacher_id');
//             $smIds = $request->input('sm_ids');

//             // Validate the class_id and section_id again within the try block
//             if (!Classes::find($classId)) {
//                 Log::error('Class ID not found.', ['class_id' => $classId]);
//                 return response()->json(['error' => 'Class not found'], 404);
//             }

//             if (!Division::find($sectionId)) {
//                 Log::error('Section ID not found.', ['section_id' => $sectionId]);
//                 return response()->json(['error' => 'Section not found'], 404);
//             }

//             // Process each sm_id and create new entries
//             foreach ($smIds as $smId) {
//                 $allotment = SubjectAllotment::create([
//                     'sm_id' => $smId,
//                     'class_id' => $classId,
//                     'section_id' => $sectionId,
//                     'teacher_id' => $teacherId,
//                     'academic_yr' => $academicYr
//                 ]);

//                 Log::info('Subject allotment entry created.', [
//                     'subject_allotment_id' => $allotment->subject_id,
//                     'class_id' => $classId,
//                     'section_id' => $sectionId,
//                     'teacher_id' => $teacherId,
//                     'sm_id' => $smId
//                 ]);
//             }

//             Log::info('Subject allotments successfully created.', [
//                 'class_id' => $classId,
//                 'section_id' => $sectionId,
//                 'teacher_id' => $teacherId,
//                 'sm_ids' => $smIds
//             ]);

//             return response()->json(['message' => 'Subject allotments successfully created'], 201);

//         } catch (Exception $e) {
//             Log::error('Error creating subject allotments.', [
//                 'message' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString()
//             ]);

//             return response()->json(['error' => 'Failed to create subject allotments. ' . $e->getMessage()], 500);
//         }
//     }

// }








//Division list base on the selected class.
// public function getDivisionNames(Request $request)
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     $classId = $request->input('class_id');
//     $divisionNames =Division::where('academic_yr', $academicYr)
//         ->where('class_id', $classId)
//         ->select('section_id', 'name')
//         ->distinct()
//         ->get();
//     return response()->json(
//         ['divisions' =>$divisionNames,
          
           
//         ]
//     );
// }

// // Subjects according to the new condition for the  hsc and non hsc subjects.
// public function getSubjectsOfClass($classId)
//     {
//         $class = Classes::find($classId);

//         if (!$class) {
//             return response()->json(['error' => 'Class not found'], 404);
//         }

//         $className = $class->name;

//         if ($className == 11 || $className == 12) {
//             $subjects = $this->getAllSubjectsNotHsc();
//         } else {
//             $subjects = $this->getAllSubjectsOfHsc();
//         }

//         return response()->json($subjects);
//     }

//     private function getAllSubjectsOfHsc()
//     {
//         return SubjectMaster::whereIn('subject_type', ['Compulsory', 'Optional', 'Co-Scholastic_hsc', 'Social'])->get();
//     }

//     private function getAllSubjectsNotHsc()
//     {
//         return SubjectMaster::whereIn('subject_type', ['Scholastic', 'Co-Scholastic', 'Social'])->get();
//     }






// public function allocateTeacherForClass(Request $request)
// {
//     // Validate the request data
//     $validatedData = $request->validate([
//         'teacher_id' => 'required|integer|exists:teacher,teacher_id', // Ensure teacher_id exists in the teachers table
//         'subject_ids' => 'required|array', // Ensure subject_ids is an array
//         'subject_ids.*' => 'integer|exists:subject,subject_id', // Ensure each subject_id exists in the subjects table
//         'academic_yr' => 'required|string', // Ensure academic_yr is present
//     ]);

//     // Extract validated data
//     $teacherId = $validatedData['teacher_id'];
//     $subjectIds = $validatedData['subject_ids'];
//     $academicYr = $validatedData['academic_yr'];

//     // Update records
//     $affectedRows = SubjectAllotment::whereIn('subject_id', $subjectIds)
//         ->where('academic_yr', $academicYr)
//         ->update(['teacher_id' => $teacherId]);

//     // Check if any records were updated
//     if ($affectedRows > 0) {
//         // Return success response
//         return response()->json(['message' => 'Teacher assigned successfully to subjects'], 200);
//     } else {
//         // Return failure response
//         return response()->json(['message' => 'No records updated. Please check your inputs.'], 404);
//     }
// }


// public function allocateTeacherForClass(Request $request)
// {

//     $validatedData = $request->validate([
//         'teacher_assignments' => 'required|array', // Ensure teacher_assignments is an array
//         'teacher_assignments.*.subject_id' => 'required|integer|exists:subject,subject_id', // Ensure each subject_id exists
//         'teacher_assignments.*.teacher_id' => 'required|integer|exists:teacher,teacher_id', // Ensure each teacher_id exists
//         'academic_yr' => 'required|string', // Ensure academic_yr is present
//     ]);

//     // Extract validated data
//     $teacherAssignments = $validatedData['teacher_assignments'];
//     $academicYr = $validatedData['academic_yr'];

//     // Log validated data
//     Log::info('Validated Data:', [
//         'teacher_assignments' => $teacherAssignments,
//         'academic_yr' => $academicYr
//     ]);

//     $affectedRows = 0;

//     foreach ($teacherAssignments as $assignment) {
//         $subjectId = $assignment['subject_id'];
//         $teacherId = $assignment['teacher_id'];

//         $updated = SubjectAllotment::where('subject_id', $subjectId)
//             ->where('academic_yr', $academicYr)
//             ->update(['teacher_id' => $teacherId]);

//         if ($updated) {
//             $affectedRows++;
//         }
//     }

//     if ($affectedRows > 0) {
//         return response()->json(['message' => 'Teacher assigned successfully to subjects'], 200);
//     } else {
//         return response()->json(['message' => 'No records updated. Please check your inputs.'], 404);
//     }
// }






// public function getSubjectAlloted(Request $request)
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }

//     $academicYr = $payload->get('academic_year');    
//     $section_id = $request->query('section_id');
//     $query = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
//         ->where('academic_yr', $academicYr);

//     if ($section_id) {
//         $query->where('section_id', $section_id);
//     }

//     $subjectAllotmentList = $query->orderBy('subject_id', 'DESC')->get();
//     return response()->json($subjectAllotmentList);
// }

// public function getSubjectAlloted(Request $request)
// {
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }

//     $academicYr = $payload->get('academic_year');
    
//     // Retrieve the 'section' query parameter
//     $section = $request->query('section');

//     // Log the value of the section parameter for debugging
//     \Log::info('Section query parameter:', ['section' => $section]);

//     // Initialize the query
//     $query = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
//         ->where('academic_yr', $academicYr);

//     // If 'section' is provided and is not empty or null, add it to the query
//     if (!empty($section)) {
//         $query->where('section_id', $section);
//     }

//     // Fetch and return the results
//     $subjectAllotmentList = $query->orderBy('subject_id', 'DESC')->get();

//     // Log the results for debugging
//     \Log::info('Subject Allotment List:', ['results' => $subjectAllotmentList]);

//     return response()->json($subjectAllotmentList);
// }













// public function getSubjectAlloted(Request $request){

//     {
//         $payload = getTokenPayload($request);
//         if (!$payload) {
//             return response()->json(['error' => 'Invalid or missing token'], 401);
//         }
//         $academicYr = $payload->get('academic_year');

//       $subjectAllotmentList = SubjectAllotment::with('getClass','getDivision','getTeacher','getSubject')
//                            ->where('academic_yr',$academicYr)
//                            ->orderBy('subject_id','DESC')
//                            ->get(); 
//       return response()->json($subjectAllotmentList);

// }




// public function storeSubjectAllotment(Request $request)
// {
//     $request->validate([
//         'class_id' => 'required|exists:class,class_id',
//         'divisions' => 'required|array',
//         'subjects' => 'required|array',
//         'teacher_id' => 'required|exists:teacher,teacher_id',
//         'divisions.*' => 'exists:section,section_id',
//         'subjects.*' => 'exists:subject_master,sm_id',
//     ]);

//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');

//     $classId = $request->input('class_id');
//     $divisions = $request->input('divisions');
//     $subjects = $request->input('subjects');
//     $teacherId = $request->input('teacher_id');

//     $createdAllotments = [];
//     $existingAllotments = [];
//     $errors = [];

//     foreach ($divisions as $division) {
//         foreach ($subjects as $subjectId) {
//             $existingAllotment = SubjectAllotment::where('sm_id', $subjectId)
//                 ->where('class_id', $classId)
//                 ->where('section_id', $division)
//                 ->where('academic_yr', $academicYr)
//                 ->first();

//             if ($existingAllotment) {
//                 $existingAllotments[] = [
//                     'subject_id' => $subjectId,
//                     'section_id' => $division,
//                 ];
//                 continue; 
//             }

//             $subjectAllotment = SubjectAllotment::create([
//                 'sm_id' => $subjectId,
//                 'class_id' => $classId,
//                 'section_id' => $division,
//                 'teacher_id' => $teacherId,
//                 'academic_yr' => $academicYr,
//             ]);

//             if ($subjectAllotment) {
//                 $createdAllotments[] = $subjectAllotment;
//             } else {
//                 $errors[] = [
//                     'subject_id' => $subjectId,
//                     'section_id' => $division,
//                     'error' => 'Failed to create subject allotment'
//                 ];
//             }
//         }
//     }

//     if (count($errors) > 0) {
//         return response()->json([
//             'status' => 400,
//             'message' => 'Some subject allotments could not be processed',
//             'errors' => $errors,
//         ], 400);
//     }

//     return response()->json([
//         'status' => 201,
//         'message' => 'Subject allotments processed successfully',
//         'data' => [
//             'created' => $createdAllotments,
//             'existing' => $existingAllotments,
//         ],
//     ], 201);
// }


// public function storeStaff(Request $request)
// {
//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             'email' => 'required|string|email|max:255|unique:teacher,email',
//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
//             'teacher_image_name' => 'nullable|string|max:255',
//             'role' => 'required|string|max:255',
//         ], $messages);

//         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
//             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
//         }

//         // Create Teacher record
//         $teacher = new Teacher();
//         $teacher->fill($validatedData);
//         $teacher->IsDelete = 'N';
        
//         if (!$teacher->save()) {
//             return response()->json([
//                 'message' => 'Failed to create teacher',
//             ], 500);
//         }

//         // Create User record
//         $user = User::create([
//             'email' => $validatedData['email'],
//             'name' => $validatedData['name'],
//             'password' => Hash::make('arnolds'),
//             'reg_id' => $teacher->teacher_id,
//             'role_id' => $validatedData['role'],
//             'IsDelete' => 'N',
//         ]);

//         if (!$user) {
//             // Rollback by deleting the teacher record if user creation fails
//             $teacher->delete();
//             return response()->json([
//                 'message' => 'Failed to create user',
//             ], 500);
//         }

//         return response()->json([
//             'message' => 'Teacher and user created successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 201);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             // Rollback by deleting the teacher record if an unexpected error occurs
//             $teacher->delete();
//         }
//         return response()->json([
//             'message' => 'An error occurred while creating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

// public function storeStaff(Request $request)
// {
//     try {
//         $messages = [
//             'name.required' => 'The name field is mandatory.',
//             'birthday.required' => 'The birthday field is required.',
//             'date_of_joining.required' => 'The date of joining is required.',
//             'email.required' => 'The email field is required.',
//             'email.email' => 'The email must be a valid email address.',
//             'email.unique' => 'The email has already been taken.',
//             'phone.required' => 'The phone number is required.',
//             'phone.max' => 'The phone number cannot exceed 15 characters.',
//             'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
//             'teacher_image_name.string' => 'The file must be an image.',
//             'role.required' => 'The role field is required.',
//         ];

//         $validatedData = $request->validate([
//             'employee_id' => 'nullable|string|max:255',
//             'name' => 'required|string|max:255',
//             'birthday' => 'required|date',
//             'date_of_joining' => 'required|date',
//             'sex' => 'required|string|max:10',
//             'religion' => 'nullable|string|max:255',
//             'blood_group' => 'nullable|string|max:10',
//             'address' => 'required|string|max:255',
//             'phone' => 'required|string|max:15',
//             'email' => 'required|string|email|max:255|unique:teacher,email',
//             'designation' => 'nullable|string|max:255',
//             'academic_qual' => 'nullable|array',
//             'academic_qual.*' => 'nullable|string|max:255',
//             'professional_qual' => 'nullable|string|max:255',
//             'special_sub' => 'nullable|string|max:255',
//             'trained' => 'nullable|string|max:255',
//             'experience' => 'nullable|string|max:255',
//             'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
//             'teacher_image_name' => 'nullable|string', // Base64 string
//             'role' => 'required|string|max:255',
//         ], $messages);

//         if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
//             $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
//         }

//         // Handle base64 image
//         if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
//             $imageData = $request->input('teacher_image_name');
//             if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
//                 $imageData = substr($imageData, strpos($imageData, ',') + 1);
//                 $type = strtolower($type[1]); // jpg, png, gif
//                 if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
//                     throw new \Exception('Invalid image type');
//                 }
//                 $imageData = base64_decode($imageData);
//                 if ($imageData === false) {
//                     throw new \Exception('Base64 decode failed');
//                 }
//                 $filename = 'teacher_' . time() . '.' . $type;
//                 $filePath = storage_path('app/public/teacher_images/'.$filename);
//                 file_put_contents($filePath, $imageData);
//                 $validatedData['teacher_image_name'] = $filename;
//             } else {
//                 throw new \Exception('Invalid image data');
//             }
//         }

//         // Create Teacher record
//         $teacher = new Teacher();
//         $teacher->fill($validatedData);
//         $teacher->IsDelete = 'N';

//         if (!$teacher->save()) {
//             return response()->json([
//                 'message' => 'Failed to create teacher',
//             ], 500);
//         }

//         // Create User record
//         $user = User::create([
//             'email' => $validatedData['email'],
//             'name' => $validatedData['name'],
//             'password' => Hash::make('arnolds'),
//             'reg_id' => $teacher->teacher_id,
//             'role_id' => $validatedData['role'],
//             'IsDelete' => 'N',
//         ]);

//         if (!$user) {
//             // Rollback by deleting the teacher record if user creation fails
//             $teacher->delete();
//             return response()->json([
//                 'message' => 'Failed to create user',
//             ], 500);
//         }

//         return response()->json([
//             'message' => 'Teacher and user created successfully!',
//             'teacher' => $teacher,
//             'user' => $user,
//         ], 201);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         // Handle unexpected errors
//         if (isset($teacher) && $teacher->exists) {
//             // Rollback by deleting the teacher record if an unexpected error occurs
//             $teacher->delete();
//         }
//         return response()->json([
//             'message' => 'An error occurred while creating the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }





//      public function updateStudentAndParent(Request $request, $studentId)
// {
//     // Validate the incoming request for all fields
//     $validatedData = $request->validate([
//         // Student model fields
//         'first_name' => 'required|string|max:100',
//         'mid_name' => 'nullable|string|max:100',
//         'last_name' => 'required|string|max:100',
//         'house' => 'nullable|string|max:100',
//         'student_name' => 'required|string|max:100',
//         'dob' => 'required|date',
//         'admission_date' => 'required|date',
//         'stud_id_no' => 'nullable|string|max:25',
//         'stu_aadhaar_no' => 'required|string|max:14',
//         'gender' => 'required|string',
//         'mother_tongue' => 'required|string|max:20',
//         'birth_place' => 'nullable|string|max:50',
//         'admission_class' => 'required|string|max:255',
//         'city' => 'required|string|max:100',
//         'state' => 'required|string|max:100',
//         'roll_no' => 'nullable|string|max:11',
//         'class_id' => 'required|integer',
//         'section_id' => 'required|integer',
//         'religion' => 'required|string|max:255',
//         'caste' => 'nullable|string|max:100',
//         'subcaste' => 'required|string|max:255',
//         'vehicle_no' => 'nullable|string|max:13',
//         'emergency_name' => 'nullable|string|max:100',
//         'emergency_contact' => 'nullable|string|max:11',
//         'emergency_add' => 'nullable|string|max:200',
//         'height' => 'nullable|numeric|max:4.1',
//         'weight' => 'nullable|numeric|max:4.1',
//         'allergies' => 'nullable|string|max:200',
//         'nationality' => 'required|string|max:100',
//         'pincode' => 'nullable|string|max:11',
//         'image_name' => 'nullable|string',

//         // Parent model fields
//         'father_name' => 'required|string|max:100',
//         'father_occupation' => 'nullable|string|max:100',
//         'f_office_add' => 'nullable|string|max:200',
//         'f_office_tel' => 'nullable|string|max:11',
//         'f_mobile' => 'required|string|max:10',
//         'f_email' => 'required|string|max:50',
//         'father_adhar_card' => 'required|string|max:14',
//         'mother_name' => 'required|string|max:100',
//         'mother_occupation' => 'nullable|string|max:100',
//         'm_office_add' => 'nullable|string|max:200',
//         'm_office_tel' => 'nullable|string|max:11',
//         'm_mobile' => 'required|string|max:10',
//         'm_emailid' => 'required|string|max:50',
//         'mother_adhar_card' => 'required|string|max:14',

//         // Preferences for SMS and email as username
//         'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//         'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
//     ]);


//       // Convert relevant fields to uppercase
//       $fieldsToUpper = [
//         'first_name', 'mid_name', 'last_name', 'house', 'emergency_name', 
//         'emergency_contact', 'nationality', 'city', 'state', 'birth_place', 
//         'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste'
//     ];

//     foreach ($fieldsToUpper as $field) {
//         if (isset($validatedData[$field])) {
//             $validatedData[$field] = strtoupper(trim($validatedData[$field]));
//         }
//     }

//     // Additional fields for parent model that need to be converted to uppercase
//     $parentFieldsToUpper = [
//         'father_name', 'mother_name', 'f_blood_group', 'm_blood_group', 'student_blood_group'
//     ];

//     foreach ($parentFieldsToUpper as $field) {
//         if (isset($validatedData[$field])) {
//             $validatedData[$field] = strtoupper(trim($validatedData[$field]));
//         }
//     }
  


//     // Retrieve the token payload
//     $payload = getTokenPayload($request);
//     $academicYr = $payload->get('academic_year');

//     // Find the student by ID
//     $student = Student::find($studentId);
//     if (!$student) {
//         return response()->json(['error' => 'Student not found'], 404);
//     }

//     // Check if specified fields have changed  str to upper case and trim the space   Include the parent_id and 
//     $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
//     $isModified = false;

//     foreach ($fieldsToCheck as $field) {
//         if ($student->$field != $validatedData[$field]) {
//             $isModified = true;
//             break;
//         }
//     }

//     // If any of the fields are modified, set 'is_modify' to 'Y'
//     if ($isModified) {
//         $validatedData['is_modify'] = 'Y';
//     }

//     // Handle student image if provided
//     if ($request->has('student_image')) {
//         $base64Image = $request->input('student_image');
//         $imageParts = explode(';', $base64Image);
//         $imageType = explode(':', $imageParts[0])[1];
//         $imageExtension = str_replace('image/', '', $imageType);
//         $image = str_replace('data:image/' . $imageExtension . ';base64,', '', $base64Image);
//         $image = str_replace(' ', '+', $image);
//         $imageName = $studentId . '.' . $imageExtension;
//         $imagePath = public_path('uploads/student_image');

//         if (!file_exists($imagePath)) {
//             mkdir($imagePath, 0755, true);
//         }

//         file_put_contents($imagePath . '/' . $imageName, base64_decode($image));
//         $validatedData['image_name'] = $imageName;
//     }

//     // Include academic year in the update data
//     $validatedData['academic_yr'] = $academicYr;

//     // Update student information
//     $student->update($validatedData);

//     // Handle parent details if provided
//     $parent = Parents::find($student->parent_id);
//     if ($parent) {
//         $parentData = $request->only([
//             'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 'f_mobile', 'f_email',
//             'mother_name', 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid',
//             'parent_adhar_no', 'm_adhar_no', 'father_adhar_card', 'mother_adhar_card',
//         ]);

//         // Update SMS contact preference
//         $contactDetails = ContactDetails::where('id', $student->parent_id)->first();
//         if ($request->input('SetToReceiveSMS') == 'Father') {
//             $contactDetails->update(['phone_no' => $parent->f_mobile]);
//         } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//             $contactDetails->update(['phone_no' => $parent->m_mobile]);
//         }

//         // Update email ID as username preference and call the external API if it has changed
//         $user = UserMaster::where('reg_id', $student->parent_id)->first();
//         $apiData = [
//             'user_id' => '',
//             'short_name' => 'SACS',
//         ];

//         $oldEmailPreference = $user->user_id; // Store old email preference for comparison


//         // if ($request->input('SetEmailIDAsUsername') == 'Father') {
//         //     $apiData['user_id'] = $parent->f_email;
//         //     $user->update(['user_id' => $parent->f_email]);
//         // } elseif ($request->input('SetEmailIDAsUsername') == 'Mother') {
//         //     $apiData['user_id'] = $parent->m_emailid;
//         //     $user->update(['user_id' => $parent->m_emailid]);
//         // } elseif ($request->input('SetEmailIDAsUsername') == 'FatherMob') {
//         //     $apiData['user_id'] = $parent->f_mobile; 
//         //     $user->update(['user_id' => $parent->f_mobile]);
//         // } elseif ($request->input('SetEmailIDAsUsername') == 'MotherMob') {
//         //     $apiData['user_id'] = $parent->m_mobile; 
//         //     $user->update(['user_id' => $parent->m_mobile]);
//         // }
        

//         // Check if the email preference changed
//         // if ($oldEmailPreference != $apiData['user_id']) {
//         //     // Call the external API only if the email preference has changed
//         //     $response = Http::post('http://aceventura.in/demo/evolvuUserService/user_create_new', $apiData);

//         //     // Handle the API response if needed
//         //     if ($response->successful()) {
//         //         // You can log the response or handle further logic here if needed
//         //     } else {
//         //         return response()->json(['error' => 'Failed to call the API.'], 500);
//         //     }
//         // }

//         $parent->update($parentData);
//     }

//     return response()->json([
//         "status" => "success",
//         "message" => "Student and parent information updated successfully",
//         "data" => $student
//     ]);
// }



// public function updateStudentAndParent(Request $request, $studentId)
// {
//     try {
//         // Log the start of the request
//         Log::info("Starting updateStudentAndParent for student ID: {$studentId}");

//         // Validate the incoming request for all fields
//         // $validatedData = $request->validate([
//         //     // Student model fields
//         //     'first_name' => 'required|string|max:100',
//         //     'mid_name' => 'nullable|string|max:100',
//         //     'last_name' => 'required|string|max:100',
//         //     'house' => 'nullable|string|max:100',
//         //     'student_name' => 'required|string|max:100',
//         //     'dob' => 'required|date',
//         //     'admission_date' => 'required|date',
//         //     'stud_id_no' => 'nullable|string|max:25',
//         //     'stu_aadhaar_no' => 'required|string|max:14',
//         //     'gender' => 'required|string',
//         //     'mother_tongue' => 'required|string|max:20',
//         //     'birth_place' => 'nullable|string|max:50',
//         //     'admission_class' => 'required|string|max:255',
//         //     'city' => 'required|string|max:100',
//         //     'state' => 'required|string|max:100',
//         //     'roll_no' => 'nullable|string|max:11',
//         //     'class_id' => 'required|integer',
//         //     'section_id' => 'required|integer',
//         //     'religion' => 'required|string|max:255',
//         //     'caste' => 'nullable|string|max:100',
//         //     'subcaste' => 'required|string|max:255',
//         //     'vehicle_no' => 'nullable|string|max:13',
//         //     'emergency_name' => 'nullable|string|max:100',
//         //     'emergency_contact' => 'nullable|string|max:11',
//         //     'emergency_add' => 'nullable|string|max:200',
//         //     'height' => 'nullable|numeric|max:4.1',
//         //     'weight' => 'nullable|numeric|max:4.1',
//         //     'allergies' => 'nullable|string|max:200',
//         //     'nationality' => 'required|string|max:100',
//         //     'pincode' => 'nullable|string|max:11',
//         //     'image_name' => 'nullable|string',

//         //     // Parent model fields
//         //     'father_name' => 'required|string|max:100',
//         //     'father_occupation' => 'nullable|string|max:100',
//         //     'f_office_add' => 'nullable|string|max:200',
//         //     'f_office_tel' => 'nullable|string|max:11',
//         //     'f_mobile' => 'required|string|max:10',
//         //     'f_email' => 'required|string|max:50',
//         //     'father_adhar_card' => 'required|string|max:14',
//         //     'mother_name' => 'required|string|max:100',
//         //     'mother_occupation' => 'nullable|string|max:100',
//         //     'm_office_add' => 'nullable|string|max:200',
//         //     'm_office_tel' => 'nullable|string|max:11',
//         //     'm_mobile' => 'required|string|max:10',
//         //     'm_emailid' => 'required|string|max:50',
//         //     'mother_adhar_card' => 'required|string|max:14',

//         //     // Preferences for SMS and email as username
//         //     'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//         //     'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
//         // ]);
//         $validatedData = $request->validate([
//             // Student model fields
//             'first_name' => 'nullable|string|max:100',
//             'mid_name' => 'nullable|string|max:100',
//             'last_name' => 'nullable|string|max:100',
//             'house' => 'nullable|string|max:100',
//             'student_name' => 'nullable|string|max:100',
//             'dob' => 'nullable|date',
//             'admission_date' => 'nullable|date',
//             'stud_id_no' => 'nullable|string|max:25',
//             'stu_aadhaar_no' => 'nullable|string|max:14',
//             'gender' => 'nullable|string',
//             'mother_tongue' => 'nullable|string|max:20',
//             'birth_place' => 'nullable|string|max:50',
//             'admission_class' => 'nullable|string|max:255',
//             'city' => 'nullable|string|max:100',
//             'state' => 'nullable|string|max:100',
//             'roll_no' => 'nullable|string|max:11',
//             'class_id' => 'nullable|integer',
//             'section_id' => 'nullable|integer',
//             'religion' => 'nullable|string|max:255',
//             'caste' => 'nullable|string|max:100',
//             'subcaste' => 'nullable|string|max:255',
//             'vehicle_no' => 'nullable|string|max:13',
//             'emergency_name' => 'nullable|string|max:100',
//             'emergency_contact' => 'nullable|string|max:11',
//             'emergency_add' => 'nullable|string|max:200',
//             'height' => 'nullable|numeric|max:4.1',
//             'weight' => 'nullable|numeric|max:4.1',
//             'allergies' => 'nullable|string|max:200',
//             'nationality' => 'nullable|string|max:100',
//             'pincode' => 'nullable|string|max:11',
//             'image_name' => 'nullable|string',
        
//             // Parent model fields
//             'father_name' => 'nullable|string|max:100',
//             'father_occupation' => 'nullable|string|max:100',
//             'f_office_add' => 'nullable|string|max:200',
//             'f_office_tel' => 'nullable|string|max:11',
//             'f_mobile' => 'nullable|string|max:10',
//             'f_email' => 'nullable|string|max:50',
//             'father_adhar_card' => 'nullable|string|max:14',
//             'mother_name' => 'nullable|string|max:100',
//             'mother_occupation' => 'nullable|string|max:100',
//             'm_office_add' => 'nullable|string|max:200',
//             'm_office_tel' => 'nullable|string|max:11',
//             'm_mobile' => 'nullable|string|max:10',
//             'm_emailid' => 'nullable|string|max:50',
//             'mother_adhar_card' => 'nullable|string|max:14',
        
//             // Preferences for SMS and email as username
//             'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//             'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
//         ]);
        

//         Log::info("Validation passed for student ID: {$studentId}");

//         // Convert relevant fields to uppercase
//         $fieldsToUpper = [
//             'first_name', 'mid_name', 'last_name', 'house', 'emergency_name', 
//             'emergency_contact', 'nationality', 'city', 'state', 'birth_place', 
//             'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste'
//         ];

//         foreach ($fieldsToUpper as $field) {
//             if (isset($validatedData[$field])) {
//                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
//             }
//         }

//         // Additional fields for parent model that need to be converted to uppercase
//         $parentFieldsToUpper = [
//             'father_name', 'mother_name', 'f_blood_group', 'm_blood_group', 'student_blood_group'
//         ];

//         foreach ($parentFieldsToUpper as $field) {
//             if (isset($validatedData[$field])) {
//                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
//             }
//         }

//         // Retrieve the token payload
//         $payload = getTokenPayload($request);
//         $academicYr = $payload->get('academic_year');

//         Log::info("Academic year: {$academicYr} for student ID: {$studentId}");

//         // Find the student by ID
//         $student = Student::find($studentId);
//         if (!$student) {
//             Log::error("Student not found: ID {$studentId}");
//             return response()->json(['error' => 'Student not found'], 404);
//         }

//         // Check if specified fields have changed
//         $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
//         $isModified = false;

//         foreach ($fieldsToCheck as $field) {
//             if ($student->$field != $validatedData[$field]) {
//                 $isModified = true;
//                 break;
//             }
//         }

//         // If any of the fields are modified, set 'is_modify' to 'Y'
//         if ($isModified) {
//             $validatedData['is_modify'] = 'Y';
//         }

//         // Handle student image if provided
//         if ($request->has('student_image')) {
//             $base64Image = $request->input('student_image');
//             $imageParts = explode(';', $base64Image);
//             $imageType = explode(':', $imageParts[0])[1];
//             $imageExtension = str_replace('image/', '', $imageType);
//             $image = str_replace('data:image/' . $imageExtension . ';base64,', '', $base64Image);
//             $image = str_replace(' ', '+', $image);
//             $imageName = $studentId . '.' . $imageExtension;
//             $imagePath = public_path('uploads/student_image');

//             if (!file_exists($imagePath)) {
//                 mkdir($imagePath, 0755, true);
//             }

//             file_put_contents($imagePath . '/' . $imageName, base64_decode($image));
//             $validatedData['image_name'] = $imageName;
//             Log::info("Image uploaded for student ID: {$studentId}");
//         }

//         // Include academic year in the update data
//         $validatedData['academic_yr'] = $academicYr;

//         // Update student information
//         $student->update($validatedData);
//         Log::info("Student information updated for student ID: {$studentId}");

//         // Handle parent details if provided
//         $parent = Parents::find($student->parent_id);
//         if ($parent) {
//             $parentData = $request->only([
//                 'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 'f_mobile', 'f_email',
//                 'mother_name', 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid',
//                 'parent_adhar_no', 'm_adhar_no', 'father_adhar_card', 'mother_adhar_card',
//             ]);

//             // Update SMS contact preference
//             // $contactDetails = ContactDetails::where('id', $student->parent_id)->first();
//             // if ($request->input('SetToReceiveSMS') == 'Father') {
//             //     $contactDetails->update(['phone_no' => $parent->f_mobile]);
//             // } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//             //     $contactDetails->update(['phone_no' => $parent->m_mobile]);
//             // }

//             $contactDetails = ContactDetails::find($student->parent_id);

//           // Determine the phone number based on the 'SetToReceiveSMS' input
//             if ($request->input('SetToReceiveSMS') == 'Father') {
//                 $phoneNo = $parent->f_mobile;
//             } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//                 $phoneNo = $parent->m_mobile;
//             } else {
//                 $phoneNo = null; // Handle invalid selection
//             }

//             // Check if a record already exists with parent_id as the id
//             $contactDetails = ContactDetails::find($student->parent_id);

//             if ($contactDetails) {
//                 // If the record exists, update the contact details
//                 $contactDetails->update([
//                     'phone_no' => $phoneNo,
//                     'alternate_phone_no' => $parent->f_mobile, // Assuming alternate phone is Father's mobile number
//                     'email_id' => $parent->f_email, // Father's email
//                     'm_emailid' => $parent->m_emailid, // Mother's email
//                     'sms_consent' => 'y', // Store consent for SMS
//                 ]);
//             } else {
//                 // If the record doesn't exist, create a new one with parent_id as the id
//                 DB::insert('INSERT INTO contact_details (id, phone_no, alternate_phone_no, email_id, m_emailid, sms_consent) VALUES (?, ?, ?, ?, ?, ?)', [
//                     $student->parent_id,
//                     $phoneNo,
//                     $parent->f_mobile,
//                     $parent->f_email,
//                     $parent->m_emailid,
//                     'y', // sms_consent
//                 ]);
//             }


            


//             // Update email ID as username preference
//             $user = UserMaster::where('reg_id', $student->parent_id)->first();
//             $apiData = [
//                 'user_id' => '',
//                 'short_name' => 'SACS',
//             ];

//             $oldEmailPreference = $user->user_id; // Store old email preference for comparison

//             // Check if the email preference changed
//             if ($oldEmailPreference != $apiData['user_id']) {
//                 // Call the external API only if the email preference has changed
//                 $response = Http::post('http://aceventura.in/demo/evolvuUserService/user_create_new', $apiData);
//                 if ($response->successful()) {
//                     Log::info("API call successful for updating user ID: {$user->user_id}");
//                 } else {
//                     Log::error("API call failed for user ID: {$user->user_id}");
//                     return response()->json(['error' => 'Failed to call the API.'], 500);
//                 }
//             }

//             $parent->update($parentData);
//             Log::info("Parent information updated for student ID: {$studentId}");
//         }

//         return response()->json([
//             "status" => "success",
//             "message" => "Student and parent information updated successfully",
//             "data" => $student
//         ]);

//     } catch (\Exception $e) {
//         Log::error("Error updating student and parent information: " . $e->getMessage());
//         return response()->json([
//             'error' => 'Failed to update student and parent information.',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }

// public function updateStudentAndParent(Request $request, $studentId)
// {
//     try {
//         // Log the start of the request
//         Log::info("Starting updateStudentAndParent for student ID: {$studentId}");

//         // Validate the incoming request for all fields
//         $validatedData = $request->validate([
//             // Student model fields
//             'first_name' => 'nullable|string|max:100',
//             'mid_name' => 'nullable|string|max:100',
//             'last_name' => 'nullable|string|max:100',
//             'house' => 'nullable|string|max:100',
//             'student_name' => 'nullable|string|max:100',
//             'dob' => 'nullable|date',
//             'admission_date' => 'nullable|date',
//             'stud_id_no' => 'nullable|string|max:25',
//             'stu_aadhaar_no' => 'nullable|string|max:14',
//             'gender' => 'nullable|string',
//             'mother_tongue' => 'nullable|string|max:20',
//             'birth_place' => 'nullable|string|max:50',
//             'admission_class' => 'nullable|string|max:255',
//             'city' => 'nullable|string|max:100',
//             'state' => 'nullable|string|max:100',
//             'roll_no' => 'nullable|string|max:11',
//             'class_id' => 'nullable|integer',
//             'section_id' => 'nullable|integer',
//             'religion' => 'nullable|string|max:255',
//             'caste' => 'nullable|string|max:100',
//             'subcaste' => 'nullable|string|max:255',
//             'vehicle_no' => 'nullable|string|max:13',
//             'emergency_name' => 'nullable|string|max:100',
//             'emergency_contact' => 'nullable|string|max:11',
//             'emergency_add' => 'nullable|string|max:200',
//             'height' => 'nullable|numeric|max:4.1',
//             'weight' => 'nullable|numeric|max:4.1',
//             'allergies' => 'nullable|string|max:200',
//             'nationality' => 'nullable|string|max:100',
//             'pincode' => 'nullable|string|max:11',
//             'image_name' => 'nullable|string',

//             // Parent model fields
//             'father_name' => 'nullable|string|max:100',
//             'father_occupation' => 'nullable|string|max:100',
//             'f_office_add' => 'nullable|string|max:200',
//             'f_office_tel' => 'nullable|string|max:11',
//             'f_mobile' => 'nullable|string|max:10',
//             'f_email' => 'nullable|string|max:50',
//             'father_adhar_card' => 'nullable|string|max:14',
//             'mother_name' => 'nullable|string|max:100',
//             'mother_occupation' => 'nullable|string|max:100',
//             'm_office_add' => 'nullable|string|max:200',
//             'm_office_tel' => 'nullable|string|max:11',
//             'm_mobile' => 'nullable|string|max:10',
//             'm_emailid' => 'nullable|string|max:50',
//             'mother_adhar_card' => 'nullable|string|max:14',

//             // Preferences for SMS and email as username
//             'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
//             'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
//         ]);

//         Log::info("Validation passed for student ID: {$studentId}");

//         // Convert relevant fields to uppercase
//         $fieldsToUpper = [
//             'first_name', 'mid_name', 'last_name', 'house', 'emergency_name',
//             'emergency_contact', 'nationality', 'city', 'state', 'birth_place',
//             'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste'
//         ];

//         foreach ($fieldsToUpper as $field) {
//             if (isset($validatedData[$field])) {
//                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
//             }
//         }

//         // Additional fields for parent model that need to be converted to uppercase
//         $parentFieldsToUpper = [
//             'father_name', 'mother_name'
//         ];

//         foreach ($parentFieldsToUpper as $field) {
//             if (isset($validatedData[$field])) {
//                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
//             }
//         }

//         // Retrieve the token payload
//         $payload = getTokenPayload($request);
//         $academicYr = $payload->get('academic_year');

//         Log::info("Academic year: {$academicYr} for student ID: {$studentId}");

//         // Find the student by ID
//         $student = Student::find($studentId);
//         if (!$student) {
//             Log::error("Student not found: ID {$studentId}");
//             return response()->json(['error' => 'Student not found'], 404);
//         }

//         // Check if specified fields have changed
//         $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
//         $isModified = false;

//         foreach ($fieldsToCheck as $field) {
//             if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
//                 $isModified = true;
//                 break;
//             }
//         }

//         // If any of the fields are modified, set 'is_modify' to 'Y'
//         if ($isModified) {
//             $validatedData['is_modify'] = 'Y';
//         }

//         // Handle student image if provided
//         if ($request->hasFile('student_image')) {
//             $image = $request->file('student_image');
//             $imageName = $studentId . '.' . $image->getClientOriginalExtension();
//             $imagePath = public_path('uploads/student_image');

//             if (!file_exists($imagePath)) {
//                 mkdir($imagePath, 0755, true);
//             }

//             $image->move($imagePath, $imageName);
//             $validatedData['image_name'] = $imageName;
//             Log::info("Image uploaded for student ID: {$studentId}");
//         }

//         // Include academic year in the update data
//         $validatedData['academic_yr'] = $academicYr;

//         // Update student information
//         $student->update($validatedData);
//         Log::info("Student information updated for student ID: {$studentId}");

//         // Handle parent details if provided
//         $parent = Parents::find($student->parent_id);
//         if ($parent) {
//             $parentData = $request->only([
//                 'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 'f_mobile', 'f_email',
//                 'mother_name', 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid',
//                 'father_adhar_card', 'mother_adhar_card',
//             ]);

//             // Determine the phone number based on the 'SetToReceiveSMS' input
//             if ($request->input('SetToReceiveSMS') == 'Father') {
//                 $phoneNo = $parent->f_mobile;
//             } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
//                 $phoneNo = $parent->m_mobile;
//             } else {
//                 $phoneNo = null; // Handle invalid selection
//             }

//             // Check if a record already exists with parent_id as the id
//             $contactDetails = ContactDetails::find($student->parent_id);

//             if ($contactDetails) {
//                 // If the record exists, update the contact details
//                 $contactDetails->update([
//                     'phone_no' => $phoneNo,
//                     'alternate_phone_no' => $parent->f_mobile, // Assuming alternate phone is Father's mobile number
//                     'email_id' => $parent->f_email, // Father's email
//                     'm_emailid' => $parent->m_emailid, // Mother's email
//                     'sms_consent' => 'y', // Store consent for SMS
//                 ]);
//                 Log::info("Contact details updated for parent ID: {$student->parent_id}");
//             } else {
//                 // Create a new record if it does not exist
//                 ContactDetails::create([
//                     'id' => $student->parent_id,
//                     'phone_no' => $phoneNo,
//                     'alternate_phone_no' => $parent->f_mobile,
//                     'email_id' => $parent->f_email,
//                     'm_emailid' => $parent->m_emailid,
//                     'sms_consent' => 'y',
//                 ]);
//                 Log::info("New contact details created for parent ID: {$student->parent_id}");
//             }

//             // Update parent information
//             $parent->update($parentData);
//             Log::info("Parent information updated for parent ID: {$student->parent_id}");
//         } else {
//             Log::error("Parent not found: ID {$student->parent_id}");
//             return response()->json(['error' => 'Parent not found'], 404);
//         }

//         // Return success response
//         return response()->json(['message' => 'Student and parent information updated successfully'], 200);

//     } catch (\Exception $e) {
//         // Log the error message
//         Log::error('Error in updateStudentAndParent: ' . $e->getMessage());
//         return response()->json(['error' => 'An error occurred while updating student and parent information'], 500);
//     }
// }