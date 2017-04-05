<?php

namespace Travoltron\Plaid\Controllers;

use Illuminate\Http\Request;
use Travoltron\Plaid\Plaid;
use Illuminate\Routing\Controller as BaseController;
use Travoltron\Plaid\Requests\Auth\AddAccountRequest;
use Travoltron\Plaid\Requests\Auth\MfaAccountRequest;
use Travoltron\Plaid\Requests\Auth\UpgradeAccountRequest;
use Travoltron\Plaid\Requests\Auth\UpdateAccountRequest;

class AuthController extends BaseController
{
    public function addAccount(AddAccountRequest $request)
    {
        if($this->authable($request->input('type'))) {
            $auth = Plaid::addAuthUser($request->input('username'), $request->input('password'), $request->input('pin', null), $request->input('type'));
        } else {
            $auth = Plaid::addConnectUser($request->input('username'), $request->input('password'), $request->input('pin', null), $request->input('type'), null);
        }
        if($this->needsMfa($auth)) {
            return $this->needsMfa($auth);
        }
        if($this->hasError($auth)) {
            return $this->hasError($auth);
        }
        if(config('plaid.autoupgrade')) {
            $this->upgradeTo(['all'], $auth['access_token']);
        }
        // Stash the token
        $this->storeToken($request->header('uuid'), $auth['access_token']);
        // Store the accounts
        $this->storeAccounts($request->header('uuid'), $auth, $request->input('type'));
        return $this->successFormatter($request->header('uuid'));
    }

    public function mfaAccount(MfaAccountRequest $request)
    {
        if($this->authable($request->input('type'))) {
            $auth = Plaid::authMfa($request->input('mfaCode'), $request->input('token'));
        }
        if(!$this->authable($request->input('type'))) {
            $auth = Plaid::connectMfa($request->input('mfaCode'), $request->input('token'));
        }
        if($this->needsMfa($auth)) {
            return $this->needsMfa($auth);
        }
        if($this->hasError($auth)) {
            return $this->hasError($auth);
        }
        if(config('plaid.autoupgrade')) {
            $this->upgradeTo(['all'], $auth['access_token']);
        }
        $this->storeToken($request->header('uuid'), $auth['access_token']);
        $this->storeAccounts($request->header('uuid'), $auth, $request->input('type'));
        return $this->successFormatter($request->header('uuid'));
    }

    public function upgradeAccount(UpgradeAccountRequest $request)
    {
        $this->upgradeTo($request->input('products'), $request->input('token'));
    }

    public function updateAccount(UpdateAccountRequest $request)
    {
        $acct = config('plaid.accountModel')::where('accountId', $request->input('accountId'))->first();
        if($acct->accountNumber !== null || $acct->routingNumber !== null) {
            return response()->api(['message' => 'Account and routing numbers already set.'], 400);
        }
        $acct->accountNumber = $request->input('accountNumber');
        $acct->routingNumber = $request->input('routingNumber');
        // Since intent is being taken, we'll enable SmartSave here
        $acct->smartsave = true;
        $acct->save();
        return $this->successFormatter($request->header('uuid'));
    }

    public function deleteAccount(Request $request)
    {
        //
    }

    protected function storeToken(string $uuid, string $token)
    {
        $savedToken = config('plaid.tokenModel')::create([
            'uuid' => $uuid,
            'token' => $token
        ]);
    }

