<?php

Route::group(['prefix' => config('plaid.prefix')], function() {
    Route::post('webhook', 'Travoltron\Plaid\Controllers\WebhookController@incoming')->name('plaidWebhook');
    Route::group(['prefix' => 'account'], function() {
        // Users
        Route::post('add', 'Travoltron\Plaid\Controllers\AuthController@addAccount');
        Route::post('mfa', 'Travoltron\Plaid\Controllers\AuthController@mfaAccount');
        Route::put('update', 'Travoltron\Plaid\Controllers\AuthController@updateAccount');
        Route::put('upgrade', 'Travoltron\Plaid\Controllers\AuthController@upgradeAccount');
        Route::delete('delete', 'Travoltron\Plaid\Controllers\AuthController@deleteAccount');
    });
    Route::group(['prefix' => 'search'], function() {
        // Searching
        Route::post('name', 'Travoltron\Plaid\Controllers\SearchController@searchName');
        Route::post('product', 'Travoltron\Plaid\Controllers\SearchController@searchProduct');
        Route::post('routing', 'Travoltron\Plaid\Controllers\SearchController@routingNumber');
    });
    Route::group(['prefix' => 'get'], function() {
        // Transactions
        Route::get('transactions', 'Travoltron\Plaid\Controllers\SpendingController@allTransactions');
        Route::get('recent', 'Travoltron\Plaid\Controllers\SpendingController@recentTransactions');
        // Accounts
        Route::get('accounts', 'Travoltron\Plaid\Controllers\AccountController@getAccounts');
        // Income
        Route::get('income', 'Travoltron\Plaid\Controllers\AccountController@getIncome');

        Route::get('info', 'Travoltron\Plaid\Controllers\AccountController@getInfo');

        Route::get('risk', 'Travoltron\Plaid\Controllers\AccountController@getRisk');

    });


});
