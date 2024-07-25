<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\AccessUrl;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AccessUrlSeeder extends Seeder
{



    public function run(): void
    {
        $menus = [
            ['name' => 'Dashboard', 'parent_id' => 0,  ], //1
            // My Actions 
            ['name' => 'My Actions', 'parent_id' => 0,],//2
            ['name' => 'Student', 'parent_id' => 2,  ], //3
            ['name' => 'New Students List', 'parent_id' => 2,  ], //4
            ['name' => 'Manage Students', 'parent_id' => 2,  ], //5
            ['name' => 'LC Students', 'parent_id' => 2,  ],//6
            ['name' => 'Deleted Students Lists', 'parent_id' => 2,  ],//7
            ['name' => 'Send User Id to Parents', 'parent_id' => 2,  ],//8
            ['name' => 'Sibling Mapping', 'parent_id' => 2,  ],//9
              
            ['name' => 'Certificate', 'parent_id' => 2,  ],//10
            ['name' => 'Bonafide Certificate', 'parent_id' => 2,  ],//11
            ['name' => 'Caste Certificate', 'parent_id' => 2,  ],//12
            ['name' => 'Character Certificate', 'parent_id' => 2,  ],//13
            ['name' => 'Percentage Certificate', 'parent_id' => 2,  ],//14
            ['name' => 'Simple Bonafide  Certificate', 'parent_id' => 2,  ],//15        
            
            
            ['name' => 'Staff', 'parent_id' => 2,  ],//16
            ['name' => 'Manage Staff', 'parent_id' => 2,  ],//17
            ['name' => 'Manage Caretaker', 'parent_id' => 2,  ],//18
            ['name' => 'Substitute Teacher', 'parent_id' => 2,  ],//19


            ['name' => 'Leaving Certificate', 'parent_id' => 2,  ],//20
            ['name' => 'Generate LC', 'parent_id' => 2,  ],//21
            ['name' => 'Manage LC', 'parent_id' => 2,  ],//22


            ['name' => 'Leave', 'parent_id' => 2,  ],//23
            ['name' => 'Leave Allocation', 'parent_id' => 2,  ],//24
            ['name' => 'Leave Allocation to All Staff', 'parent_id' => 0,  ],//25
            ['name' => 'Leave Application', 'parent_id' => 2,  ],//26

            ['name' => 'Notice/SMS', 'parent_id' => 2,  ],//27
            ['name' => 'Holiday List', 'parent_id' => 2,  ],//28
            ['name' => 'Allot Class teachers', 'parent_id' => 2,  ],//29
            ['name' => 'Allot Department Coordinator', 'parent_id' => 2,  ],//30
            ['name' => 'Allot GR Numbers', 'parent_id' => 2,  ],//31
            ['name' => 'Update Category and religion', 'parent_id' => 2,  ],//32
            ['name' => 'Update Student ID and other details', 'parent_id' => 2,  ],//33
            ['name' => 'Time Table', 'parent_id' => 2,  ],//34
            ['name' => 'News', 'parent_id' => 2,  ],//35
            ['name' => 'Important links', 'parent_id' => 2,  ],//36
            ['name' => 'Book Requisition', 'parent_id' => 2,  ],//37
            ['name' => 'Approve Stationery', 'parent_id' => 2,  ],//38
            ['name' => 'Substitute Class Teacher', 'parent_id' => 2,  ],//39

            //ID Card
            ['name' => 'ID Card', 'parent_id' => 0,  ],//40
            ['name' => 'Student ID Card', 'parent_id' => 40,  ],//41
            ['name' => 'Teacher ID Card', 'parent_id' => 40,  ],//42
            ['name' => 'Pending Student ID Card', 'parent_id' => 40,  ],//43

            //View 
            ['name' => 'View', 'parent_id' => 0,  ],//44
            ['name' => 'Leaving Certificate', 'parent_id' => 44,  ],//45
            ['name' => 'Notices/SMS for staff', 'parent_id' => 44,  ],//46
            ['name' => 'Todays Birthday ', 'parent_id' => 44,  ],//47
            ['name' => 'Book Availability', 'parent_id' => 44,  ],//48
             

            //Reports
            ['name' => 'Reports', 'parent_id' => 0,  ],//49
            ['name' => 'Balance Leave', 'parent_id' => 49,  ],//50
            ['name' => 'Consolidated Leave', 'parent_id' => 49,  ],//51
            ['name' => 'Student Report', 'parent_id' => 49,  ],//52
            ['name' => 'Student Contact Details Report', 'parent_id' => 49,  ],//53
            ['name' => 'Student Remarks Report', 'parent_id' => 49,  ],//54
            ['name' => 'Student -Category wise Report', 'parent_id' => 49,  ],//55
            ['name' => 'Student -Religion wise Report', 'parent_id' => 49,  ],//56
            ['name' => 'Student -Genderwise Report', 'parent_id' => 49,  ],//57
            ['name' => 'New Students Report', 'parent_id' => 49,  ],//58
            ['name' => 'Left Students Report', 'parent_id' => 49,  ],//59
            ['name' => 'HSC Students Subjects Report', 'parent_id' => 49,  ],//60
            ['name' => 'Staff Report', 'parent_id' => 49,  ],//61
            ['name' => 'Monthly Attendance Report', 'parent_id' => 49,  ],//62
            ['name' => 'Fee Payment Report', 'parent_id' => 49,  ],//63
            ['name' => 'Worldline Fee Payment Report', 'parent_id' => 49,  ],//64
            ['name' => 'Pending Student ID Card Report', 'parent_id' => 49,  ],//65
            

            //Ticket
            ['name' => 'Ticket', 'parent_id' => 0,  ],//66
            ['name' => 'Service Type', 'parent_id' => 66,  ],//67
            ['name' => 'Sub Service Type ', 'parent_id' => 66,  ],//68
            ['name' => 'Appointment Window', 'parent_id' => 66,  ],//69
            ['name' => 'Ticket List', 'parent_id' => 66,  ],//70
            ['name' => 'Ticket Report', 'parent_id' => 66,  ],//71
            
            
            //Masters
            ['name' => 'Masters', 'parent_id' => 0,  ],//72
            ['name' => 'Section', 'parent_id' => 72,  ],//73
            ['name' => 'Class', 'parent_id' => 72,  ],//74
            ['name' => 'Division', 'parent_id' => 72,  ],//75
            ['name' => 'Subjects', 'parent_id' => 72,  ],//76
            ['name' => 'Subjects Allotment', 'parent_id' => 72,  ],//77
            ['name' => 'Studentwise Subject Allotment For HSC', 'parent_id' => 72,  ],//78
            ['name' => 'Subject Allotment For Report Card', 'parent_id' => 72,  ],//79
            ['name' => 'Exams', 'parent_id' => 72,  ],//80
            ['name' => 'Grades', 'parent_id' =>72,  ],//81
            ['name' => 'Marks Heading', 'parent_id' => 72,  ],//82
            ['name' => 'Allot Marks Heading', 'parent_id' => 72,  ],//83
            ['name' => 'Exam Timetable', 'parent_id' => 72,  ],//84
            ['name' => 'Stationery', 'parent_id' => 72,  ],//85
            ['name' => 'Leave type', 'parent_id' => 72,  ],//86
            ['name' => 'Letters', 'parent_id' => 72,  ],//87

            //Help 
            ['name' => 'Help', 'parent_id' => 0,  ],//88

            //Academic Year           
            ['name' => 'Academic Year', 'parent_id' => 0,  ],//89


             
        ];

        Menu::insert($menus);
    }
}
