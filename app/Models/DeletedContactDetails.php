<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedContactDetails extends Model
{
    use HasFactory;

    protected $table = 'deleted_contact_details';
    protected $primaryKey = 'id';
    public $incrementing = true;  
    protected $fillable = [	'phone_no','email_id',	'm_emailid'	];
}
