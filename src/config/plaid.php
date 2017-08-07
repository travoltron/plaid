<?php

/**
 * Plaid specific information
 */

return [
    'baseUrl' => (env('APP_ENV') !== 'production') ? 'https://tartan.plaid.com/':'https://api.plaid.com/',
    'client_id' => (env('APP_ENV') !== 'production') ? 'test_id':env('PLAID_CLIENT_ID'),
    'secret' => (env('APP_ENV') !== 'production') ? 'test_secret':env('PLAID_SECRET'),
    'prefix' => 'plaid',
    'webhook' => (env('APP_ENV') === 'local') ? 'http://requestb.in/1dm3d8e1': 'http://requestb.in/1dm3d8e1',
    'slackChannel' => '@ben', // change this to whatever works for you.
    'stripFakes' => true, // this will remove the 'fake_institution' from being resolved in saving the accounts
    'autoupgrade' => true,
    'products' => [
        'auth',
        'connect',
        'creditdetails'
    ],
    'auth' => [
        'list' => false,
        'login_only' => true,
    ],
    'connect' => [
        'list' => false,
        'login_only' => true,
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
    'tokenModel' => \App\Models\PlaidToken::class,
    'accountModel' => \App\Models\PlaidAccount::class,
    'transactionModel' => \App\Models\PlaidTransaction::class,
    'code' => [
        '1000' => [
            'http' => 400,
            'reason' => 'access_token missing',
            'message' => 'You need to include the access_token that you received from the original submit call.'
        ],
        '1001' => [
            'http' => 400,
            'reason' => 'type missing',
            'message' => 'You need to include a type parameter. Ex. bofa, wells, amex, chase, citi, etc.'
        ],
        '1003' => [
            'http' => 400,
            'reason' => 'access_token disallowed',
            'message' => 'You included an access_token on a submit call - this is only allowed on step and get routes.'
        ],
        '1008' => [
            'http' => 400,
            'reason' => 'unsupported access_token',
            'message' => 'This access token format is no longer supported. Contact support to resolve.'
        ],
        '1004' => [
            'http' => 400,
            'reason' => 'invalid options format',
            'message' => 'Options need to be JSON or stringified JSON.'
        ],
        '1005' => [
            'http' => 400,
            'reason' => 'credentials missing',
            'message' => 'Provide username, password, and pin if appropriate.'
        ],
        '1006' => [
            'http' => 400,
            'reason' => 'invalid credentials format',
            'message' => 'Credentials need to be JSON or stringified JSON.'
        ],
        '1007' => [
            'http' => 400,
            'reason' => 'upgrade_to required',
            'message' => 'In order to upgrade an account, an upgrade_to field is required , ex. connect'
        ],
        '1009' => [
            'http' => 400,
            'reason' => 'invalid content-type',
            'message' => 'Valid "Content-Type" headers are "application/json" and "application/x-www-form-urlencoded" with an optional "UTF-8" charset.'
        ],
        '1100' => [
            'http' => 401,
            'reason' => 'client_id missing',
            'message' => 'Include your Client ID so we know who you are.'
        ],
        '1101' => [
            'http' => 401,
            'reason' => 'secret missing',
            'message' => 'Include your Secret so we can verify your identity.'
        ],
        '1102' => [
            'http' => 401,
            'reason' => 'secret or client_id invalid',
            'message' => 'The Client ID does not exist or the Secret does not match the Client ID you provided.'
        ],
        '1104' => [
            'http' => 401,
            'reason' => 'unauthorized product',
            'message' => 'Your Client ID does not have access to this product. Contact us to purchase this product.'
        ],
        '1105' => [
            'http' => 401,
            'reason' => 'bad access_token',
            'message' => 'This access_token appears to be corrupted.'
        ],
        '1106' => [
            'http' => 401,
            'reason' => 'bad public_token',
            'message' => 'This public_token is corrupt or does not exist in our database. See the Link docs.'
        ],
        '1107' => [
            'http' => 401,
            'reason' => 'missing public_token',
            'message' => 'Include the public_token received from the Plaid Link module. See the Link docs.'
        ],
        '1108' => [
            'http' => 401,
            'reason' => 'invalid type',
            'message' => 'This institution is not currently supported.'
        ],
        '1109' => [
            'http' => 401,
            'reason' => 'unauthorized product',
            'message' => 'The sandbox client_id and secret can only be used with sandbox credentials and access tokens. See Sandbox docs.'
        ],
        '1110' => [
            'http' => 401,
            'reason' => 'product not enabled',
            'message' => 'This product is not enabled for this item. Use the upgrade route to add it.'
        ],
        '1111' => [
            'http' => 401,
            'reason' => 'invalid upgrade',
            'message' => 'Specify a valid product to upgrade this item to.'
        ],
        '1112' => [
            'http' => 401,
            'reason' => 'addition limit exceeded',
            'message' => 'You have reached the maximum number of additions. Contact us to raise your limit.'
        ],
        '1113' => [
            'http' => 429,
            'reason' => 'rate limit exceeded',
            'message' => 'You have exceeded your request rate limit for this product. Try again soon.'
        ],
        '1114' => [
            'http' => 401,
            'reason' => 'unauthorized environment',
            'message' => 'Your Client ID is not authorized to access this API environment. Contact support@plaid.com to gain access.'
        ],
        '1115' => [
            'http' => 401,
            'reason' => 'product already enabled',
            'message' => 'The specified product is already enabled for this item. Call the corresponding product endpoint directly.'
        ],
        '1200' => [
            'http' => 402,
            'reason' => 'invalid credentials',
            'message' => 'The username or password provided were not correct.'
        ],
        '1201' => [
            'http' => 402,
            'reason' => 'invalid username',
            'message' => 'The username provided was not correct.'
        ],
        '1202' => [
            'http' => 402,
            'reason' => 'invalid password',
            'message' => 'The password provided was not correct.'
        ],
        '1203' => [
            'http' => 402,
            'reason' => 'invalid mfa',
            'message' => 'The MFA response provided was not correct.'
        ],
        '1204' => [
            'http' => 402,
            'reason' => 'invalid send_method',
            'message' => 'The MFA send_method provided was invalid. Consult the documentation for the proper format.'
        ],
        '1205' => [
            'http' => 402,
            'reason' => 'account locked',
            'message' => 'The account is locked. Prompt the user to visit the issuing institution\'s site and unlock their account.'
        ],
        '1206' => [
            'http' => 402,
            'reason' => 'account not setup',
            'message' => 'The account has not been fully set up. Prompt the user to visit the issuing institution\'s site and finish the setup process.'
        ],
        '1207' => [
            'http' => 402,
            'reason' => 'country not supported',
            'message' => 'We\'re United States-only at this point!'
        ],
        '1208' => [
            'http' => 402,
            'reason' => 'mfa not supported',
            'message' => 'This account requires a form of MFA that is not currently supported. Other accounts at this institution with a different form of MFA may be supported.'
        ],
        '1209' => [
            'http' => 402,
            'reason' => 'invalid pin',
            'message' => 'The pin provided was not correct.'
        ],
        '1210' => [
            'http' => 402,
            'reason' => 'account not supported',
            'message' => 'This account is currently not supported.'
        ],
        '1211' => [
            'http' => 402,
            'reason' => 'bofa account not supported',
            'message' => 'The security rules for this account restrict access. Disable "Extra Security at Sign-In" in your Bank of America settings.'
        ],
        '1212' => [
            'http' => 402,
            'reason' => 'no accounts',
            'message' => 'No valid accounts exist for this user.'
        ],
        '1213' => [
            'http' => 402,
            'reason' => 'invalid patch username',
            'message' => 'The username in a PATCH request must match the username provided in the initial add user POST request.'
        ],
        '1215' => [
            'http' => 402,
            'reason' => 'mfa reset',
            'message' => 'MFA access has changed or this application\'s access has been revoked. Submit a PATCH call to resolve.'
        ],
        '1218' => [
            'http' => 401,
            'reason' => 'mfa not required',
            'message' => 'This item does not require the MFA process at this time.'
        ],
        '1219' => [
            'http' => 402,
            'reason' => 'wells account not supported',
            'message' => 'The security rules for this account restrict access. Disable "Enhanced Sign On" in your Wells Fargo settings.'
        ],
        '1300' => [
            'http' => 404,
            'reason' => 'institution not available',
            'message' => 'This institution is not yet available in this environment.'
        ],
        '1301' => [
            'http' => 404,
            'reason' => 'unable to find institution',
            'message' => 'Double-check the provided institution ID.'
        ],
        '1302' => [
            'http' => 402,
            'reason' => 'institution not responding',
            'message' => 'The institution is failing to respond to our request, if you resubmit the query the request may go through.'
        ],
        '1303' => [
            'http' => 402,
            'reason' => 'institution down',
            'message' => 'The institution is down for an indeterminate amount of time, if you resubmit in a couple hours it may go through.'
        ],
        '1307' => [
            'http' => 402,
            'reason' => 'institution no longer supported',
            'message' => 'This institution is no longer supported by our longtail partner.'
        ],
        '1501' => [
            'http' => 404,
            'reason' => 'unable to find category',
            'message' => 'Double-check the provided category ID.'
        ],
        '1502' => [
            'http' => 400,
            'reason' => 'type required',
            'message' => 'You must include a type parameter.'
        ],
        '1503' => [
            'http' => 400,
            'reason' => 'invalid type',
            'message' => 'The specified type is not supported.'
        ],
        '1507' => [
            'http' => 400,
            'reason' => 'invalid date',
            'message' => 'Consult the documentation for valid date formats.'
        ],
        '1600' => [
            'http' => 404,
            'reason' => 'product not found',
            'message' => 'This product doesn\'t exist yet, we\'re actually not sure how you reached this error...'
        ],
        '1601' => [
            'http' => 404,
            'reason' => 'product not available',
            'message' => 'This product is not yet available for this institution.'
        ],
        '1605' => [
            'http' => 404,
            'reason' => 'user not found',
            'message' => 'User was previously deleted from our system.'
        ],
        '1606' => [
            'http' => 404,
            'reason' => 'account not found',
            'message' => 'The account ID provided was not correct.'
        ],
        '1610' => [
            'http' => 404,
            'reason' => 'item not found',
            'message' => 'No matching items found.'
        ],
        '1700' => [
            'http' => 501,
            'reason' => 'extractor error',
            'message' => 'We failed to pull the required information from the institution - make sure the user can access their account; we have been notified.'
        ],
        '1701' => [
            'http' => 502,
            'reason' => 'extractor error retry',
            'message' => 'We failed to pull the required information from the institution - resubmit this query.'
        ],
        '1702' => [
            'http' => 500,
            'reason' => 'plaid error',
            'message' => 'An unexpected error has occurred on our systems; we\'ve been notified and are looking into it!'
        ],
        '1800' => [
            'http' => 503,
            'reason' => 'planned maintenance',
            'message' => 'Portions of our system are down for maintenance. This route is inaccessible. GET requests to Auth and Connect may succeed.'
        ],
    ]
];
