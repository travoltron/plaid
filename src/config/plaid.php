<?php

/**
 * Plaid specific information
 */

return [
    'baseUrl' => (env('APP_ENV') !== 'production'?'https://tartan.plaid.com/':'https://api.plaid.com/'),
    'client_id' => (env('APP_ENV') !== 'production'?'test_id':env('PLAID_CLIENT_ID')),
    'secret' => (env('APP_ENV') !== 'production'?'test_secret':env('PLAID_SECRET')),
    'auth' => [
        'list' => false,
        'login_only' => false,
    ],
    'connect' => [
        'list' => false,
        'login_only' => false,
        'pending' => true,
        'start_date' => 30,
        'end_date' => 0
    ],
    'income' => [
        'list' => false
    ],
    'risk' => [
        'list' => false
    ],
];
