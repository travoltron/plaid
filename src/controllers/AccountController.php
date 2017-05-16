<?php

namespace Travoltron\Plaid\Controllers;

use Illuminate\Http\Request;
use Travoltron\Plaid\Plaid;
use Illuminate\Routing\Controller as BaseController;
use Travoltron\Plaid\Requests\Accounts\GetIncomeRequest;
use Travoltron\Plaid\Requests\Accounts\GetAccountsRequest;

class AccountController extends BaseController
{
    public function getIncome(Request $request)
    {
        $incomeTokens = $this->isSkillable($request->header('uuid'), 'income');
        $income = collect($incomeTokens)->map(function($token) {
            return Plaid::getIncomeData($token)['income'];
        });

        return $this->successFormatter(['income' => $income]);
    }

    public function getInfo(Request $request)
    {
        $infoTokens = $this->isSkillable($request->header('uuid'), 'info');
        $info = collect($infoTokens)->map(function($token) {
            return Plaid::getInfoData($token)['info'];
        });

        return $this->successFormatter(['info' => $info]);
    }

    public function getRisk(Request $request)
    {
        $riskTokens = $this->isSkillable($request->header('uuid'), 'risk');
        $risk = collect($riskTokens)->map(function($token) {
            return Plaid::getRiskData($token);
        });

        return $this->successFormatter(['risk' => $risk]);
    }

    protected function isSkillable(string $uuid, string $skill)
    {
        switch ($skill) {
            case 'income':
                return config('plaid.accountModel')::where('uuid', $uuid)
                    ->where('plaidIncome', '1')->get()->map(function($account) {
                        return $account->token;
                    })->unique()->values()->toArray();
                break;
            case 'risk':
                return config('plaid.accountModel')::where('uuid', $uuid)
                    ->where('plaidRisk', '1')->get()->map(function($account) {
                        return $account->token;
                    })->unique()->values()->toArray();
                break;
            case 'info':
                return config('plaid.accountModel')::where('uuid', $uuid)
                    ->where('plaidInfo', '1')->get()->map(function($account) {
                        return $account->token;
                    })->unique()->values()->toArray();
                break;
            case 'auth':
                return config('plaid.accountModel')::where('uuid', $uuid)
                    ->where('plaidAuth', '1')->get()->map(function($account) {
                        return $account->token;
                    })->unique()->values()->toArray();
                break;
            case 'connect':
                return config('plaid.accountModel')::where('uuid', $uuid)
                    ->where('plaidConnect', '1')->get()->map(function($account) {
                        return $account->token;
                    })->unique()->values()->toArray();
                break;
            default:
                return;
                break;
        }

    }


    public function getAccounts(Request $request)
    {
        return config('plaid.accountModel')::where('uuid', $request->header('uuid'))->get()
            ->filter(function($account) {
                return $account->smartsave == 1;
            })
            ->map(function($account) {
                return [
                    'accountId' => $account->accountId,
                    'balance' => [
                        'current' => str_dollarsCents($account->balance)
                    ],
                    'name' => $account->institutionName,
                    'meta' => [
                        'name' => $account->accountName,
                        'number' => $account->last4
                    ],
                    'numbers' => [
                        'routing' => $account->routingNumber,
                        'account' => $account->accountNumber,
                    ],
                    'subtype' => ($account->type === 'checking' || $account->type === 'savings') ? $account->type : null,
                    'type' => ($account->type === 'checking' || $account->type === 'savings') ? 'depository' : $account->type,
                    'updatedAt' => $account->updated_at->diffForHumans()
                ];
            })->values();
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
        return response()->api($reply, 200);
    }
}
