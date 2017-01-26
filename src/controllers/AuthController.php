<?php

namespace Travoltron\Plaid\Controllers;

use Illuminate\Http\Request;
use Travoltron\Plaid\Plaid;
use Illuminate\Routing\Controller as BaseController;
use Travoltron\Plaid\Requests\Auth\AddAccountRequest;
use Travoltron\Plaid\Requests\Auth\MfaAccountRequest;
use Travoltron\Plaid\Requests\Auth\UpgradeAccountRequest;

class AuthController extends BaseController
{
    public function addAccount(AddAccountRequest $request)
    {
        if($this->authable($request->input('type'))) {
            $auth = Plaid::addAuthUser($request->input('username'), $request->input('password'), $request->input('pin', null), $request->input('type'));
        } else {
            $auth = Plaid::addConnectUser($request->input('username'), $request->input('password'), $request->input('pin', null), $request->input('type'), config('plaid.webhook'));
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
        $this->storeAccounts($request->header('uuid'), $auth['access_token'], $request->input('type'));
        return $this->successFormatter($auth);
    }

    public function mfaAccount(MfaAccountRequest $request)
    {
        if($this->authable($request->input('type'))) {
            $auth = Plaid::authMfa($request->input('mfaCode'), $request->input('token'));
        } else {
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
        $this->storeAccounts($request->header('uuid'), $auth['access_token'], $request->input('type'));
        return $this->successFormatter($auth);
    }

    public function upgradeAccount(UpgradeAccountRequest $request)
    {
        $this->upgradeTo($request->input('products'), $request->input('token'));
    }

    protected function storeToken(string $uuid, string $token)
    {
        $savedToken = config('plaid.tokenModel')::create([
            'uuid' => $uuid,
            'token' => $token
        ]);
    }

    protected function storeAccounts(string $uuid, string $token, string $type)
    {
        $accounts = Plaid::creditDetails($token);

        $extraInfo = (config('plaid.stripFakes')) ? collect(Plaid::searchId(str_replace('test_', '', $accounts['access_token'])))->toArray() : collect(Plaid::searchId($accounts['access_token']))->toArray();
        $search = collect(Plaid::search($extraInfo['name']));
        $lookup = $search->search(function($bank) use ($extraInfo) {
            return $bank['name'] === $extraInfo['name'];
        });
        $logo = $search->toArray()[$lookup]['logo'];
        $savedAccounts = collect($accounts['accounts'])->each(function($account) use ($uuid, $extraInfo, $logo) {
            $spendingLimit = $account['meta']['limit'] ?? 0.00;
            $apr =  array_key_exists('creditDetails', $account['meta']) ? $account['meta']['creditDetails']['aprs']['purchases']['apr'] * 100 : 0.00;
            $minimumPayment = ($account['meta']['creditDetails']['minimumPaymentAmount'] ?? 0.00);
            config('plaid.accountModel')::create([
                'uuid' => $uuid,
                'institutionName' => $extraInfo['name'],
                'logo' => $logo,
                'accountName' => $account['meta']['name'],
                'accountId' => $account['_id'],
                'last4' => $account['meta']['number'],
                'type' => ($account['type'] === 'depository') ? $account['subtype'] : $account['type'],
                'accountNumber' => null,
                'routingNumber' => null,
                'balance' => $account['balance']['current'],
                'spendingLimit' => $spendingLimit,
                'apr' => $apr,
                'minimumPayment' => $minimumPayment,
                'smartsave' => false,
                'plaidAuth' => (in_array('auth', $extraInfo['products'])) ? true : false,
                'plaidConnect' => (in_array('connect', $extraInfo['products'])) ? true : false,
                'plaidIncome' => (in_array('income', $extraInfo['products'])) ? true : false,
                'plaidInfo' => (in_array('info', $extraInfo['products'])) ? true : false,
                'plaidRisk' => (in_array('risk', $extraInfo['products'])) ? true : false,
            ]);
        });

        if($this->authable($type)) {
            $authData = Plaid::getAuthData($token);
            $updatedAcct = collect($authData['accounts'])->each(function($acct) {
                $account = config('plaid.accountModel')::where('accountId', $acct['_id'])->first();
                $account->accountNumber = $acct['numbers']['account'] ?? null;
                $account->routingNumber = $acct['numbers']['routing'] ?? null;
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
                return Plaid::upgrade($token, $product, config('plaid.webhook'));
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

    protected function successFormatter($auth)
    {
        return response()->json([
            'status' => 200,
            'data' => $auth,
            'errors' => [],
        ], 200);
    }
}
