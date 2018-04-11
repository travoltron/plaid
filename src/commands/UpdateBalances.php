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
                try {
                    $accounts = Plaid::getConnectData($accessToken);
                } catch (\Exception $e) {
                    \Log::info($e);
                    return;
                }
                // Get the accounts that need a relinking
                if (isset($accounts['message']) && ($accounts['message'] === 'mfa reset' || $accounts['message'] === 'invalid credentials')) {
                    $bankLock = $this->lookupBank($uuid);
                    $user = \App\Models\User::uuid($uuid) ?? null;
                    // Don't throw the re-link if the account is locked, that's a different thing
                    if($bankLock['isLocked']) {
                        \Log::info(($user) ? $user->name() . ': Account locked.' : 'Account locked, missing name.');
                        return;
                    }
                    $bankInfo = $this->bankInfo($uuid);
                    if(!$bankInfo) {
                        \Log::info(($user) ? $user->name() . ': Account info missing.' : 'Account info missing, missing name.');
                        return;
                    }
                    if(config('plaid.notifyRelink')) {
                        event(new \App\Events\Plaid\BankCredentialsCorrupted(\App\Models\User::uuid($uuid), $bankInfo->institutionName, $bankInfo->last4));
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
                        $saved = \Investforward\Smartsave\Models\SmartsaveBalance::create([
                            'uuid' => $uuid,
                            'accountId' => $accountId,
                            'balance' => $account['balance']['available'] ?? $account['balance']['current'] // failover to current balance if available (pending) isn't defined
                        ]);
                    }
                });
            });
        }
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
