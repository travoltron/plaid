<?php

namespace Travoltron\Plaid\Controllers;

use \Carbon\Carbon;
use Travoltron\Plaid\Plaid;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;


class WebhookController extends BaseController
{
    public function incoming(Request $request)
    {
        $this->slackMessage($request->input('message'));
        switch ($request->input('code')) {
            case '0':
            case '1':
            case '2':
                $this->saveTransactions($request);
                break;
            case '3':
                $this->removeTransactions($request);
                break;
            default:
                $this->otherRequests($request);
                break;
        }
    }

    protected function saveTransactions(Request $request)
    {
        switch ($request->input('code')) {
            case '0':
            case '1':
                $startDate = Carbon::now()->subDays((app()->environment('local')) ? 1200 : 365)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
                break;
            case '2':
                $startDate = Carbon::now()->subDays(2)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
                break;
        }
        $uuid = $this->getUuidFromToken($request->input('access_token'));
        $transactions = collect(Plaid::getConnectTransactions($request->input('access_token'), $startDate, $endDate))->each(function($transaction) use ($uuid) {
            config('plaid.transactionModel')::firstOrCreate([
                'uuid' => $uuid,
                'accountId' => (app()->environment('local')) ? $uuid.'_'.$transaction['_account'] : $transaction['_account'],
                'transactionId' => $transaction['_id'],
                'amount' => $transaction['amount'],
                'date' => $transaction['date'],
                'name' => $transaction['name'],
                'categoryId' => $transaction['category_id'] ?? null,
                'meta' => json_encode($transaction['meta']),
            ]);
        });
    }

    protected function getUuidFromToken(string $token)
    {
        return config('plaid.tokenModel')::where('token', $token)->first()->uuid;
    }

    protected function removeTransactions(Request $request)
    {
        collect($request->input('removed_transactions'))->each(function($transaction) {
            config('plaid.transactionModel')::where('transactionId', $transaction)->delete();
        });
    }

    protected function otherRequests(Request $request)
    {
        //
    }

    protected function slackMessage($message)
    {
        \Slack::to(config('plaid.slackChannel'))->attach([
            'fallback' => $message,
            'color' => '#0e304b',
            'author_name' => $message,
            'footer' => 'IF-API',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts' => \Carbon\Carbon::now()->timestamp
        ])->send();
    }

}
