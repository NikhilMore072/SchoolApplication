<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccountName extends Model
{
    use HasFactory;
    protected $table = 'bank_account_name';
    protected $fillable = ['account_name'];

}
