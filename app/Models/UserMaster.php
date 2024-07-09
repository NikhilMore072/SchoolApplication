<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserMaster extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_master';
    protected $primaryKey = 'user_id'; 
    public $incrementing = false; 
    protected $keyType = 'string'; 
    protected $fillable = ['user_id','name','password','reg_id','role_id','answer_one','answer_two','IsDelete'];

    public function getTeacher()
    {
        return $this->belongsTo(Teacher::class, 'reg_id');  
    }
}
