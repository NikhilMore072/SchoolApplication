<?php

namespace App\Models;

use App\Models\Classes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;
   

    protected $table = 'events'; // Specify the table name if different from the model name

    protected $primaryKey = 'event_id'; // Specify the primary key column name if different


    protected $fillable = [
        'unq_id',
        'title',
        'event_desc',
        'class_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'login_type',
        'isDelete',
        'publish',
        'created_by',
        'competition',
        'activity',
        'notify',
        'academic_yr',
    ]; 
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id', 'class_id');
    }
}
