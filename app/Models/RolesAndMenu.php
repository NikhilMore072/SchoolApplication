<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolesAndMenu extends Model
{
    use HasFactory;
    protected $table = 'roles_and_menus';
    protected  $fillable = ['role_id','menu_id'];
}
