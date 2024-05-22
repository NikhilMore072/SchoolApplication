<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table ='students';
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'house',
        'admitted_in_class',
        'gender',
        'blood_group',
        'nationality',
        'birth_place',
        'mother_tongue',
        'emergency_name',
        'date_of_birth',
        'date_of_admission',
        'grn_no',
        'student_id_no',
        'student_aadhaar_no',
        'class',
        'division',
        'address',
        'city',
        'state',
        'pincode',
        'religion',
        'caste',
        'emergency_address',
        'emergency_contact',
        'transport_mode',
        'vehicle_no',
        'allergies',
        'height',
        'roll_no',
        'category',
        'weight',
        'has_spectacles'];
}
