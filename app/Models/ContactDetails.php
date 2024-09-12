<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactDetails extends Model
{
    use HasFactory;

    protected $table = 'contact_details';
    // protected $primaryKey = 'id';
    // public $incrementing = true;    
    protected $fillable = ['phone_no','alternate_phone_no','email_id','m_emailid', 'sms_consent',];
        
        
       
    
}
