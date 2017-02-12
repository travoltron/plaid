<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaidAccount extends Model
{
    protected $fillable = [
        'uuid',
        'token',
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
        'batch',
        'smartsave',
        'plaidAuth',
        'plaidConnect',
        'plaidIncome',
        'plaidInfo',
        'plaidRisk',
    ];
}
