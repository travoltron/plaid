<?php

namespace Travoltron\Plaid\Commands;

use Carbon\Carbon;
use Travoltron\Plaid\Plaid;
use Illuminate\Console\Command;

class UpdateBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plaid:balances  {--uuid=}';

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
        if($this->option('uuid')) {
            if(!\App\Models\User::uuid($this->option('uuid'))) {
                return;
            }
            $tokens = config('plaid.accountModel')::where('uuid', $this->option('uuid'))->get()->chunk(100);
        }
        if(!$this->option('uuid')) {
            $tokens = config('plaid.accountModel')::where('smartsave', '=', 1)->get()->unique(function ($account) {
                return $account->last4 . $account->institutionName . $account->type;
            });
        }
        foreach($tokens as $token) {
            $token->each(function ($token) {
                // Since this lookup updates everything, we'll skip subsequent requests
                if($token->updated_at->isSameDay(Carbon::now())) {
                    \Log::info('This account has already been updated.');
                    return;
                }
                try {
                    $accessToken = $token->token;
                } catch (\Exception $e) {
                    \Log::info('Payload decryption error.');
                    return;
                }
                $uuid = $token->uuid;
                $user = \App\Models\User::uuid($uuid);
                // Skip for users that have closed an account
                if(!$user) {
                    \Log::info('Skipping for deleted user');
                    $token->delete();
                    return;
                }

                $accounts = Plaid::getConnectData($accessToken);
                $bankInfo = $this->bankInfo($uuid);
                $bankLock = $this->lookupBank($uuid);

                // Get the accounts that need a relinking
                if (isset($accounts['message']) && ($accounts['message'] === 'mfa reset' || $accounts['message'] === 'invalid credentials')) {

                    // Don't throw the re-link if the account is locked, that's a different thing
                    if($bankLock['isLocked']) {
                        \Log::info($user->name() . ': Account locked at Corepro.');
                        return;
                    }

                    if(!$bankInfo) {
                        \Log::info($user->name() . ': Account info missing, likely never finished MFA or unsupported at Plaid.');
                        return;
                    }
                    // Update the Plaid smartsave account to be on the correct batch
                    $linkedAccounts = config('plaid.accountModel')::where('uuid', $uuid)->where('last4', $bankInfo->last4)->get();
                    $smartsaver = $linkedAccounts->containsStrict(function($value, $key) {
                        return $value->smartsave === 1;
                    });
                    if(!$smartsaver) {
                        \Log::info($user->name() . ' never selected an account to use.');
                        return;
                    }
                    if($smartsaver && $linkedAccounts->count() > 1) {
                        // Update the first linked to be false
                        $first = $linkedAccounts->sortByDesc('batch')->first();
                        $first->timestamps = false;
                        $first->smartsave = true;
                        $first->save();
                        // Update the last linked to be true
                        $last = $linkedAccounts->sortByDesc('batch')->last();
                        $last->timestamps = false;
                        $last->smartsave = false;
                        $last->save();
                    }

                    if(config('plaid.notifyRelink')) {
                        \Redis::setex('plaidRelink_' . $uuid, 10 * 60, json_encode([\App\Models\User::uuid($uuid), $bankInfo->institutionName, $bankInfo->last4]));
                    }
                }
                if ( ! isset($accounts['accounts']) ) {
                    \Log::info($user->name() . ': Plaid did not update.');
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
            \Log::info('Notifying ' . \App\Models\User::uuid($info[0]['uuid'])->name() . ' of broken Plaid link.');
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
        return config('plaid.accountModel')::where('uuid', $uuid)->where('smartsave', true)->orderByDesc('batch')->first();
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
