<?php

namespace Travoltron\Plaid\Commands;

use Travoltron\Plaid\Plaid;
use Illuminate\Console\Command;

class UpdateBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plaid:balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update account balances';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tokens = collect(config('plaid.tokenModel')::get()->chunk(100))->unique('token');
        foreach($tokens as $token) {
            $token->each(function ($token) {
                $accessToken = $token->token;
                $uuid = $token->uuid;
                $user = \App\Models\User::uuid($uuid);
                // Skip for users that have closed an account
                if(!$user) {
                    \Log::info('Skipping for deleted user');
                    $token->delete();
                    return;
                }
                $accounts = Plaid::getConnectData($accessToken);
                // Get the accounts that need a relinking
                if (isset($accounts['message']) && ($accounts['message'] === 'mfa reset' || $accounts['message'] === 'invalid credentials')) {
                    $bankLock = $this->lookupBank($uuid);
                    // Don't throw the re-link if the account is locked, that's a different thing
                    if($bankLock['isLocked']) {
                        \Log::info($user->name() . ': Account locked.');
                        return;
                    }
                    $bankInfo = $this->bankInfo($uuid);
                    if(!$bankInfo) {
                        \Log::info($user->name() . ': Account info missing.');
                        return;
                    }
                    // Update the Plaid smartsave account to be on the correct batch
                    $accounts = config('plaid.accountModel')::where('uuid', $uuid)->where('last4', $bankInfo->last4)->get();
                    $smartsaver = $accounts->containsStrict(function($value, $key) {
                        return $value->smartsave === 1;
                    });
                    if($smartsaver && $accounts->count() > 1) {
                        $accounts->sortByDesc('batch')->first()->update(['smartsave' => true]);
                        $accounts->sortByDesc('batch')->last()->update(['smartsave' => false]);
                    }

                    if(config('plaid.notifyRelink')) {
                        \Redis::setex('plaidRelink_' . $uuid, 10 * 60, json_encode([\App\Models\User::uuid($uuid), $bankInfo->institutionName, $bankInfo->last4]));
                    }
                }
                if ( ! isset($accounts['accounts']) ) {
                    return;
                }

                collect($accounts['accounts'])->each(function ($account) use ($accessToken, $uuid) {
                    $accountId = ( starts_with($accessToken, 'test_') ) ? $uuid . '_' . $account['_id'] : $account['_id'];
                    config('plaid.accountModel')::where('accountId', $accountId)->update([
                        'balance' => ( isset($account['balance']['current']) ) ? $account['balance']['current'] : 0.00
                    ]);
                    if ( isset(config('plaid.accountModel')::where('accountId', $accountId)->where('uuid', $uuid)
                                                           ->first()->smartsave) && class_exists(\Investforward\Smartsave\Models\SmartsaveBalance::class) ) {
                        \Investforward\Smartsave\Models\SmartsaveBalance::create([
                            'uuid' => $uuid,
                            'accountId' => $accountId,
                            'balance' => $account['balance']['available'] ?? $account['balance']['current'] // failover to current balance if available (pending) isn't defined
                        ]);
                    }
                });
            });
        }

        $this->sendNotifications();

        $this->slack();
    }

    protected function sendNotifications()
    {
        foreach(\Redis::keys('plaidRelink_*') as $brokenLink) {
            $info = json_decode(\Redis::get($brokenLink), true);
            event(new \App\Events\Plaid\BankCredentialsCorrupted(\App\Models\User::uuid($info[0]['uuid']), $info[1], $info[2]));
        }
    }

    protected function slack()
    {
        \Slack::to('@ben')->attach([
            'fallback' => 'Updating Plaid balances',
            'color' => '#36a64f',
            'author_name' => 'Updating Plaid balances',
            'footer' => 'IF-API',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts' => \Carbon\Carbon::now()->timestamp
        ])->send();
    }

    public function bankInfo($uuid)
    {
        return config('plaid.accountModel')::where('uuid', $uuid)->where('smartsave', true)->first();
    }

    public function lookupBank($uuid)
    {
        $customerId = \App\Models\User::uuid($uuid)->coreproId ?? null;
        if(!$customerId) {
            return;
        }
        return collect(json_decode((new \Corepro)->request('GET', "externalAccount/list/$customerId")->getContent(), true)['data'])
            ->filter(function($account) {
                return $account['customField1'] === "external" && $account['isActive'];
            })->first();
    }

}
