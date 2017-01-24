<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaidTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plaid_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->index()->unsigned();
            $table->string('accountId')->index()->unsigned();
            $table->string('transactionId')->index()->unsigned();
            $table->decimal('amount', 15, 2);
            $table->string('date')->index()->unsigned();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('categoryId')->nullable();
            $table->text('meta')->nullable();
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
        Schema::dropIfExists('plaid_transactions');
    }
}
