<?php

namespace App\Http\Controllers;

use Exception;
use Validator;
use App\Models\User;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Division;
use App\Mail\WelcomeEmail;
use App\Models\Attendence;
use App\Models\UserMaster;
use App\Models\StaffNotice;
use Illuminate\Http\Request;
use App\Models\SubjectMaster;
use Illuminate\Support\Carbon;
use App\Models\BankAccountName;
use Illuminate\Validation\Rule;
use App\Models\SubjectAllotment;
use Illuminate\Http\JsonResponse;
use App\Mail\TeacherBirthdayEmail;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Support\Facades\Auth;


class MastersController extends Controller
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
            'father_spouse_name' => 'nullable|string|max:255',
            'birthday' => 'required|date',
            'date_of_joining' => 'required|date',
            'sex' => 'required|string|max:10',
            'religion' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:teacher,email,' . $id . ',teacher_id',
            'designation' => 'nullable|string|max:255',
            'academic_qual' => 'nullable|array',
            'academic_qual.*' => 'nullable|string|max:255',
            'professional_qual' => 'nullable|string|max:255',
            'special_sub' => 'nullable|string|max:255',
            'trained' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no,' . $id . ',teacher_id',
            'teacher_image_name' => 'nullable|string', // Base64 string
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'isDelete' => 'nullable|string|in:Y,N',
        ], $messages);

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
                $filePath = storage_path('app/public/teacher_images/' . $filename);

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

        $teacher = Teacher::findOrFail($id);
        $teacher->fill($validatedData);

        if (!$teacher->save()) {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to update teacher',
            ], 500);
        }

        $user = User::where('reg_id', $id)->first();
        if ($user) {
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];

            if (!$user->save()) {
                // Rollback by reverting the teacher record if user update fails
                $teacher->delete();
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
            $teacher->delete();
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
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

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
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');    
    $sectionId = $request->query('section_id');    
    $query = Student::where('academic_yr', $academicYr)
        ->whereNotNull('parent_id');
    
    if ($sectionId) {
        $query->where('section_id', $sectionId);
    }

    $students = $query->get();    
    $studentCount = $students->count();    
    
    return response()->json([
        'count' => $studentCount,
        'students' => $students,
      
    ]);
}

//  get the student list by there id  with the parent details 
public function getStudentById($studentId)
{
    $student = Student::with('parents')->find($studentId);
    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }    
    return response()->json($student);
}

