<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaidTransaction extends Model
{
    protected $fillable = [
        'uuid',
        'accountId',
        'transactionId',
        'amount',
        'date',
        'name',
        'categoryId',
        'meta',
    ];
}
