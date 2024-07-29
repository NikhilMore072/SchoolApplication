<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectAllotment extends Model
{
    use HasFactory;
    protected $primaryKey = 'subject_id'; 
    public $incrementing = true;     
    protected $table = 'subject_allotments';
    protected $fillable = ['sm_id','class_id','section_id','teacher_id','academic_yr'];


}
