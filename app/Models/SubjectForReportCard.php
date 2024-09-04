<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectForReportCard extends Model
{
    use HasFactory;
    protected $primaryKey = 'sub_rc_master_id'; 
    public $incrementing = true;     
    protected $table ='subjects_on_report_card_master';

    protected $fillable = ['sub_rc_master_id','name','sequence'];
}
