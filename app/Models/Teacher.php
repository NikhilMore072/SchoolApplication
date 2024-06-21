<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    protected $table = 'teacher'; // Replace with your actual table name
    
    protected $primaryKey = 'teacher_id'; // Replace with your actual primary key

}
