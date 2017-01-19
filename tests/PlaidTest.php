<?php

use Travoltron\Plaid\Plaid;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlaidTest extends TestCase
{
    public function testContactPlaid()
    {
        $categories = Plaid::categories();
        $this->assertNotNull($categories);
    }

    public function testAddAuthNoMfa()
    {
         $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'wells'
            ])->seeJson([
                'status' => 200,
            ]);
    }

    public function testAddAuthMfa()
    {
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'chase'
            ])->seeJsonStructure([
                'data' => [
                    'mfa'
                ]
            ]);
    }

    public function testBadAuth()
    {
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'bad_password',
                'type' => 'chase'
            ])->seeJsonEquals([
                'data' => [],
                'errors' => [
                    'The username or password provided were not correct.'
                ],
                'status' => 402
             ]);
    }

    public function testCompletedMfaAuth()
    {
        // Initialize the stub account
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'chase'
            ]);
        $this->json('POST', '/plaid/account/mfa',
            [
                'mfaCode' => '1234',
                'token' => 'test_chase',
                'type' => 'chase'
            ])->seeJson([
                'status' => 200,
            ]);
    }

    public function testRepeatedMfaAuth()
    {
        // Initialize the stub account
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'bofa'
            ]);
        $this->json('POST', '/plaid/account/mfa',
            [
                'mfaCode' => 'again',
                'token' => 'test_bofa',
                'type' => 'bofa'
            ])->seeJson([
                'status' => 201,
            ]);
    }

    public function testBadMfaAuth()
    {
        // Initialize the stub account
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'chase'
            ]);
        $this->json('POST', '/plaid/account/mfa',
            [
                'mfaCode' => 'wrong',
                'token' => 'test_chase',
                'type' => 'chase'
            ])->seeJsonEquals([
                'data' => [],
                'errors' => [
                    'The MFA response provided was not correct.'
                ],
                'status' => 402
             ]);
    }


}
