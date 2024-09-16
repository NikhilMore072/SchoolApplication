<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkHeading extends Model
{
    use HasFactory;

    protected $table ='marks_headings';
    protected $fillable = ['marks_headings_id','name','written_exam','sequence'];
}
