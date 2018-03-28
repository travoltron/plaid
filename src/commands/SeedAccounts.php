<?php

namespace Travoltron\Plaid\Commands;

use Travoltron\Plaid\Plaid;
use Illuminate\Console\Command;

class SeedAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plaid:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds testing accounts';

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
        // Make dummy users to test against
        \Artisan::call('db:seed');
        \App\Models\User::all()->take(5)->each(function($user) {
            \App\Models\PlaidToken::firstOrCreate([
                'uuid' => $user->uuid,
                'token' => 'test_wells'
            ]);
            \App\Models\PlaidAccount::firstOrCreate([
                'uuid' => $user->uuid,
                'token' => 'test_wells',
                'institutionName' => 'Wells Fargo (seed fake)',
                'accountName' => 'Obvious Fake Account',
                'accountId' => str_random(15),
                'type' => 'checking',
                'accountNumber' => mt_rand(1500,5000),
                'routingNumber' => '022000020',
                'balance' => mt_rand(45, 5600),
                'smartsave' => 1,
            ]);
        });
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
