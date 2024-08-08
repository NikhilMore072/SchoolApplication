<?php

namespace App\Models;

use App\Models\Event;
use App\Models\Section;
use App\Models\Students;
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
      

    public function events()
    {
        return $this->hasMany(Event::class, 'class_id', 'class_id');
    }

    public function parentNotices()
    {
        return $this->hasMany(Notice::class, 'class_id', 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'department_id');  
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id', 'class_id');
    }

}
