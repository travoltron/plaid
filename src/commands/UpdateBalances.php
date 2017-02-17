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
        $accounts->each(function($account) {
            $data = Plaid::getConnectData($account->token);
            dd($data);
        });
        $newAverage = collect(config('plaid.accountModel')::all())->avg('balance');

        \Slack::to('@ben')->attach([
            'fallback' => 'Updating Plaid balances',
            'color' => '#36a64f',
            'author_name' => 'Updating Plaid balances',
            'fields' => [
                [
                    'title' => 'Average account balance (yesterday)',
                    'value' => $oldAverage,
                    'short' => true
                ],
                [
                    'title' => 'Average account balance (today)',
                    'value' => $newAverage,
                    'short' => true
                ],
            ],
            'footer' => 'IF-API',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts' => \Carbon\Carbon::now()->timestamp
        ])->send();
    }
}