public function deleteStudent($studentId)
    {
        $student = Student::find($studentId);        
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }
        $student->isDelete = 'Y';
        $student->save();        
        return response()->json(['message' => 'Student deleted successfully']);
    }


    public function inAvateStudent($studentId)
    {
        $student = Student::find($studentId);        
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }
        $student->isActive = 'N';
        $student->save();        
        return response()->json(['message' => 'Student deactivated successfully']);
    }


    public function updateStudentAndParent(Request $request, $studentId)
{
    // Validate the incoming request
    $request->validate([
        'student_name' => 'sometimes|string|max:255',
        'dob' => 'sometimes|date',
        'gender' => 'sometimes|string|max:10',
        'admission_date' => 'sometimes|date',
        'stud_id_no' => 'sometimes|string|max:255',
        'mother_tongue' => 'sometimes|string|max:50',
        'birth_place' => 'sometimes|string|max:255',
        'admission_class' => 'sometimes|string|max:50',
        'roll_no' => 'sometimes|string|max:50',
        'class_id' => 'sometimes|integer|exists:classes,class_id',
        'section_id' => 'sometimes|integer|exists:sections,section_id',
        'fees_paid' => 'sometimes|numeric',
        'blood_group' => 'sometimes|string|max:10',
        'religion' => 'sometimes|string|max:50',
        'caste' => 'sometimes|string|max:50',
        'subcaste' => 'sometimes|string|max:50',
        'transport_mode' => 'sometimes|string|max:50',
        'vehicle_no' => 'sometimes|string|max:50',
        'bus_id' => 'sometimes|integer',
        'emergency_name' => 'sometimes|string|max:100',
        'emergency_contact' => 'sometimes|string|max:15',
        'emergency_add' => 'sometimes|string|max:255',
        'height' => 'sometimes|numeric',
        'weight' => 'sometimes|numeric',
        'has_specs' => 'sometimes|boolean',
        'allergies' => 'sometimes|string|max:255',
        'nationality' => 'sometimes|string|max:50',
        'permant_add' => 'sometimes|string|max:255',
        'city' => 'sometimes|string|max:100',
        'state' => 'sometimes|string|max:100',
        'pincode' => 'sometimes|string|max:10',
        'IsDelete' => 'sometimes|in:Y,N',
        'prev_year_student_id' => 'sometimes|string|max:255',
        'isPromoted' => 'sometimes|boolean',
        'isNew' => 'sometimes|boolean',
        'isModify' => 'sometimes|boolean',
        'isActive' => 'sometimes|in:Y,N',
        'reg_no' => 'sometimes|string|max:255',
        'house' => 'sometimes|string|max:50',
        'stu_aadhaar_no' => 'sometimes|string|max:20',
        'category' => 'sometimes|string|max:50',
        'last_date' => 'sometimes|date',
        'slc_no' => 'sometimes|string|max:255',
        'slc_issue_date' => 'sometimes|date',
        'leaving_remark' => 'sometimes|string|max:255',
        'deleted_date' => 'sometimes|date',
        'deleted_by' => 'sometimes|string|max:50',
        'image_name' => 'sometimes|string|max:100',
        'guardian_name' => 'sometimes|string|max:100',
        'guardian_add' => 'sometimes|string|max:255',
        'guardian_mobile' => 'sometimes|string|max:15',
        'relation' => 'sometimes|string|max:50',
        'guardian_image_name' => 'sometimes|string|max:100',
        'udise_pen_no' => 'sometimes|string|max:50',
        'added_bk_date' => 'sometimes|date',
        'added_by' => 'sometimes|string|max:50',
        
        'parent_id' => 'sometimes|exists:parents,parent_id',
        'father_name' => 'sometimes|string|max:100',
        'father_occupation' => 'sometimes|string|max:100',
        'f_office_add' => 'sometimes|string|max:200',
        'f_office_tel' => 'sometimes|string|max:11',
        'f_mobile' => 'sometimes|string|max:10',
        'f_email' => 'sometimes|string|max:50',
        'mother_name' => 'sometimes|string|max:100',
        'mother_occupation' => 'sometimes|string|max:100',
        'm_office_add' => 'sometimes|string|max:200',
        'm_office_tel' => 'sometimes|string|max:11',
        'm_mobile' => 'sometimes|string|max:13',
        'm_emailid' => 'sometimes|string|max:50',
        'parent_adhar_no' => 'sometimes|string|max:14',
        'm_adhar_no' => 'sometimes|string|max:14',
        'f_dob' => 'sometimes|date',
        'm_dob' => 'sometimes|date',
        'f_blood_group' => 'sometimes|string|max:5',
        'm_blood_group' => 'sometimes|string|max:5',
        'IsDelete' => 'sometimes|in:Y,N',
        'father_image_name' => 'sometimes|string|max:100',
        'mother_image_name' => 'sometimes|string|max:100',
    ]);

    // Find the student by ID
    $student = Student::find($studentId);
    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }

    // Update student details
    $student->update($request->only([
        'student_name', 'dob', 'gender', 'admission_date', 'stud_id_no', 
        'mother_tongue', 'birth_place', 'admission_class', 'roll_no', 
        'class_id', 'section_id', 'fees_paid', 'blood_group', 'religion', 
        'caste', 'subcaste', 'transport_mode', 'vehicle_no', 'bus_id', 
        'emergency_name', 'emergency_contact', 'emergency_add', 'height', 
        'weight', 'has_specs', 'allergies', 'nationality', 'permant_add', 
        'city', 'state', 'pincode', 'IsDelete', 'prev_year_student_id', 
        'isPromoted', 'isNew', 'isModify', 'isActive', 'reg_no', 'house', 
        'stu_aadhaar_no', 'category', 'last_date', 'slc_no', 'slc_issue_date', 
        'leaving_remark', 'deleted_date', 'deleted_by', 'image_name', 
        'guardian_name', 'guardian_add', 'guardian_mobile', 'relation', 
        'guardian_image_name', 'udise_pen_no', 'added_bk_date', 'added_by', 
        'parent_id'
    ]));

    // If parent_id is provided, update parent details
    if ($request->has('parent_id')) {
        $parentId = $request->input('parent_id');
        $parent = Parents::find($parentId);
        if ($parent) {
            $parent->update($request->only([
                'father_name', 'father_occupation', 'f_office_add', 'f_office_tel', 
                'f_mobile', 'f_email', 'mother_name', 'mother_occupation', 
                'm_office_add', 'm_office_tel', 'm_mobile', 'm_emailid', 
                'parent_adhar_no', 'm_adhar_no', 'f_dob', 'm_dob', 
                'f_blood_group', 'm_blood_group', 'IsDelete', 'father_image_name', 
                'mother_image_name'
            ]));
        } else {
            return response()->json(['error' => 'Parent not found'], 404);
        }
    }

    return response()->json([
        'message' => 'Student and parent details updated successfully',
        'student' => $student
    ]);
}



