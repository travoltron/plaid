<?php

namespace Travoltron\Plaid\Controllers;

use Illuminate\Http\Request;
use Travoltron\Plaid\Plaid;
use Illuminate\Routing\Controller as BaseController;
use Travoltron\Plaid\Requests\Accounts\GetIncomeRequest;
use Travoltron\Plaid\Requests\Accounts\GetAccountsRequest;

class AccountController extends BaseController
{
    public function getIncome(GetIncomeRequest $request)
    {
        $income = Plaid::getIncomeData($request->input('token'));
        if($this->hasError($income)) {
            return $this->hasError($income);
        }
        if(!collect($income)->isEmpty()) {
            return $this->successFormatter(['income' => $income['income']]);
        }
    }

    public function getInfo(GetIncomeRequest $request)
    {
        $info = Plaid::getInfoData($request->input('token'));
        if($this->hasError($info)) {
            return $this->hasError($info);
        }
        if(!collect($info)->isEmpty()) {
            return $this->successFormatter(['info' => $info['info']]);
        }
    }

    public function getRisk(GetIncomeRequest $request)
    {
        $risk = Plaid::getRiskData($request->input('token'));

        if($this->hasError($risk)) {
            return $this->hasError($risk);
        }
        if(!$lookup->isEmpty()) {
            return $this->successFormatter(['risk' => $risk['risk']]);
        }
    }

    public function getAccounts(GetAccountsRequest $request)
    {
        $lookup = Plaid::getAuthData($request->input('token'));
        if($this->hasError($lookup)) {
            $lookup = Plaid::getConnectData($request->input('token'));
        }
        // Run twice to account for Connect lookup failing also
        if($this->hasError($lookup)) {
            // return $this->errorFormatter($lookup['code']);
        }
        $accounts = collect($lookup['accounts']);

        if($request->input('scope') === 'all') {
            if(!$accounts->isEmpty()) {
                return $this->successFormatter(['accounts' => $this->formatter($accounts)]);
            } else {
                return $this->errorFormatter('1610');
            }
        }
        return $this->successFormatter(['accounts' => $this->typeFilter($accounts, $request->input('scope'))]);
    }

    protected function typeFilter(\Illuminate\Support\Collection $accounts, $type)
    {
        $filtered = $accounts->filter(function($acct) use ($type) {
            if($acct['type'] === 'depository') {
                return $acct['subtype'] === $type;
            }
            return $acct['type'] === $type;
        })->values();

        return $this->formatter($filtered);
    }

    protected function formatter(\Illuminate\Support\Collection $response)
    {
        if($response->isEmpty()) {
            return $this->errorFormatter('1610');
        }
        return $response->map(function($acct) {
            return [
                'name' => $acct['institution_type'],
                'balance' => $acct['balance'],
                'type' => ($acct['type'] === 'depository')?$acct['subtype']:$acct['type'],
                'meta' => $acct['meta']
            ];
        });
    }

    protected function authable($type)
    {
        $authable = collect(Plaid::searchType('auth')['results'])->map(function($inst) {
            return $inst['type'];
        })->toArray();

        if(in_array($type, $authable)) {
            return true;
        }
        return false;
    }

    protected function hasError($reply)
    {
        if(isset($reply['code'])) {
            return $this->errorFormatter($reply['code']);
        }
        return false;
    }

    protected function errorFormatter($code)
    {
        return response()->json([
            'status' => config("plaid.code.$code.http"),
            'data' => [],
            'errors' => [config("plaid.code.$code.message")],
        ], config("plaid.code.$code.http"));
    }

    protected function successFormatter($reply)
    {
        return response()->json([
            'status' => 200,
            'data' => $reply,
            'errors' => [],
        ], 200);
    }
}
