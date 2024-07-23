<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccessUrlSeeder extends Seeder
{



    public function run(): void
    {
        $menus = [
            ['name' => 'Dashboard', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'My Actions', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student', 'parent_id' => 2, 'super_id' => null],
            ['name' => 'New Students List', 'parent_id' => 2, 'super_id' => null],
            ['name' => 'Manage Students', 'parent_id' => 2, 'super_id' => null],
            ['name' => 'LC Students', 'parent_id' => 2, 'super_id' => null],
            ['name' => 'Deleted Students Lists', 'parent_id' => 2, 'super_id' => null],
            ['name' => 'Send User Id to Parents', 'parent_id' => 8, 'super_id' => null],
            ['name' => 'Sibling Mapping', 'parent_id' => 0, 'super_id' => null],
              
            ['name' => 'Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Bonafide Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Caste Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Character Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Percentage Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Simple Bonafide  Certificate', 'parent_id' => 0, 'super_id' => null],        
            
            
            ['name' => 'Staff', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Manage Staff', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Manage Caretaker', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Substitute Teacher', 'parent_id' => 0, 'super_id' => null], 


            ['name' => 'Leaving Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Generate LC', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Manage LC', 'parent_id' => 0, 'super_id' => null],


            ['name' => 'Leave', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Leave Allocation', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Leave Allocation to All Staff', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Leave Application', 'parent_id' => 0, 'super_id' => null],

            ['name' => 'Notice/SMS', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Holiday List', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Allot Class teachers', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Allot Department Coordinator', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Allot GR Numbers', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Update Category and religion', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Update Student ID and other details', 'parent_id' => 0, 'super_id' => null],








            ['name' => 'Allot Roll Numbers', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Promote Students', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Remark', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Time Table', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'View Attendance', 'parent_id' => 0, 'super_id' => null],

          
            
            ['name' => 'Leaving Certificate', 'parent_id' => 0, 'super_id' => null],




            ['name' => 'Curriculum', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Chapters', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Lesson Plan Heading', 'parent_id' => 0, 'super_id' => null],

            
            ['name' => 'Library', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Book Availability', 'parent_id' => 0, 'super_id' => null],

            ['name' => 'View', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Teachers Timetable', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],
            ['name' => '', 'parent_id' => 0, 'super_id' => null],













           
            ['name' => 'Manage Caretaker', 'parent_id' => 0, 'super_id' => null],
           
            ['name' => 'Event', 'parent_id' => 0, 'super_id' => null],

            ['name' => 'News', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Important links', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Book Requisition', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Approve Stationery', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Substitute Class Teacher', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student ID Card', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Leaving Certificate', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Notices/SMS for staff', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Todays Birthday ', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Book Availability', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Balance Leave', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Consolidated Leave', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student Report', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student Contact Details Report', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student Remarks Report', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student - Category wise Report', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Student - Religion wise Report', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Farmer', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Soil Test Request', 'parent_id' => 0, 'super_id' => null],
            ['name' => 'Khata-Book', 'parent_id' => 0, 'super_id' => null],
        ];

        // Insert data into the access_urls table
        AccessUrl::insert($menus);
    }
}
