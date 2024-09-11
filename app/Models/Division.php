<?php

namespace App\Models;

use App\Models\Classes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends Model
{
    
    use HasFactory;
    protected $primaryKey = 'section_id'; 
    public $incrementing = true;     
    protected $table ='section';
    protected $fillable =['section_id','name','class_id','academic_yr'];

    public function getClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');  
    }
    public function students()
{
    return $this->hasMany(Student::class, 'section_id', 'section_id');
}

}
