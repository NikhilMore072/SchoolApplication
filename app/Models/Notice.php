<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;
    protected $table = 'notice'; // Replace with your actual table name
    
    protected $primaryKey = 'notice_id'; // Replace with your actual primary key

    public function classes()
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }
}
