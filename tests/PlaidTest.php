<?php

use Travoltron\Plaid;

class PlaidTest extends PHPUnit_Framework_TestCase {

    public function testContactPlaid()
    {
        $categories = Plaid::categories();
        $this->assertNotNull($categories);
    }
}
