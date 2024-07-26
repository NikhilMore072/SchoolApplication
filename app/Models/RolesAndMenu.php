<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolesAndMenu extends Model
{
    use HasFactory;
    protected $table = 'roles_and_menus';
    protected  $fillable = ['role_id','menu_id'];
   

    public function getRole()
    {
        return $this->belongsTo(Role::class, 'reg_id');  
    }

    public function getMenu(){
        return $this->belongsTo(Menu::class,'menu_id');
    }

}
