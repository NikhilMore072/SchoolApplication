<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $table = 'department';
    protected $primaryKey = 'department_id'; 
    public $incrementing = true; 
    protected $fillable = ['department_id','name','academic_yr'];


    public function classes()
    {
        return $this->hasMany(Classes::class, 'department_id', 'department_id');
    }

}
