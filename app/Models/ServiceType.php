<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $table = 'service_type';
    protected $primaryKey = 'service_id'; 
    public $incrementing = true; 
    protected $fillable = ['service_id','service_name', 'role_id', 'description', 'RequiresAppointment'];

}
