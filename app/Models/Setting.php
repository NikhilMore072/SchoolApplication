<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    
    protected $table = 'settings';
    protected $fillable = ['institute_name','address','phone_number','	page_title','page_meta_tag','academic_yr_from','academic_yr_to','academic_yr','active'];






}