    protected function storeAccounts(string $uuid, $data, string $type)
    {
        $batch = config('plaid.tokenModel')::where('uuid', $uuid)->get()->count();
        $extraInfo = (config('plaid.stripFakes') && env('PLAID_STATUS') !== 'live') ? collect(Plaid::searchId(str_replace('test_', '', $data['access_token'])))->toArray() : collect(Plaid::searchId($data['accounts'][0]['institution_type']))->toArray();
        $search = collect(Plaid::search($extraInfo['name']));
        $lookup = $search->search(function($bank) use ($extraInfo) {
            return $bank['name'] === $extraInfo['name'];
        });
        $logo = $search->toArray()[$lookup]['logo'];
        $savedAccounts = collect($data['accounts'])->each(function($account) use ($uuid, $extraInfo, $logo, $data, $batch) {
            $savedAccount = config('plaid.accountModel')::firstOrNew([
                'uuid'            => $uuid,
                'last4'           => $account['meta']['number'],
                'accountName'     => $account['meta']['name'],
                'accountNumber'   => $account['numbers']['account'] ?? null,
                'routingNumber'   => $account['numbers']['routing'] ?? null,
            ]);
            $savedAccount->token           = $data['access_token'];
            $savedAccount->institutionName = $extraInfo['name'];
            $savedAccount->logo            = $logo;
            $savedAccount->accountId       = (env('PLAID_STATUS') !== 'live') ? $uuid.'_'.$account['_id'] : $account['_id'];
            $savedAccount->type            = ($account['type'] === 'depository') ? $account['subtype'] : $account['type'];
            $savedAccount->balance         = $account['balance']['current'];
            $savedAccount->spendingLimit   = 0;
            $savedAccount->apr             = 0;
            $savedAccount->minimumPayment  = 0;
            $savedAccount->batch           = $batch;
            $savedAccount->smartsave       = false;
            $savedAccount->plaidAuth       = (in_array('auth', $extraInfo['products'])) ? true : false;
            $savedAccount->plaidConnect    = (in_array('connect', $extraInfo['products'])) ? true : false;
            $savedAccount->plaidIncome     = (in_array('income', $extraInfo['products'])) ? true : false;
            $savedAccount->plaidInfo       = (in_array('info', $extraInfo['products'])) ? true : false;
            $savedAccount->plaidRisk       = (in_array('risk', $extraInfo['products'])) ? true : false;
            $savedAccount->save();
        });

        $creditDetails = Plaid::creditDetails($data['access_token']);
        if (isset($creditDetails['accounts'])) {
            collect($creditDetails['accounts'])->each(function($account) use ($uuid, $batch) {
                $spendingLimit = $account['meta']['limit'] ?? 0.00;
                $apr =  array_key_exists('creditDetails', $account['meta']) ? $account['meta']['creditDetails']['aprs']['purchases']['apr'] * 100 : 0.00;
                $minimumPayment = ($account['meta']['creditDetails']['minimumPaymentAmount'] ?? 0.00);
                $account = (env('PLAID_STATUS') !== 'live') ?
                    config('plaid.accountModel')::firstOrNew([
                        'accountId' => $uuid.'_'.$account['_id'],
                        'batch' => $batch
                    ]) :
                    config('plaid.accountModel')::firstOrNew([
                        'accountId' => $account['_id'],
                        'batch' => $batch
                    ]);
                $account->spendingLimit  = $spendingLimit;
                $account->apr            = $apr;
                $account->minimumPayment = $minimumPayment;
                $account->save();

            });
        }
    }

    protected function needsMfa($reply)
    {
        if(isset($reply['mfa'])) {
            return response()->json([
                'status' => 201,
                'data' => $reply,
                'errors' => [],
            ], 201);
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

    protected function upgradeTo(array $product, $token)
    {
        $all = ['auth', 'connect', 'creditdetails', 'income', 'info', 'risk'];
        $products = collect($product);
        if($products->contains('all')) {
            $upgrade = collect($all)->map(function($product) use ($token) {
                $webhook = ($product === 'connect') ? config('plaid.webhook') : null;
                return Plaid::upgrade($token, $product, $webhook);
            });
        }
        if(!$products->contains('all')) {
            $upgrade = collect($products)->map(function($product) use ($token) {
                return Plaid::upgrade($token, $product, config('plaid.webhook'));
            });
        }
        return;
    }

    protected function errorFormatter($code)
    {
        return response()->json([
            'status' => config("plaid.code.$code.http"),
            'data' => [],
            'errors' => [config("plaid.code.$code.message")],
        ], config("plaid.code.$code.http"));
    }

    protected function successFormatter(string $uuid)
    {
        $accounts = config('plaid.accountModel')::where('uuid', $uuid)->get()->filter(function($account) use ($uuid) {
            return $account->batch == config('plaid.tokenModel')::where('uuid', $uuid)->get()->count();
        })->map(function($account) {
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
                'type' => ($account->type === 'checking' || $account->type === 'savings') ? 'depository' : $account->type
            ];
        })->values();

        return response()->api(['accounts' => $accounts], 200);

    }
}
