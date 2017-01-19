<?php

Route::group(['prefix' => config('plaid.prefix')], function() {
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
    });
    Route::group(['prefix' => 'get'], function() {
        // Transactions
        Route::get('transactions', 'Travoltron\Plaid\Controllers\SpendingController@allTransactions');
        Route::get('recent', 'Travoltron\Plaid\Controllers\SpendingController@recentTransactions');
        // Accounts
        Route::post('accounts', 'Travoltron\Plaid\Controllers\AccountController@getAccounts');
        // Income
        Route::post('income', 'Travoltron\Plaid\Controllers\AccountController@getIncome');

        Route::post('info', 'Travoltron\Plaid\Controllers\AccountController@getInfo');

        Route::post('risk', 'Travoltron\Plaid\Controllers\AccountController@getRisk');

    });


});
