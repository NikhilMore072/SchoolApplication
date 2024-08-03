<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;
    protected $table = 'teacher'; 
    
    protected $primaryKey = 'teacher_id'; 
    public $timestamps = false;
    protected $fillable = [
        'employee_id',
        'name',
        'father_spouse_name',
        'birthday',
        'date_of_joining',
        'sex',
        'religion',
        'blood_group',
        'address',
        'phone',
        'email',
        'designation',
        'academic_qual',
        'professional_qual',
        'special_sub',
        'trained',
        'experience',
        'aadhar_card_no',
        'teacher_image_name',
        'class_id',
        'section_id',
        'isDelete',
    ];  //getTeacher

    public function getTeacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');  
    }

}
