<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaidAccount extends Model
{
    protected $fillable = [
        'uuid',
        'institutionName',
        'debtHash',
        'logo',
        'accountName',
        'accountId',
        'last4',
        'type',
        'accountNumber',
        'routingNumber',
        'balance',
        'spendingLimit',
        'apr',
        'minimumPayment',
        'smartsave',
        'plaidAuth',
        'plaidConnect',
        'plaidIncome',
        'plaidInfo',
        'plaidRisk',
    ];
}
