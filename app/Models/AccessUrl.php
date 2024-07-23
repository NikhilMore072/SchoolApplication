<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessUrl extends Model
{
    use HasFactory;

    protected $table = 'access_urls';
    protected $fillable = ['name','parent_id','super_id'];
}
