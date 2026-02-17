<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultLedger extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_defult_ledger';
    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'ledger_name',
        'location',
        'contact_number',
        'type',
    ];
}
