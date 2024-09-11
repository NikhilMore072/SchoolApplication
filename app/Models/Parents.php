<?php

namespace App\Models;

use App\Models\UserMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Parents extends Model
{
    use HasFactory;
    protected $primaryKey = 'parent_id'; 
    public $incrementing = true;     
    protected $table ='parent';

    protected $fillable = [
        'parent_id',
        'father_name',
        'father_occupation',
        'f_office_add',
        'f_office_tel',
        'f_mobile',
        'f_email',
        'mother_name',
        'mother_occupation',
        'm_office_add',
        'm_office_tel',
        'm_mobile',
        'm_emailid',
        'parent_adhar_no',
        'm_adhar_no',
        'f_dob',
        'm_dob',
        'f_blood_group',
        'm_blood_group',
        'IsDelete',
        'father_image_name',
        'mother_image_name'
    ];
     
    //reg_id and role_id combination is unique .
    // role should be 'P'.
    public function user()  
    {
        return $this->belongsTo(UserMaster::class, 'parent_id', 'reg_id');
    }


}
