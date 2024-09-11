<?php

namespace App\Models;

use App\Models\Classes;
use App\Models\Parents;
use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;
    protected $primaryKey = 'student_id'; 
    public $incrementing = true;     
    protected $table ='student';

    protected $fillable = [
        'academic_yr',
        'parent_id',
        'first_name',
        'mid_name',
        'last_name',
        'student_name',
        'dob',
        'gender',
        'admission_date',
        'stud_id_no',
        'mother_tongue',
        'birth_place',
        'admission_class',
        'roll_no',
        'class_id',
        'section_id',
        'fees_paid',
        'blood_group',
        'religion',
        'caste',
        'subcaste',
        'transport_mode',
        'vehicle_no',
        'bus_id',
        'emergency_name',
        'emergency_contact',
        'emergency_add',
        'height',
        'weight',
        'has_specs',
        'allergies',
        'nationality',
        'permant_add',
        'city',
        'state',
        'pincode',
        'IsDelete',
        'prev_year_student_id',
        'isPromoted',
        'isNew',
        'isModify',
        'isActive',
        'reg_no',
        'house',
        'stu_aadhaar_no',
        'category',
        'last_date',
        'slc_no',
        'slc_issue_date',
        'leaving_remark',
        'deleted_date',
        'deleted_by',
        'image_name',
        'guardian_name',
        'guardian_add',
        'guardian_mobile',
        'relation',
        'guardian_image_name',
        'udise_pen_no',
        'added_bk_date',
        'added_by'
    ];

    public function getClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
    public function getDivision()
    {
        return $this->belongsTo(Division::class, 'section_id');
    }

    public function parents()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }
    public function userMaster(){
        return $this->belongsTo(UserMaster::class,'parent_id','reg_id');
    }
    
    // public function userMaster(){
    //     return $this->belongsTo(House::class,'house','house_id');
    // }


}
