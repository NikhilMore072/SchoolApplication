<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendence extends Model
{
    use HasFactory;
    protected $primaryKey = 'attendance_id'; 
    public $incrementing = true;     
    protected $table ='attendance';

    protected $fillable = [
        'attendance_id',
        'unq_id',
        'teacher_id',
        'class_id',
        'section_id',
        'subject_id',
        'date',
        'student_id',
        'attendance_status',
        'only_date',
        'academic_yr'
    ];
}
