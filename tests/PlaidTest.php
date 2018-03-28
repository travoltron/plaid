<?php

use Travoltron\Plaid\Plaid;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlaidTest extends TestCase
{

    use DatabaseMigrations;
    use DatabaseTransactions;

    public function testContactPlaid()
    {
        $categories = Plaid::categories();
        $this->assertNotNull($categories);
    }

    public function testAddAuthNoMfa()
    {
        $this->artisan('migrate:refresh');
        $this->artisan('db:seed');
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'wells'
            ],
            [
                'uuid' => '1234567890'
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
            ],
            [
                'uuid' => '1234567890'
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
            ],
            [
                'uuid' => '1234567890'
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
        $this->artisan('migrate:refresh');
        $this->artisan('db:seed');
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'chase'
            ],
            [
                'uuid' => '1234567890'
            ]);
        $this->json('POST', '/plaid/account/mfa',
            [
                'mfaCode' => '1234',
                'token' => 'test_chase',
                'type' => 'chase'
            ],
            [
                'uuid' => '1234567890'
            ])->seeJson([
                'status' => 200,
            ]);
    }

    public function testRepeatedMfaAuth()
    {
        $this->artisan('migrate:refresh');
        $this->artisan('db:seed');
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'bofa'
            ],
            [
                'uuid' => '1234567890'
            ]);
        $this->json('POST', '/plaid/account/mfa',
            [
                'mfaCode' => 'again',
                'token' => 'test_bofa',
                'type' => 'bofa'
            ],
            [
                'uuid' => '1234567890'
            ])->seeJson([
                'status' => 201,
            ]);
    }

    public function testBadMfaAuth()
    {
        $this->artisan('migrate:refresh');
        $this->artisan('db:seed');
        $this->json('POST', '/plaid/account/add',
            [
                'username' => 'plaid_test',
                'password' => 'plaid_good',
                'type' => 'chase'
            ],
            [
                'uuid' => '1234567890'
            ]);
        $this->json('POST', '/plaid/account/mfa',
            [
                'mfaCode' => 'wrong',
                'token' => 'test_chase',
                'type' => 'chase'
            ],
            [
                'uuid' => '1234567890'
            ])->seeJsonEquals([
                'data' => [],
                'errors' => [
                    'The MFA response provided was not correct.'
                ],
                'status' => 402
             ]);
    }


}