// Subject Allotment Methods


// get all the class and their associated Division.
public function getallClass(Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');

    // Fetch divisions with their associated class data
    $divisions = Division::with('getClass')
        ->where('academic_yr', $academicYr)
        ->get();

    // Transform the data to only include section_id and class name
    $result = $divisions->map(function ($division) {
        return [
            'section_id' => $division->section_id,
            'name' => $division->name,
            'class_name' => $division->getClass->name
        ];
    });

    return response()->json($result);
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

    $subjectAllotmentList = $query->orderBy('subject_id', 'DESC')->get();
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
        'teacher_id' => 'required',
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

    if (!$subjectAllotment) {
        return response()->json(['error' => 'Subject Allotment not found'], 404);
    }
    $isAllocated = StudentMark::where('subject_id', $subjectAllotment->subject_id)
        ->exists();

    if ($isAllocated) {
        return response()->json(['error' => 'Subject Allotment cannot be deleted as it is associated with student marks'], 400);
    }

    if ($subjectAllotment->delete()) {
        return response()->json(['message' => 'Subject Allotment deleted successfully'], 200);
    }

    return response()->json(['error' => 'Failed to delete Subject Allotment'], 500);
}
 
//Classs list
public function getClassList(Request $request)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
    $classes =Classes::where('academic_yr', $academicYr)->get();
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
    
    // Return Combined Response
    return response()->json([
        'divisions' => $divisionNames,
        'subjects' => $subjects
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



// Get the Division and subject for the selected class preasign.
public function getDivisionsAndSubjectsByClass($classId)
{
    $subjectAllotments = SubjectAllotment::with(['getDivision', 'getSubject'])
        ->where('class_id', $classId)
        ->get();  
    $subjectAllotmentsCount = SubjectAllotment::with(['getDivision', 'getSubject'])
        ->where('class_id', $classId)
        ->count();  
    return response()->json(
      ['subjectAllotments'=>$subjectAllotments,
       'subjectAllotmentsCount' =>$subjectAllotmentsCount      
      ]
    );
}

// Save the Subject Allotment  
public function storeSubjectAllotment(Request $request)
{
    $validatedData = $request->validate([
        'class_id' => 'required|exists:class,class_id',
        'section_ids' => 'required|array',
        'section_ids.*' => 'exists:section,section_id', 
        'subject_ids' => 'required|array',
        'subject_ids.*' => 'exists:subject_master,sm_id',
    ]);

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    $classId = $validatedData['class_id'];
    $sectionIds = $validatedData['section_ids'];
    $subjectIds = $validatedData['subject_ids'];

    foreach ($sectionIds as $sectionId) {
        foreach ($subjectIds as $subjectId) {
            $existingAllotment = SubjectAllotment::where([
                ['class_id', '=', $classId],
                ['section_id', '=', $sectionId],
                ['sm_id', '=', $subjectId],
                ['academic_yr', '=', $academicYr],
            ])->first();

            if (!$existingAllotment) {
                SubjectAllotment::create([
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'sm_id' => $subjectId,
                    'academic_yr' => $academicYr,
                ]);
            }
        }
    }

    return response()->json([
        'message' => 'Subject allotment details stored successfully',
    ], 201);
}

// Get the Subject-Allotment details subject with teachers By Section
public function getSubjectAllotmentWithTeachersBySection(Request $request, $sectionId)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    $subjectAllotments = SubjectAllotment::with(['getClass', 'getSubject', 'getTeacher'])
        ->where('section_id', $sectionId)
        ->where('academic_yr', $academicYr)
        ->get()
        ->groupBy('sm_id');

    $response = [];

    foreach ($subjectAllotments as $subjectId => $allotments) {
        $teachers = $allotments->map(function ($allotment) {
            return [
                'teacher_id' => optional($allotment->getTeacher)->teacher_id,
                'teacher_name' => optional($allotment->getTeacher)->name,
            ];
        });

        $response[] = [
            'class_id' => optional($allotments->first())->class_id,
            'class_name' => optional($allotments->first()->getClass)->name,
            'section_id' => optional($allotments->first())->section_id,
            'subject_id' => $subjectId,
            'sm_id' => optional($allotments->first())->sm_id,
            'subject_name' => optional($allotments->first()->getSubject)->name,
            'teachers' => $teachers,
        ];
    }

    return response()->json([
        'status' => 'success',
        'data' => $response
    ]);
}

// Pending 
public function updateSubjectAllotmentWithTeachers(Request $request)
{
    // Define validation rules
    $rules = [
        'data' => 'required|array',
        'data.*.class_id' => 'required|exists:class,class_id',
        'data.*.section_id' => 'required|exists:section,section_id',
        'data.*.subject_id' => 'required|exists:subject,subject_id',
        'data.*.sm_id' => 'required|integer',
        'data.*.teachers' => 'array',
        'data.*.teachers.*' => 'nullable|exists:teacher,teacher_id',
    ];

    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()
        ], 422);
    }

    try {
        $subjectAllotments = $request->input('data');

        foreach ($subjectAllotments as $allotmentData) {
            // Loop through each teacher in the provided list
            if (!empty($allotmentData['teachers'])) {
                foreach ($allotmentData['teachers'] as $teacherId) {
                    // Check if the combination already exists in the database
                    $existingAllotment = SubjectAllotment::where([
                        ['class_id', '=', $allotmentData['class_id']],
                        ['section_id', '=', $allotmentData['section_id']],
                        ['sm_id', '=', $allotmentData['sm_id']],
                        ['teacher_id', '=', $teacherId],
                    ])->first();

                    // If the combination does not exist, create a new record
                    if (!$existingAllotment) {
                        SubjectAllotment::create([
                            'class_id' => $allotmentData['class_id'],
                            'section_id' => $allotmentData['section_id'],
                            'academic_yr' => $request->input('academic_yr'),
                            'sm_id' => $allotmentData['sm_id'],
                            'teacher_id' => $teacherId,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subject allotments updated successfully!',
        ]);

    } catch (\Exception $e) {
        \Log::error('Error updating subject allotments: ' . $e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while updating the subject allotments.'
        ], 500);
    }
}


// for the teacher list .
public function getTeacherNames(Request $request){
      
    $teacherList = User::Where('role_id','T')->get();
    return response()->json($teacherList);
}



public function allotTeacherForSubjects(Request $request)
    {
        // Manual validation with detailed error logging
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,class_id',
            'section_id' => 'required|exists:sections,section_id',
            'teacher_id' => 'required|exists:teachers,teacher_id',
            'sm_ids' => 'required|array',
            'sm_ids.*' => 'exists:subject_master,sm_id'
        ]);

        if ($validator->fails()) {
            // Log validation errors
            Log::error('Validation failed for subject allotment.', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Log the start of the operation
            Log::info('Starting subject allotment for teacher.', [
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
                'teacher_id' => $request->input('teacher_id'),
                'sm_ids' => $request->input('sm_ids')
            ]);

            // Token validation
            $payload = getTokenPayload($request);
            if (!$payload) {
                Log::warning('Invalid or missing token for subject allotment request.');
                return response()->json(['error' => 'Invalid or missing token'], 401);
            }
            $academicYr = $payload->get('academic_year');

            // Retrieve data from the request
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $teacherId = $request->input('teacher_id');
            $smIds = $request->input('sm_ids');

            // Process each sm_id and create new entries
            foreach ($smIds as $smId) {
                $allotment = SubjectAllotment::create([
                    'sm_id' => $smId,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'teacher_id' => $teacherId,
                    'academic_yr' => $academicYr
                ]);

                // Log each successful subject allotment entry
                Log::info('Subject allotment entry created.', [
                    'subject_allotment_id' => $allotment->subject_id,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'teacher_id' => $teacherId,
                    'sm_id' => $smId
                ]);
            }

            // Log successful operation
            Log::info('Subject allotments successfully created.', [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'teacher_id' => $teacherId,
                'sm_ids' => $smIds
            ]);

            return response()->json(['message' => 'Subject allotments successfully created'], 201);

        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error creating subject allotments: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Return detailed error response
            return response()->json(['error' => 'Failed to create subject allotments. ' . $e->getMessage()], 500);
        }
    }




public function allocateTeacherForClass(Request $request)
{
    $validatedData = $request->validate([
        'teacher_assignments' => 'required|array', 
        'teacher_assignments.*.subject_id' => 'required|integer|exists:subject,subject_id',
        'teacher_assignments.*.teacher_id' => 'required|integer|exists:teacher,teacher_id',
        'academic_yr' => 'required|string', // Ensure academic_yr is present
    ]);

    $teacherAssignments = $validatedData['teacher_assignments'];
    $academicYr = $validatedData['academic_yr'];

    $updateErrors = [];
    
    foreach ($teacherAssignments as $assignment) {
        $subjectId = $assignment['subject_id'];
        $teacherId = $assignment['teacher_id'];

        $updated = SubjectAllotment::where('subject_id', $subjectId)
            ->where('academic_yr', $academicYr)
            ->update(['teacher_id' => $teacherId]);

        if ($updated === 0) {
            $updateErrors[] = [
                'subject_id' => $subjectId,
                'error' => 'Failed to update teacher_id. Record may not exist or already have the same teacher_id.'
            ];
        }
    }

    if (count($updateErrors) > 0) {
        return response()->json(['errors' => $updateErrors], 400);
    }
    return response()->json(['message' => 'Teacher assigned successfully to subjects'], 200);
}

public function editallocateTeacherForClass($subjectId)
{
    $subjectAllotment = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
        ->find($subjectId);
    if ($subjectAllotment) {
        return response()->json($subjectAllotment, 200);
    }

    return response()->json(['error' => 'Subject Allotment not found.'], 404);
}

public function updateallocateTeacherForClass(Request $request, $subjectId)
{
    $request->validate([
        'teacher_id' => 'required|integer|exists:teacher,teacher_id',
    ]);

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }

    $academicYr = $payload->get('academic_year');       
    $teacherId = $request->input('teacher_id');

    $subjectAllotment = SubjectAllotment::where('subject_id', $subjectId)
        ->where('academic_yr', $academicYr)
        ->first();

    if ($subjectAllotment) {
        $subjectAllotment->teacher_id = $teacherId;
        $subjectAllotment->save();

        return response()->json(['message' => 'Teacher ID updated successfully.'], 200);
    }

    return response()->json(['error' => 'Subject Allotment not found.'], 404);
}

public function deleteSubjectAlloted($subjectId)
{
    $subjectAllotment = SubjectAllotment::find($subjectId);
    if ($subjectAllotment) {
        $subjectAllotment->delete();
        return response()->json([
            'status' => 200,
            'message' => "Subject Allotment Deleted Successfully"
        ]);
    } else {
        return response()->json([
            'status' => 404,
            'message' => "Subject Allotment Not Found"
        ]);
    }
}

public function getSubjectsAndSectionsByClass(Request $request, $classId)
{
    $class = Classes::find($classId);
    if (!$class) {
        return response()->json([
            'status' => 404,
            'message' => 'Class not found',
        ], 404);
    }
    $subjectsAndSections = SubjectAllotment::with('getSubject', 'getDivision')
        ->where('class_id', $classId)
        ->get()
        ->groupBy('section_id');

    return response()->json([
        'status' => 200,
        'data' => $subjectsAndSections,
    ]);
}

}








































































































































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