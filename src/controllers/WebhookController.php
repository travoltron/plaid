<?php

namespace Travoltron\Plaid\Controllers;

use Travoltron\Plaid\Plaid;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;


class WebhookController extends BaseController
{
    public function incoming(Request $request)
    {
        \Log::info($request);
        $this->slackMessage($request->input('message'));
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
