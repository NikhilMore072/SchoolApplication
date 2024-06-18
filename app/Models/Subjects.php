<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subjects extends Model
{
    use HasFactory;
    protected $primaryKey = 'sm_id'; 
    public $incrementing = true;     
    protected $table ='subject_master';
    protected $fillable =['sm_id','name','subject_type'];
}
