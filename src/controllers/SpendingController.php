<?php

namespace Travoltron\Plaid\Controllers;

use Illuminate\Http\Request;
use Travoltron\Plaid\Plaid;
use Illuminate\Routing\Controller as BaseController;
use Travoltron\Plaid\Requests\Auth\AddAccountRequest;

class SpendingController extends BaseController
{
    public function addAccount(AddAccountRequest $request)
    {
        dd($request);
    }
}
