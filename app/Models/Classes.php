<?php

namespace App\Models;

use App\Models\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classes extends Model
{
    
    protected $table = 'class';
    protected $primaryKey = 'class_id'; 
    public $incrementing = true; 
    protected $fillable = ['class_id','name', 'name_numeric', 'academic_yr', 'department_id'];

    public function getDepartment()
    {
        return $this->belongsTo(Section::class, 'department_id');  
    }

}
