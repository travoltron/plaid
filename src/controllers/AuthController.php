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
            $auth = Plaid::addConnectUser($request->input('username'), $request->input('password'), $request->input('pin', null), $request->input('type'), 'http://requestb.in/p4mqsrp4');
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
        return $this->successFormatter($auth);
    }

    public function upgradeAccount(UpgradeAccountRequest $request)
    {
        $this->upgradeTo($request->input('products'), $request->input('token'));
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
                return Plaid::upgrade($token, $product, 'http://requestb.in/p4mqsrp4');
            });
        }
        if(!$products->contains('all')) {
            $upgrade = collect($products)->map(function($product) use ($token) {
                return Plaid::upgrade($token, $product, 'http://requestb.in/p4mqsrp4');
            });
        }

        return 'nope';
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
