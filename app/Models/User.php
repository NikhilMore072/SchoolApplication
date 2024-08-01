<?php

// namespace App\Models;

// // use Illuminate\Contracts\Auth\MustVerifyEmail;
// use session;
// use App\Models\Setting;
// use Laravel\Sanctum\HasApiTokens;
// use Illuminate\Notifications\Notifiable;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;


// class User extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable;

//     /**
//      * The attributes that are mass assignable.
//      *
//      * @var array<int, string>
//      */
//     // protected $table = 'user_master';
//     protected $fillable = ['email','user_id','name','password','reg_id','role_id','answer_one','answer_two','IsDelete'];
//     // protected $fillable = [
//     //     'name',
//     //     'email',
//     //     'password',
//     // ];

//     /**
//      * The attributes that should be hidden for serialization.
//      *
//      * @var array<int, string>
//      */
//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     /**
//      * Get the attributes that should be cast.
//      *
//      * @return array<string, string>
//      */
//     protected function casts(): array
//     {
//         return [
//             'email_verified_at' => 'datetime',
//             'password' => 'hashed',
//         ];
//     }
    
   
     
//     public function getTeacher()
//     {
//         return $this->belongsTo(Teacher::class, 'reg_id');  
//     }


//     public function getAcademicYrAttribute()
//     {
//         if (session()->has('sessionData')) {
//             return session('sessionData')['academic_yr'];
//         }    
//         return Setting::where('active', 'Y')->first()->academic_yr;
//     }
    
    
    
//      // public function getAcademicYrAttribute()
//     // {
//     //     return Setting::where('active', 'Y')->first()->academic_yr;
//     // }
       

// }



namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Models\Teacher;

use App\Models\Role;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject; // Import JWTSubject interface

class User extends Authenticatable implements JWTSubject // Implement JWTSubject interface
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email','user_id','name','password','reg_id','role_id','answer_one','answer_two','IsDelete'];

        public function getTeacher()
    {
        return $this->belongsTo(Teacher::class, 'reg_id');  
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array of custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
           'academic_yr' =>  Setting::where('active', 'Y')->first()->academic_yr,
           'institute_name' =>  Setting::where('active', 'Y')->first()->institute_name,          
        ];
    }

   
}

