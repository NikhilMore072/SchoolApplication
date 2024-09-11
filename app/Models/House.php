<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    use HasFactory;
    protected $primaryKey = 'house_id'; 
    protected $table ='house';

    protected $fillable = ['house_id','house_name','color_code'] ;

}
