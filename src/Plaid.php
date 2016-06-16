<?php namespace Travoltron\Plaid;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Post\PostBodyInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class Plaid
{
    /**
     * Initialize the Guzzle Client and make it ready for all requests
     */
    public static function client()
    {
        return new Guzzle(['base_url' => config('plaid.baseUrl')]);
    }

    //////////
    // Auth //
    //////////

    /**
     * [addAuthUser description]
     * @method addAuthUser
     * @param  [type]      $username [description]
     * @param  [type]      $password [description]
     * @param  [type]      $pin      [description]
     * @param  [type]      $type     [description]
     */
    public static function addAuthUser($username, $password, $pin = null, $type)
    {
        try {
            $request = self::client()->post('auth', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.auth.list')
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [authMfa description]
     * @method authMfa
     * @param  [type]  $mfa         [description]
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $method      [description]
     * @return [type]               [description]
     */
    public static function authMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('auth/step', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method,
                        'login_only' => config('plaid.auth.login_only'),
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [getAuthData description]
     * @method getAuthData
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getAuthData($plaid_token)
    {
        try {
            $request = self::client()->post('auth/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [updateAuthUser description]
     * @method updateAuthUser
     * @param  [type]         $username    [description]
     * @param  [type]         $password    [description]
     * @param  [type]         $pin         [description]
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function updateAuthUser($username, $password, $pin = null, $plaid_token)
    {
        try {
            $request = self::client()->patch('auth', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [deleteAuthUser description]
     * @method deleteAuthUser
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function deleteAuthUser($plaid_token)
    {
        try {
            $request = self::client()->delete('auth', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /////////////
    // Connect //
    /////////////

    /**
     * [addConnectUser description]
     * @method addConnectUser
     * @param  [type]         $username [description]
     * @param  [type]         $password [description]
     * @param  [type]         $pin      [description]
     * @param  [type]         $type     [description]
     * @param  string          $options  [description]
     */
    public static function addConnectUser($username, $password, $pin = null, $type, $webhook, $start_date = null, $end_date = null)
    {
        $start = ($start_date ? Carbon::parse($start_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.start_date'))->toDateString());
        $end = ($end_date ? Carbon::parse($end_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.end_date'))->toDateString());
        try {
            $request = self::client()->post('connect', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'login_only' => config('plaid.connect.login_only'),
                        'webhook' => $webhook,
                        'pending' => config('plaid.connect.pending'),
                        'list' => config('plaid.connect.list'),
                        'gte' => $start,
                        'lte' => $end
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [connectMfa description]
     * @method connectMfa
     * @param  [type]     $mfa         [description]
     * @param  [type]     $plaid_token [description]
     * @param  [type]     $method      [description]
     * @return [type]                  [description]
     */
    public static function connectMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('connect/step', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [updateConnectUser description]
     * @method updateConnectUser
     * @param  [type]            $username    [description]
     * @param  [type]            $password    [description]
     * @param  [type]            $pin         [description]
     * @param  [type]            $plaid_token [description]
     * @param  [type]            $webhook     [description]
     * @return [type]                         [description]
     */
    public static function updateConnectUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('connect', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    public static function getConnectData($plaid_token, $start_date = null, $end_date = null)
    {
        $start = ($start_date ? Carbon::parse($start_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.start_date'))->toDateString());
        $end = ($end_date ? Carbon::parse($end_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.end_date'))->toDateString());
        try {
            $request = self::client()->post('connect/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                    'options' => [
                        'pending' => config('plaid.connect.pending'),
                        'gte' => $start,
                        'lte' => $end
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [deleteConnectUser description]
     * @method deleteConnectUser
     * @param  [type]            $plaid_token [description]
     * @return [type]                         [description]
     */
    public static function deleteConnectUser($plaid_token)
    {
        try {
            $request = self::client()->delete('connect', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [getAccounts description]
     * @method getAccounts
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getConnectAccounts($plaid_token)
    {
        try {
            $request = self::client()->post('connect/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return $request->json()['accounts'];
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [getTransactions description]
     * @method getTransactions
     * @param  [type]          $plaid_token [description]
     * @return [type]                       [description]
     */
    public static function getConnectTransactions($plaid_token, $start_date = null, $end_date = null)
    {
        $start = ($start_date ? Carbon::parse($start_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.start_date'))->toDateString());
        $end = ($end_date ? Carbon::parse($end_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.end_date'))->toDateString());
        try {
            $request = self::client()->post('connect/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                    'options' => [
                        'pending' => config('plaid.connect.pending'),
                        'gte' => $start,
                        'lte' => $end
                    ]
                ]
            ]);
            return $request->json()['transactions'];
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    //////////
    // Info //
    //////////

    /**
     * [addInfoUser description]
     * @method addInfoUser
     * @param  [type]      $username [description]
     * @param  [type]      $password [description]
     * @param  [type]      $pin      [description]
     * @param  [type]      $type     [description]
     * @param  [type]      $webhook  [description]
     */
    public static function addInfoUser($username, $password, $pin = null, $type, $webhook)
    {
        try {
            $request = self::client()->post('info', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.connect.list'),
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [infoMfa description]
     * @method infoMfa
     * @param  [type]  $mfa         [description]
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $method      [description]
     * @return [type]               [description]
     */
    public static function infoMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('info/step', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [updateInfoUser description]
     * @method updateInfoUser
     * @param  [type]         $username    [description]
     * @param  [type]         $password    [description]
     * @param  [type]         $pin         [description]
     * @param  [type]         $plaid_token [description]
     * @param  [type]         $webhook     [description]
     * @return [type]                      [description]
     */
    public static function updateInfoUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('info', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [deleteInfoUser description]
     * @method deleteInfoUser
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function deleteInfoUser($plaid_token)
    {
        try {
            $request = self::client()->delete('info', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [getInfoData description]
     * @method getInfoData
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getInfoData($plaid_token)
    {
        try {
            $request = self::client()->post('info/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    ////////////
    // Income //
    ////////////

    /**
     * [addIncomeUser description]
     * @method addIncomeUser
     * @param  [type]        $username [description]
     * @param  [type]        $password [description]
     * @param  [type]        $pin      [description]
     * @param  [type]        $type     [description]
     * @param  [type]        $webhook  [description]
     */
    public static function addIncomeUser($username, $password, $pin = null, $type, $webhook)
    {
        try {
            $request = self::client()->post('income', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.income.list'),
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [incomeMfa description]
     * @method incomeMfa
     * @param  [type]    $mfa         [description]
     * @param  [type]    $plaid_token [description]
     * @param  [type]    $method      [description]
     * @return [type]                 [description]
     */
    public static function incomeMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('income/step', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [updateIncomeUser description]
     * @method updateIncomeUser
     * @param  [type]           $username    [description]
     * @param  [type]           $password    [description]
     * @param  [type]           $pin         [description]
     * @param  [type]           $plaid_token [description]
     * @param  [type]           $webhook     [description]
     * @return [type]                        [description]
     */
    public static function updateIncomeUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('income', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [deleteIncomeUser description]
     * @method deleteIncomeUser
     * @param  [type]           $plaid_token [description]
     * @return [type]                        [description]
     */
    public static function deleteIncomeUser($plaid_token)
    {
        try {
            $request = self::client()->delete('income', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [getIncomeData description]
     * @method getIncomeData
     * @param  [type]        $plaid_token [description]
     * @return [type]                     [description]
     */
    public static function getIncomeData($plaid_token)
    {
        try {
            $request = self::client()->post('income/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    //////////
    // Risk //
    //////////

    /**
     * [addRiskUser description]
     * @method addRiskUser
     * @param  [type]      $username [description]
     * @param  [type]      $password [description]
     * @param  [type]      $pin      [description]
     * @param  [type]      $type     [description]
     * @param  [type]      $webhook  [description]
     */
    public static function addRiskUser($username, $password, $pin = null, $type, $webhook)
    {
        try {
            $request = self::client()->post('risk', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.risk.list'),
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [riskMfa description]
     * @method riskMfa
     * @param  [type]  $mfa         [description]
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $method      [description]
     * @return [type]               [description]
     */
    public static function riskMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('risk/step', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [updateRiskUser description]
     * @method updateRiskUser
     * @param  [type]         $username    [description]
     * @param  [type]         $password    [description]
     * @param  [type]         $pin         [description]
     * @param  [type]         $plaid_token [description]
     * @param  [type]         $webhook     [description]
     * @return [type]                      [description]
     */
    public static function updateRiskUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('risk', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [deleteRiskUser description]
     * @method deleteRiskUser
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function deleteRiskUser($plaid_token)
    {
        try {
            $request = self::client()->delete('risk', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /**
     * [getRiskData description]
     * @method getRiskData
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getRiskData($plaid_token)
    {
        try {
            $request = self::client()->post('risk/get', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /////////////
    // Balance //
    /////////////

    /**
     * [getBalances description]
     * @method getBalances
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getBalances($plaid_token)
    {
        try {
            $request = self::client()->post('balance', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    /////////////
    // Upgrade //
    /////////////

    /**
     * [upgrade description]
     * @method upgrade
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $webhook     [description]
     * @return [type]               [description]
     */
    public static function upgrade($plaid_token, $upgrade_to, $webhook = null)
    {
        try {
            $request = self::client()->post('upgrade', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                    'upgrade_to' => $upgrade_to,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    ///////////////////////////
    // Longtail Institutions //
    ///////////////////////////

    public static function longtail($count = 50, $offset = 0)
    {
        try {
            $request = self::client()->post('institutions/longtail', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'count' => $count,
                    'offset' => $offset
                ]
            ]);
            return $request->getBody();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    ////////////
    // Search //
    ////////////

    public static function search($query, $product = null, $institution_id = null)
    {
        $queryArray = [
            'q' => $query,
            'p' => $product,
            'id' => $institution_id
        ];
        if(!$product) {
            unset($queryArray['p']);
        }
        if(!$institution_id) {
            unset($queryArray['id']);
        }
        try {
            $request = self::client()->get('institutions/search', [
                'query' => $queryArray
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    ////////////////
    // Categories //
    ////////////////

    /**
     * [$id description]
     * @var [type]
     */
    public static function categories($category_id = null)
    {
        $endpoint = ($category_id)?'/'.$category_id:'';
        try {
            $request = self::client()->get('categories'.$endpoint);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

    //////////
    // Link //
    //////////

    /**
     * [exchangeToken description]
     * @method exchangeToken
     * @param  [type]        $public_token [description]
     * @return [type]                      [description]
     */
    public static function exchangeToken($public_token)
    {
        try {
            $request = self::client()->post('exchange_token', [
                'body' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'public_token' => $public_token,
                ]
            ]);
            return $request->json();
        } catch (RequestException $e) {
            return $e->getResponse()->json();
        }
    }

}
