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
        $accounts = collect(config('plaid.accountModel')::all());
        $oldAverage = $accounts->avg('balance');
        $tokens = collect(config('plaid.tokenModel')::all())->unique('token');
        $tokens->each(function($token) {
            $accessToken = $token->token;
            $uuid = $token->uuid;
            collect(Plaid::getConnectData($accessToken)['accounts'])->each(function($account) use ($accessToken, $uuid) {
                $accountId = (starts_with($accessToken, 'test_')) ? $uuid.'_'.$account['_id'] : $account['_id'];
                config('plaid.accountModel')::where('accountId', $accountId)->update(['balance' => $account['balance']['current']]);
                if(config('plaid.accountModel')::where('accountId', $accountId)->where('uuid', $uuid)->first()->smartsave && class_exists(\App\Models\SmartsaveBalance::class)) {
                    $saved = \App\Models\SmartsaveBalance::create([
                        'uuid' => $uuid,
                        'balance' => $account['balance']['available'] ?? $account['balance']['current'] // failover to current balance if available (pending) isn't defined
                    ]);
                }
            });
        });
        $newAverage = collect(config('plaid.accountModel')::all())->avg('balance');
        $common = config('plaid.accountModel')::all()->unique('institutionName')->pluck('institutionName')->map(function($name) {
            return [
                'name' => $name,
                'accounts' => config('plaid.accountModel')::where('institutionName', $name)->count()
            ];
        })->sortByDesc('accounts')->shift();
        \Slack::to('@ben')->attach([
            'fallback' => 'Updating Plaid balances',
            'color' => '#36a64f',
            'author_name' => 'Updating Plaid balances',
            'fields' => [
                [
                    'title' => 'Total number of linked accounts',
                    'value' => config('plaid.accountModel')::all()->count(),
                    'short' => true
                ],
                [
                    'title' => 'Most common bank',
                    'value' => $common['name'].': '.$common['accounts'].' accounts',
                    'short' => true
                ],
                [
                    'title' => 'Average account balance (yesterday)',
                    'value' => str_dollarsCents($oldAverage),
                    'short' => true
                ],
                [
                    'title' => 'Average account balance (today)',
                    'value' => str_dollarsCents($newAverage),
                    'short' => true
                ],
            ],
            'footer' => 'IF-API',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts' => \Carbon\Carbon::now()->timestamp
        ])->send();
    }
}
