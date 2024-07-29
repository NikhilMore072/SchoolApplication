<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectMaster extends Model
{
    use HasFactory;
     
    protected $table = 'subject_masters';
    protected $primaryKey = 'sm_id'; 
    public $incrementing = true; 
    protected $fillable = ['name','subject_type'];

}
