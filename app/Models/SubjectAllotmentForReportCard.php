<?php

namespace App\Models;

use App\Models\SubjectForReportCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubjectAllotmentForReportCard extends Model
{
    use HasFactory;
    protected $primaryKey = 'sub_reportcard_id'; 
    public $incrementing = true;     
    protected $table = 'subjects_on_report_card';
    public $timestamps = true;
    protected $fillable = ['sub_reportcard_id','sub_rc_master_id','class_id','subject_type','academic_yr'];

    public function getSubjectsForReportCard(){
        return $this->belongsTo(SubjectForReportCard::class,'sub_rc_master_id');
    }
     
    public function getClases(){
        return $this->belongsTo(Classes::class,'class_id');
    }
}
