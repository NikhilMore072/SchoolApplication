<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffNotice extends Model
{
    use HasFactory;
    protected $table = 'staff_notice'; 
    protected $primaryKey = 't_notice_id'; 
    public $incrementing = true; 
    protected $fillable = [
        'unq_id',
        'subject',
        'notice_desc',
        'notice_date',
        'teacher_id',
        'notice_type',
        'academic_yr',
        'publish',
        'created_by',
        'department_id'
    ]; // Fillable fields for mass assignment

    // Relationships

    // Define the relationship with Teacher (assuming Teacher model exists)
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'teacher_id');
    }

    // Define the relationship with Department (assuming Department model exists)
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}
