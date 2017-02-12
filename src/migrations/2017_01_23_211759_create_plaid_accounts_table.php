<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaidAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plaid_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->index()->unsigned();
            $table->string('token');
            $table->string('institutionName');
            $table->string('debtHash')->nullable();
            $table->text('logo')->nullable();
            $table->string('accountName');
            $table->string('accountId');
            $table->string('last4')->default('0000');
            $table->string('type');
            $table->text('accountNumber')->nullable();
            $table->text('routingNumber')->nullable();
            $table->decimal('balance', 15, 2);
            $table->decimal('spendingLimit', 15, 2);
            $table->decimal('apr', 4, 2);
            $table->decimal('minimumPayment', 15, 2);
            $table->integer('batch')->default(0);
            $table->boolean('smartsave')->index()->unsigned()->default(false);
            $table->boolean('plaidAuth')->default(false);
            $table->boolean('plaidConnect')->index()->unsigned()->default(false);
            $table->boolean('plaidIncome')->index()->unsigned()->default(false);
            $table->boolean('plaidInfo')->index()->unsigned()->default(false);
            $table->boolean('plaidRisk')->index()->unsigned()->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plaid_accounts');
    }
}
