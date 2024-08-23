<?php

namespace App\Models;

use App\Models\Classes;
use App\Models\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubjectAllotment extends Model
{
    use HasFactory;
    protected $primaryKey = 'subject_id'; 
    public $incrementing = true;     
    protected $table = 'subject';
    public $timestamps = true;
    protected $fillable = ['sm_id','class_id','section_id','teacher_id','academic_yr'];

     
    public function getClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');  
    }    

    public function getDivision()
    {
        return $this->belongsTo(Division::class, 'section_id');  
    }  
     
    public function getTeacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');  
    }

    public function getSubject()
    {
        return $this->belongsTo(Subjects::class, 'sm_id');  
    }


}
